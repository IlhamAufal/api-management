<?php

namespace App\Controllers;

use App\Models\ApiSyncTaskModel;
use App\Models\MonitoringAppModel;
use App\Models\SysSyncLogModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Monitoring extends BaseController
{
    public function index()
    {
        $appModel = new MonitoringAppModel();
        $logModel = new SysSyncLogModel();
        $apps = $appModel->getActiveAppsWithTableCount();

        foreach ($apps as &$app) {
            $tables = $appModel->getActiveTables((int) $app['id']);
            $app += $this->summarizeApplication($tables, $logModel);
        }
        unset($app);

        return view('pages/monitoring/index', [
            'title' => 'Monitoring Aplikasi | MD-Bridge',
            'page'  => 'monitoring',
            'apps'  => $apps,
        ]);
    }

    public function show($appCode)
    {
        $appModel = new MonitoringAppModel();
        $logModel = new SysSyncLogModel();
        $taskModel = new ApiSyncTaskModel();
        $app = $appModel->findActiveByCode($appCode);

        if ($app === null) {
            throw PageNotFoundException::forPageNotFound('Aplikasi monitoring tidak ditemukan.');
        }

        $tables = $appModel->getActiveTables((int) $app['id']);
        $taskCodes = [];
        $tableByTaskCode = [];

        foreach ($tables as &$table) {
            $taskCode = $table['sync_task_code'] ?? null;
            $table['last_cron'] = null;
            $table['cron_expression'] = null;

            if (!$taskCode) {
                $table['operational_status'] = 'NOT_CONNECTED';
                continue;
            }

            $taskCodes[] = $taskCode;
            $tableByTaskCode[$taskCode] = $table;
            $table['last_cron'] = $logModel->getLatestCronByTaskCode($taskCode);
            $table['operational_status'] = $table['last_cron']['status'] ?? 'UNKNOWN';
            $task = $taskModel->where('task_code', $taskCode)->first();
            $table['cron_expression'] = $task['cron_expression'] ?? null;
        }
        unset($table);

        $history = $logModel->getCronHistoryByTaskCodes(array_values(array_unique($taskCodes)), 50);
        foreach ($history as &$log) {
            $mappedTable = $tableByTaskCode[$log['task_code']] ?? [];
            $log['table_code'] = $mappedTable['table_code'] ?? '—';
            $log['table_name'] = $mappedTable['table_name'] ?? $log['task_code'];
        }
        unset($log);

        $summary = $this->summarizeApplication($tables, $logModel);

        return view('pages/monitoring/show', [
            'title'    => $app['app_name'] . ' | Monitoring MD-Bridge',
            'page'     => 'monitoring',
            'app'      => $app,
            'tables'   => $tables,
            'history'  => $history,
            'summary'  => $summary,
            'workflow' => $this->buildWorkflow($app, $tables, $summary),
        ]);
    }

    /**
     * Build a read-only workflow graph (nodes + connections) used by the
     * Drawflow visualization on the monitoring detail page. This is purely
     * a visual guide of the SAP -> Worker -> Tables -> Destination pipeline.
     */
    private function buildWorkflow(array $app, array $tables, array $summary): array
    {
        $nodes = [];
        $edges = [];

        // Column 0 - data origin
        $nodes[] = [
            'key'      => 'source',
            'column'   => 0,
            'variant'  => 'source',
            'icon'     => 'cloud',
            'title'    => 'SAP S/4HANA Cloud',
            'subtitle' => 'OData API source',
            'status'   => 'SOURCE',
            'meta'     => [],
        ];

        // Column 1 - the sync worker (cron)
        $nodes[] = [
            'key'      => 'worker',
            'column'   => 1,
            'variant'  => 'process',
            'icon'     => 'worker',
            'title'    => 'CI4 Sync Worker',
            'subtitle' => 'Cron scheduler',
            'status'   => $summary['operational_status'],
            'meta'     => [
                ['label' => 'Tabel termapping', 'value' => (int) $summary['mapped_tables'] . '/' . count($tables)],
                ['label' => 'Telemetry', 'value' => (int) $summary['reported_tables'] . ' aktif'],
            ],
        ];

        // Column 2 - one node per table, coloured by its operational status
        foreach ($tables as $table) {
            $lastCron = $table['last_cron'] ?? null;
            $meta = [];

            if ($lastCron) {
                $meta[] = [
                    'label' => 'Read / Written',
                    'value' => number_format((int) ($lastCron['records_read'] ?? 0)) . ' / ' . number_format((int) ($lastCron['records_written'] ?? 0)),
                ];
                $timestamp = $lastCron['finished_at'] ?? $lastCron['executed_at'] ?? null;
                $meta[] = [
                    'label' => 'Cron terakhir',
                    'value' => $timestamp ? date('d M, H:i', strtotime($timestamp)) : '—',
                ];
            }

            if (!empty($table['cron_expression'])) {
                $meta[] = ['label' => 'Schedule', 'value' => $table['cron_expression']];
            }

            $nodes[] = [
                'key'      => 'table_' . $table['table_code'],
                'column'   => 2,
                'variant'  => 'table',
                'icon'     => 'table',
                'title'    => $table['table_name'],
                'subtitle' => $table['table_code'],
                'status'   => $table['operational_status'] ?? 'UNKNOWN',
                'meta'     => $meta,
            ];
        }

        // Column 3 - destination database / application
        $nodes[] = [
            'key'      => 'sink',
            'column'   => 3,
            'variant'  => 'sink',
            'icon'     => 'database',
            'title'    => $app['app_name'],
            'subtitle' => $app['database_name'] ?: 'Target database',
            'status'   => $summary['operational_status'],
            'meta'     => [],
        ];

        // Connections: source -> worker, then fan-out to tables and fan-in to sink.
        $edges[] = ['from' => 'source', 'to' => 'worker'];

        $tableKeys = array_values(array_filter(array_map(
            static fn ($node) => $node['variant'] === 'table' ? $node['key'] : null,
            $nodes
        )));

        if ($tableKeys === []) {
            $edges[] = ['from' => 'worker', 'to' => 'sink'];
        } else {
            foreach ($tableKeys as $tableKey) {
                $edges[] = ['from' => 'worker', 'to' => $tableKey];
                $edges[] = ['from' => $tableKey, 'to' => 'sink'];
            }
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    private function summarizeApplication(array $tables, SysSyncLogModel $logModel)
    {
        $mappedTables = 0;
        $reportedTables = 0;
        $failedTables = 0;
        $runningTables = 0;
        $latestTimestamp = null;

        foreach ($tables as $table) {
            $taskCode = $table['sync_task_code'] ?? null;
            if (!$taskCode) {
                continue;
            }

            $mappedTables++;
            $latest = $table['last_cron'] ?? $logModel->getLatestCronByTaskCode($taskCode);
            if (!$latest) {
                continue;
            }

            $reportedTables++;
            $failedTables += $latest['status'] === 'FAILED' ? 1 : 0;
            $runningTables += $latest['status'] === 'RUNNING' ? 1 : 0;
            $timestamp = $latest['finished_at'] ?: $latest['executed_at'];
            if ($latestTimestamp === null || strtotime($timestamp) > strtotime($latestTimestamp)) {
                $latestTimestamp = $timestamp;
            }
        }

        if ($mappedTables === 0) {
            $status = 'NOT_CONNECTED';
        } elseif ($failedTables > 0) {
            $status = 'FAILED';
        } elseif ($runningTables > 0) {
            $status = 'RUNNING';
        } elseif ($reportedTables < $mappedTables) {
            $status = 'UNKNOWN';
        } else {
            $status = 'SUCCESS';
        }

        return [
            'operational_status' => $status,
            'mapped_tables'      => $mappedTables,
            'reported_tables'    => $reportedTables,
            'failed_tables'      => $failedTables,
            'last_cron_at'       => $latestTimestamp,
        ];
    }
}
