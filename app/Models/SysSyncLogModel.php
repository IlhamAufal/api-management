<?php

namespace App\Models;

use CodeIgniter\Model;

class SysSyncLogModel extends Model
{
    protected $table = 'sys_sync_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'task_code',
        'trigger_type',
        'status',
        'step_failed',
        'records_read',
        'records_written',
        'duration_sec',
        'error_message',
        'executed_at',
        'finished_at',
    ];

    public function getLatestLogByTaskCode($taskCode)
    {
        return $this->where('task_code', $taskCode)
            ->orderBy('executed_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();
    }
}
