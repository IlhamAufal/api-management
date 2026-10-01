<?php

namespace App\Models;

use CodeIgniter\Model;

class MonitoringAppModel extends Model
{
    protected $table = 'monitoring_apps';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'app_code',
        'app_name',
        'description',
        'base_url',
        'database_name',
        'is_active',
        'sort_order',
    ];

    public function getActiveAppsWithTableCount()
    {
        return $this->select('monitoring_apps.*, COUNT(monitoring_app_tables.id) AS table_count')
            ->join('monitoring_app_tables', 'monitoring_app_tables.monitoring_app_id = monitoring_apps.id AND monitoring_app_tables.is_active = 1', 'left')
            ->where('monitoring_apps.is_active', 1)
            ->groupBy('monitoring_apps.id')
            ->orderBy('monitoring_apps.sort_order', 'ASC')
            ->orderBy('monitoring_apps.app_name', 'ASC')
            ->findAll();
    }

    public function findActiveByCode($appCode)
    {
        return $this->where('app_code', $appCode)
            ->where('is_active', 1)
            ->first();
    }

    public function getActiveTables($monitoringAppId)
    {
        return $this->db->table('monitoring_app_tables')
            ->where('monitoring_app_id', $monitoringAppId)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('table_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Map task codes to the application that monitors them.
     *
     * @return array<string,string> task_code => app_code
     */
    public function getLinkedTaskApps(array $taskCodes): array
    {
        if ($taskCodes === []) {
            return [];
        }

        $rows = $this->db->table('monitoring_app_tables')
            ->select('monitoring_app_tables.sync_task_code, monitoring_apps.app_code')
            ->join('monitoring_apps', 'monitoring_apps.id = monitoring_app_tables.monitoring_app_id')
            ->whereIn('monitoring_app_tables.sync_task_code', $taskCodes)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['sync_task_code']] = $row['app_code'];
        }

        return $map;
    }

    /**
     * Attach a sync task to its target table on every application that
     * already owns at least one linked table (the ingestion apps). If the
     * table row does not exist yet it is created; an unlinked row is claimed;
     * a row already owned by another task is left untouched.
     */
    public function linkTask($taskCode, $tableCode): void
    {
        $ownerIds = $this->db->table('monitoring_app_tables')
            ->select('monitoring_app_id')
            ->where('sync_task_code IS NOT NULL', null, false)
            ->where('sync_task_code !=', '')
            ->groupBy('monitoring_app_id')
            ->get()
            ->getResultArray();

        foreach ($ownerIds as $owner) {
            $appId = (int) $owner['monitoring_app_id'];
            $row = $this->db->table('monitoring_app_tables')
                ->where('monitoring_app_id', $appId)
                ->where('table_code', $tableCode)
                ->get()
                ->getRowArray();

            if ($row === null) {
                $max = $this->db->table('monitoring_app_tables')
                    ->selectMax('sort_order')
                    ->where('monitoring_app_id', $appId)
                    ->get()
                    ->getRowArray();

                $this->db->table('monitoring_app_tables')->insert([
                    'monitoring_app_id' => $appId,
                    'table_code'        => $tableCode,
                    'table_name'        => ucwords(str_replace('_', ' ', $tableCode)),
                    'sync_task_code'    => $taskCode,
                    'is_active'         => 1,
                    'sort_order'        => (int) ($max['sort_order'] ?? 0) + 10,
                ]);
            } elseif ((string) ($row['sync_task_code'] ?? '') === '') {
                $this->db->table('monitoring_app_tables')
                    ->where('id', $row['id'])
                    ->update(['sync_task_code' => $taskCode]);
            }
        }
    }

    /**
     * Clear task references, optionally scoped to one table code.
     */
    public function detachTask($taskCode, ?string $tableCode = null): void
    {
        $builder = $this->db->table('monitoring_app_tables')
            ->where('sync_task_code', $taskCode);

        if ($tableCode !== null) {
            $builder->where('table_code', $tableCode);
        }

        $builder->update(['sync_task_code' => null]);
    }
}
