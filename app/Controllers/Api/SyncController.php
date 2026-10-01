<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\Sync\SyncPipeline;
use App\Models\ApiSyncTaskModel;

class SyncController extends BaseController
{
    /**
     * Sync spesifik per tabel / task_code (manual, tanpa cron).
     */
    public function run($taskCode)
    {
        // Fetch + upsert bisa lebih lama dari batas eksekusi default.
        set_time_limit(300);

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

        $result   = (new SyncPipeline())->run($task, 'MANUAL_UI');
        $status   = $result['log']['status'] ?? 'FAILED';
        $isFailed = $status === 'FAILED';

        return $this->response
            ->setStatusCode($isFailed ? 500 : 200)
            ->setJSON([
                'status'  => $status,
                'message' => $result['message'],
                'log'     => $result['log'],
            ]);
    }

    /**
     * Wrapper Sync All: menjalankan semua task aktif secara berurutan.
     */
    public function runAll()
    {
        // Berurutan untuk semua task aktif; longgarkan batas eksekusi default.
        set_time_limit(900);

        $taskModel = new ApiSyncTaskModel();
        $tasks = $taskModel->where('is_active', 1)->findAll();

        if (empty($tasks)) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'FAILED',
                    'message' => 'Tidak ada task aktif yang ditemukan.',
                    'details' => [],
                ]);
        }

        $pipeline = new SyncPipeline();
        $results = [];
        $hasFailure = false;

        foreach ($tasks as $task) {
            $syncResult = $pipeline->run($task, 'MANUAL_UI');
            $status = $syncResult['log']['status'] ?? 'FAILED';

            $results[] = [
                'task_code' => $task['task_code'],
                'task_name' => $task['task_name'] ?? $task['task_code'],
                'status'    => $status,
                'message'   => $syncResult['message'],
            ];

            if ($status === 'FAILED') {
                $hasFailure = true;
            }
        }

        return $this->response->setJSON([
            'status'  => $hasFailure ? 'PARTIAL_FAILED' : 'SUCCESS',
            'message' => $hasFailure ? 'Beberapa task sinkronisasi mengalami kendala.' : 'Seluruh task sinkronisasi berhasil dijalankan.',
            'details' => $results,
        ]);
    }
}
