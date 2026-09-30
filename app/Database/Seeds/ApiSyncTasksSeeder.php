<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ApiSyncTasksSeeder extends Seeder
{
    public function run()
    {
        $tasks = [
            [
                'task_code'       => 'sap_material',
                'task_name'       => 'Material Master & Price',
                'category'        => 'SAP_MASTER',
                'source_type'     => 'DIRECT_DB',
                'source_endpoint' => 'yp_so.sap_material_master',
                'target_table'    => 'sap_material_master',
                'batch_size'      => 1500,
                'cron_expression' => '10 8 * * 1-6',
                'is_active'       => 1,
            ],
            [
                'task_code'       => 'sap_customer',
                'task_name'       => 'Customer Master Data',
                'category'        => 'SAP_MASTER',
                'source_type'     => 'DIRECT_DB',
                'source_endpoint' => 'yp_so.sap_customer_master',
                'target_table'    => 'sap_customer_master',
                'batch_size'      => 1500,
                'cron_expression' => '15 8 * * 1-6',
                'is_active'       => 1,
            ],
            [
                'task_code'       => 'sap_customer_material',
                'task_name'       => 'Customer Material Mapping',
                'category'        => 'SAP_MASTER',
                'source_type'     => 'DIRECT_DB',
                'source_endpoint' => 'yp_so.sap_customer_material',
                'target_table'    => 'sap_customer_material',
                'batch_size'      => 1500,
                'cron_expression' => '20 8 * * 1-6',
                'is_active'       => 1,
            ],
            [
                'task_code'       => 'sap_customer_sales_area',
                'task_name'       => 'Customer Sales Area Matrix',
                'category'        => 'SAP_MASTER',
                'source_type'     => 'DIRECT_DB',
                'source_endpoint' => 'yp_so.sap_customer_sales_area',
                'target_table'    => 'sap_customer_sales_area',
                'batch_size'      => 1500,
                'cron_expression' => '25 8 * * 1-6',
                'is_active'       => 1,
            ],
        ];

        $builder = $this->db->table('api_sync_tasks');

        foreach ($tasks as $task) {
            $exists = $builder->where('task_code', $task['task_code'])->countAllResults(false);

            if ($exists === 0) {
                $builder->insert($task);
            }

            // Reset builder state for the next iteration.
            $builder->resetQuery();
        }
    }
}
