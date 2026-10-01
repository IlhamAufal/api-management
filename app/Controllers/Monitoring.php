<?php

namespace App\Controllers;

use App\Libraries\Sync\HistoryCheckService;
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
            $isCheckApp = HistoryCheckService::sourceForAppCode($app['app_code']) !== null;
            $tables = $appModel->getActiveTables((int) $app['id']);

            // Aplikasi cek read-only hanya menulis log MANUAL_UI, bukan CRON.
            foreach ($tables as &$table) {
                $taskCode = $table['sync_task_code'] ?? null;
                $table['last_cron'] = $taskCode
                    ? ($isCheckApp
                        ? $logModel->getLatestLogByTaskCode($taskCode)
                        : $logModel->getLatestCronByTaskCode($taskCode))
                    : null;
            }
            unset($table);

            $app += $this->summarizeApplication($tables, $logModel);
            $app['check_supported'] = $isCheckApp;
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

        $historyCheck = new HistoryCheckService();
        $checkSource  = $historyCheck->sourceForApp($appCode);
        $isCheckApp   = $checkSource !== null;

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
            $table['last_cron'] = $isCheckApp
                ? $logModel->getLatestLogByTaskCode($taskCode)
                : $logModel->getLatestCronByTaskCode($taskCode);
            $table['operational_status'] = $table['last_cron']['status'] ?? 'UNKNOWN';
            $task = $taskModel->where('task_code', $taskCode)->first();
            $table['cron_expression'] = $task['cron_expression'] ?? null;
        }
        unset($table);

        $uniqueCodes = array_values(array_unique($taskCodes));
        $history = $isCheckApp
            ? $logModel->getHistoryByTaskCodes($uniqueCodes, 50)
            : $logModel->getCronHistoryByTaskCodes($uniqueCodes, 50);
        foreach ($history as &$log) {
            $mappedTable = $tableByTaskCode[$log['task_code']] ?? [];
            $log['table_code'] = $mappedTable['table_code'] ?? '—';
            $log['table_name'] = $mappedTable['table_name'] ?? $log['task_code'];
        }
        unset($log);

        $summary = $this->summarizeApplication($tables, $logModel);

        $check = null;
        if ($isCheckApp) {
            $check = [
                'source'         => $checkSource,
                'source_label'   => HistoryCheckService::SOURCE_LABELS[$checkSource] ?? $checkSource,
                'configured'     => $historyCheck->isConfigured($checkSource),
                'npd_configured' => $historyCheck->isConfigured('npd'),
                'sap_configured' => $historyCheck->isConfigured('sap'),
                'comparison'     => $historyCheck->comparison(),
            ];
        }

        return view('pages/monitoring/show', [
            'title'    => $app['app_name'] . ' | Monitoring MD-Bridge',
            'page'     => 'monitoring',
            'app'      => $app,
            'tables'   => $tables,
            'history'  => $history,
            'summary'  => $summary,
            'check'    => $check,
            'workflow' => $this->buildWorkflow($app, $tables, $summary, $isCheckApp),
        ]);
    }

    /**
     * Jalankan pemeriksaan read-only untuk satu aplikasi (AJAX).
     * Menulis log MANUAL_UI per tabel lalu mengembalikan hasil + perbandingan.
     */
    public function check($appCode)
    {
        $appModel = new MonitoringAppModel();
        $app = $appModel->findActiveByCode($appCode);

        if ($app === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'FAILED',
                'message' => 'Aplikasi monitoring tidak ditemukan.',
            ]);
        }

        $service = new HistoryCheckService();
        if ($service->sourceForApp($appCode) === null) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'FAILED',
                'message' => 'Aplikasi ini tidak memiliki koneksi database read-only.',
            ]);
        }

        // Pemeriksaan membaca beberapa tabel remote; longgarkan batas eksekusi.
        set_time_limit(120);

        $result = $service->runCheck($appCode);

        return $this->response->setJSON($result);
    }

    public function showTable($appCode, $tableCode)
    {
        $appModel = new MonitoringAppModel();
        $logModel = new SysSyncLogModel();
        $taskModel = new ApiSyncTaskModel();
        $app = $appModel->findActiveByCode($appCode);

        if ($app === null) {
            throw PageNotFoundException::forPageNotFound('Aplikasi monitoring tidak ditemukan.');
        }

        $tables = $appModel->getActiveTables((int) $app['id']);
        $table = null;

        foreach ($tables as $row) {
            if (($row['table_code'] ?? null) === $tableCode) {
                $table = $row;
                break;
            }
        }

        if ($table === null) {
            throw PageNotFoundException::forPageNotFound('Tabel monitoring tidak ditemukan pada aplikasi ini.');
        }

        $isCheckApp = HistoryCheckService::sourceForAppCode($appCode) !== null;
        $taskCode = $table['sync_task_code'] ?? null;
        $task = null;

        if ($taskCode) {
            $table['last_cron'] = $isCheckApp
                ? $logModel->getLatestLogByTaskCode($taskCode)
                : $logModel->getLatestCronByTaskCode($taskCode);
            $table['operational_status'] = $table['last_cron']['status'] ?? 'UNKNOWN';
            $task = $taskModel->where('task_code', $taskCode)->first();
            $table['cron_expression'] = $task['cron_expression'] ?? null;
        } else {
            $table['last_cron'] = null;
            $table['operational_status'] = 'NOT_CONNECTED';
            $table['cron_expression'] = null;
        }

        $history = [];
        if ($taskCode) {
            $history = $isCheckApp
                ? $logModel->getHistoryByTaskCodes([$taskCode], 50)
                : $logModel->getCronHistoryByTaskCodes([$taskCode], 50);
        }
        $lastCron = $table['last_cron'];
        $totals = $this->summarizeTableHistory($history);
        $workflow = $this->buildTableWorkflow($app, $table, $task);

        return view('pages/monitoring/table', [
            'title'     => $table['table_name'] . ' | ' . $app['app_name'] . ' | Monitoring MD-Bridge',
            'page'      => 'monitoring',
            'app'       => $app,
            'table'     => $table,
            'task'      => $task,
            'history'   => $history,
            'totals'    => $totals,
            'lastCron'  => $lastCron,
            'workflow'  => $workflow,
            'checkMode' => $isCheckApp,
        ]);
    }

    /**
     * Aggregate a single table's cron history into compact summary metrics.
     */
    private function summarizeTableHistory(array $history): array
    {
        $success = 0;
        $failed = 0;
        $rows = 0;
        $durations = [];
        $lastFailure = null;

        foreach ($history as $log) {
            $success += $log['status'] === 'SUCCESS' ? 1 : 0;
            $failed += $log['status'] === 'FAILED' ? 1 : 0;
            $rows += (int) $log['records_written'];
            $durations[] = (float) $log['duration_sec'];

            if ($log['status'] === 'FAILED' && $lastFailure === null) {
                $lastFailure = $log;
                break;
            }
        }

        return [
            'success_count' => $success,
            'failed_count'  => $failed,
            'rows_written'  => $rows,
            'average_duration' => count($durations) > 0 ? array_sum($durations) / count($durations) : 0,
            'last_failure'  => $lastFailure,
        ];
    }

    /**
     * Focused workflow graph for one table:
     * SAP source -> sync task (cron) -> the table -> destination app.
     */
    private function buildTableWorkflow(array $app, array $table, ?array $task): array
    {
        $nodes = [];
        $edges = [];
        $status = $table['operational_status'] ?? 'UNKNOWN';
        $lastCron = $table['last_cron'];
        $lastMeta = [];

        if ($lastCron) {
            $lastMeta[] = [
                'label' => 'Read / Written',
                'value' => number_format((int) ($lastCron['records_read'] ?? 0)) . ' / ' . number_format((int) ($lastCron['records_written'] ?? 0)),
            ];
            $timestamp = $lastCron['finished_at'] ?? $lastCron['executed_at'] ?? null;
            $lastMeta[] = [
                'label' => 'Cron terakhir',
                'value' => $timestamp ? date('d M, H:i', strtotime($timestamp)) : '—',
            ];
        }

        if (!empty($table['cron_expression'])) {
            $lastMeta[] = ['label' => 'Schedule', 'value' => $table['cron_expression']];
        }

        // Mode cek read-only: tabel punya sync_task_code tapi tidak ada
        // baris di api_sync_tasks (task null) => sumbernya DB, bukan SAP.
        $isCheck = $task === null && !empty($table['sync_task_code']);

        $nodes[] = [
            'key'      => 'source',
            'column'   => 0,
            'variant'  => 'source',
            'icon'     => 'cloud',
            'title'    => $isCheck ? ('MySQL ' . ($app['database_name'] ?: 'sumber')) : 'SAP S/4HANA Cloud',
            'subtitle' => $isCheck
                ? 'Koneksi read-only (SELECT)'
                : (($task['source_endpoint'] ?? '') !== '' ? 'OData API source' : 'Data source'),
            'status'   => 'SOURCE',
            'meta'     => [],
        ];

        $nodes[] = [
            'key'      => 'task',
            'column'   => 1,
            'variant'  => 'process',
            'icon'     => 'worker',
            'title'    => $isCheck ? 'HistoryCheckService' : ($task['task_name'] ?? 'Belum terhubung'),
            'subtitle' => $isCheck
                ? ($table['sync_task_code'] . ' (cek manual)')
                : ($table['sync_task_code'] ?: 'Tidak ada task'),
            'status'   => $status,
            'meta'     => $lastMeta,
        ];

        $nodes[] = [
            'key'      => 'table',
            'column'   => 2,
            'variant'  => 'table',
            'icon'     => 'table',
            'title'    => $table['table_name'],
            'subtitle' => $table['table_code'],
            'status'   => $status,
            'meta'     => [],
        ];

        $nodes[] = [
            'key'      => 'sink',
            'column'   => 3,
            'variant'  => 'sink',
            'icon'     => 'database',
            'title'    => $app['app_name'],
            'subtitle' => $app['database_name'] ?: 'Target database',
            'status'   => $status,
            'meta'     => [],
        ];

        $edges = [
            ['from' => 'source', 'to' => 'task'],
            ['from' => 'task', 'to' => 'table'],
            ['from' => 'table', 'to' => 'sink'],
        ];
        if (!$table['sync_task_code']) {
            $edges = [
                ['from' => 'source', 'to' => 'table'],
                ['from' => 'table', 'to' => 'sink'],
            ];
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    /**
     * Build a read-only workflow graph (nodes + connections) used by the
     * Drawflow visualization on the monitoring detail page. This is purely
     * a visual guide of the SAP -> Worker -> Tables -> Destination pipeline.
     */
    private function buildWorkflow(array $app, array $tables, array $summary, bool $checkMode = false): array
    {
        $nodes = [];
        $edges = [];

        // Column 0 - data origin
        $nodes[] = [
            'key'      => 'source',
            'column'   => 0,
            'variant'  => 'source',
            'icon'     => 'cloud',
            'title'    => $checkMode ? ('MySQL ' . ($app['database_name'] ?: 'sumber')) : 'SAP S/4HANA Cloud',
            'subtitle' => $checkMode ? 'Koneksi read-only (SELECT)' : 'OData API source',
            'status'   => 'SOURCE',
            'meta'     => [],
        ];

        // Column 1 - the sync worker (cron) / read-only checker
        $nodes[] = [
            'key'      => 'worker',
            'column'   => 1,
            'variant'  => 'process',
            'icon'     => 'worker',
            'title'    => $checkMode ? 'HistoryCheckService' : 'CI4 Sync Worker',
            'subtitle' => $checkMode ? 'Pemeriksaan read-only (manual)' : 'Cron scheduler',
            'status'   => $summary['operational_status'],
            'meta'     => [
                ['label' => 'Tabel termapping', 'value' => (int) $summary['mapped_tables'] . '/' . count($tables)],
                ['label' => $checkMode ? 'Terpantau' : 'Telemetry', 'value' => (int) $summary['reported_tables'] . ' aktif'],
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
