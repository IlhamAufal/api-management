<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ApiSyncTaskModel;
use App\Models\SysSyncLogModel;
use Throwable;

class SyncController extends BaseController
{
    public function run($taskCode)
    {
        $taskModel = new ApiSyncTaskModel();
        $task = $taskModel->where('task_code', $taskCode)->first();

        if (!$task) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'FAILED',
                    'message' => 'Sync task tidak ditemukan.',
                    'log'     => null,
                ]);
        }

        $startedAt = microtime(true);
        usleep(300000);

        $failed = random_int(1, 5) === 1;
        $duration = round(microtime(true) - $startedAt, 2);
        $now = date('Y-m-d H:i:s');
        $logData = [
            'task_code'      => $task['task_code'],
            'trigger_type'   => 'MANUAL_UI',
            'status'         => $failed ? 'FAILED' : 'SUCCESS',
            'step_failed'    => $failed ? 'FETCH_GET' : 'NONE',
            'records_read'   => 0,
            'records_written'=> 0,
            'duration_sec'   => $duration,
            'error_message'  => $failed ? 'Connection timeout to SAP endpoint' : null,
            'executed_at'    => $now,
            'finished_at'    => $now,
        ];

        if (!$failed) {
            $records = $taskModel->countTargetTableRows($task['target_table']);
            $logData['records_read'] = $records;
            $logData['records_written'] = $records;
        }

        try {
            $logModel = new SysSyncLogModel();
            $logModel->insert($logData);
            $log = $logModel->find($logModel->getInsertID());
        } catch (Throwable $exception) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => 'FAILED',
                    'message' => 'Log sinkronisasi gagal disimpan.',
                    'log'     => null,
                ]);
        }

        return $this->response->setJSON([
            'status'  => $log['status'],
            'message' => $failed
                ? 'Sync gagal: ' . $logData['error_message']
                : 'Sync ' . ($task['task_name'] ?? $taskCode) . ' berhasil.',
            'log'     => $log,
        ]);
    }
}
