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

    public function getLatestCronByTaskCode($taskCode)
    {
        return $this->where('task_code', $taskCode)
            ->where('trigger_type', 'CRON')
            ->orderBy('executed_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();
    }

    public function getCronHistoryByTaskCodes(array $taskCodes, $limit = 50)
    {
        if ($taskCodes === []) {
            return [];
        }

        return $this->whereIn('task_code', $taskCodes)
            ->where('trigger_type', 'CRON')
            ->orderBy('executed_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll($limit);
    }

    public function getDashboardSummary()
    {
        return $this->db->table($this->table)
            ->select("COUNT(*) AS total_runs, SUM(status IN ('SUCCESS','FAILED','WARNING')) AS completed_runs, SUM(status = 'SUCCESS') AS successful_runs, SUM(status = 'FAILED') AS failed_runs, SUM(status = 'RUNNING') AS running_runs, COALESCE(SUM(records_written), 0) AS records_written, COALESCE(AVG(duration_sec), 0) AS average_duration", false)
            ->get()
            ->getRowArray();
    }

    public function getDailyStats($days)
    {
        $startDate = date('Y-m-d 00:00:00', strtotime('-' . max(0, (int) $days - 1) . ' days'));

        return $this->db->table($this->table)
            ->select("DATE(executed_at) AS run_date, COUNT(*) AS total_count, SUM(status = 'SUCCESS') AS success_count, SUM(status = 'FAILED') AS failed_count, SUM(status = 'WARNING') AS warning_count", false)
            ->where('executed_at >=', $startDate)
            ->groupBy('DATE(executed_at)', false)
            ->orderBy('run_date', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getRecentFailures($limit)
    {
        return $this->where('status', 'FAILED')
            ->orderBy('executed_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll($limit);
    }

    public function getLatestRun()
    {
        return $this->orderBy('executed_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();
    }
}
