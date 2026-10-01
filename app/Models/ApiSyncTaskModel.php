<?php

namespace App\Models;

use CodeIgniter\Model;
use InvalidArgumentException;

class ApiSyncTaskModel extends Model
{
    protected $table = 'api_sync_tasks';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
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

    protected $validationRules = [
        'id'              => 'permit_empty|is_natural_no_zero',
        'task_code'       => 'required|alpha_dash|min_length[3]|max_length[50]|is_unique[api_sync_tasks.task_code,id,{id}]',
        'task_name'       => 'required|string|min_length[3]|max_length[100]',
        'category'        => 'required|string|max_length[50]',
        'source_type'     => 'required|in_list[DIRECT_DB,HTTP_GET,HTTP_POST]',
        'source_endpoint' => 'required|string|max_length[500]',
        'target_table'    => 'required|alpha_dash|max_length[100]',
        'batch_size'      => 'required|is_natural|greater_than[0]|less_than_equal_to[10000]',
        'cron_expression' => 'permit_empty|string|max_length[50]',
        'is_active'       => 'required|in_list[0,1]',
    ];

    protected $validationMessages = [
        'task_code' => [
            'is_unique' => 'Task Code sudah digunakan oleh task lain.',
            'alpha_dash' => 'Task Code hanya boleh berisi huruf, angka, dash, dan underscore.',
        ],
        'batch_size' => [
            'greater_than' => 'Batch Size harus lebih dari 0.',
            'less_than_equal_to' => 'Batch Size maksimal 10000.',
        ],
    ];

    private $targetTables = [
        'sap_material_master',
        'sap_customer_master',
        'sap_customer_material',
        'sap_customer_sales_area',
    ];

    public function getTargetTables(): array
    {
        return $this->targetTables;
    }

    public function getAllTasks(): array
    {
        return $this->orderBy('id', 'ASC')->findAll();
    }

    public function getActiveTasks(): array
    {
        return $this->where('is_active', 1)
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function findByCode($taskCode)
    {
        return $this->where('task_code', $taskCode)->first();
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
