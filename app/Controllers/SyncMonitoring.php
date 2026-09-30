<?php

namespace App\Controllers;

use App\Models\ApiSyncTaskModel;
use App\Models\SysSyncLogModel;

class SyncMonitoring extends BaseController
{
    private $entityLabels = [
        'sap_material' => 'Material',
        'sap_customer' => 'Customer',
        'sap_customer_material' => 'Customer Material',
        'sap_customer_sales_area' => 'Customer Sales Area',
    ];

    public function index()
    {
        $taskModel = new ApiSyncTaskModel();
        $logModel = new SysSyncLogModel();
        $tasks = [];

        foreach ($taskModel->getActiveTasks() as $task) {
            $lastLog = $logModel->getLatestLogByTaskCode($task['task_code']);

            $tasks[] = [
                'id'               => (int) $task['id'],
                'task_code'        => $task['task_code'],
                'entity_label'     => $this->entityLabels[$task['task_code']] ?? $task['task_name'],
                'task_name'        => $task['task_name'],
                'source_type'      => $task['source_type'],
                'source_endpoint'  => $task['source_endpoint'],
                'target_table'     => $task['target_table'],
                'total_rows'       => $taskModel->countTargetTableRows($task['target_table']),
                'cron_expression'  => $task['cron_expression'],
                'last_log'         => $lastLog,
            ];
        }

        return view('pages/sync_monitoring/index', [
            'title'          => 'Dashboard Monitoring | MD-Bridge',
            'page'           => 'monitoring',
            'tasks'          => $tasks,
            'metrics'        => $this->buildMetrics($tasks),
            'pipelineStatus' => $this->getPipelineStatus($tasks),
        ]);
    }

    private function buildMetrics($tasks)
    {
        $totalRowsIngested = 0;
        $successful = 0;
        $completed = 0;
        $durations = [];

        foreach ($tasks as $task) {
            $log = $task['last_log'];
            if (!$log) {
                continue;
            }

            $totalRowsIngested += (int) $log['records_written'];
            if ($log['status'] === 'SUCCESS') {
                $successful++;
            }
            if (in_array($log['status'], ['SUCCESS', 'FAILED', 'WARNING'], true)) {
                $completed++;
            }
            $durations[] = (float) $log['duration_sec'];
        }

        return [
            [
                'title'       => 'Total Rows Ingested',
                'value'       => number_format($totalRowsIngested),
                'subText'     => 'From latest execution logs',
                'icon'        => 'database',
                'statusColor' => 'brand',
            ],
            [
                'title'       => 'Success Rate',
                'value'       => $completed > 0 ? round(($successful / $completed) * 100, 1) . '%' : '—',
                'subText'     => $completed . ' completed executions',
                'icon'        => 'check',
                'statusColor' => 'success',
            ],
            [
                'title'       => 'Average Duration',
                'value'       => count($durations) > 0 ? number_format(array_sum($durations) / count($durations), 2) . 's' : '—',
                'subText'     => 'Latest log per entity',
                'icon'        => 'clock',
                'statusColor' => 'warning',
            ],
        ];
    }

    private function getPipelineStatus($tasks)
    {
        $hasLog = false;
        $hasFailure = false;
        $hasRunning = false;

        foreach ($tasks as $task) {
            if (!$task['last_log']) {
                continue;
            }

            $hasLog = true;
            $hasFailure = $hasFailure || $task['last_log']['status'] === 'FAILED';
            $hasRunning = $hasRunning || $task['last_log']['status'] === 'RUNNING';
        }

        if ($hasFailure) {
            return 'FAILED';
        }
        if (!$hasLog || $hasRunning) {
            return 'WARNING';
        }

        return 'SUCCESS';
    }
}
