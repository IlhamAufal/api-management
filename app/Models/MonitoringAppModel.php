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
}
