<?php

namespace App\Models;

use CodeIgniter\Model;
use InvalidArgumentException;

class ApiSyncTaskModel extends Model
{
    protected $table = 'api_sync_tasks';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'task_code',
        'task_name',
        'category',
        'source_type',
        'source_endpoint',
        'target_table',
        'batch_size',
        'cron_expression',
        'is_active',
    ];

    private $targetTables = [
        'sap_material_master',
        'sap_customer_master',
        'sap_customer_material',
        'sap_customer_sales_area',
    ];

    public function getActiveTasks()
    {
        return $this->where('is_active', 1)
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function countTargetTableRows($table)
    {
        if (!in_array($table, $this->targetTables, true)) {
            throw new InvalidArgumentException('Target table is not allowed.');
        }

        return (int) $this->db->table($table)->countAllResults();
    }

    public function isAllowedTargetTable($table)
    {
        return in_array($table, $this->targetTables, true);
    }
}
