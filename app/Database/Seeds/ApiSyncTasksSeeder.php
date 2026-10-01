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
                'source_type'     => 'HTTP_GET',
                'source_endpoint' => 'https://my433897-api.s4hana.cloud.sap/sap/opu/odata/sap/API_PRODUCT_SRV/A_Product',
                'target_table'    => 'sap_material_master',
                'batch_size'      => 1500,
                'cron_expression' => '10 8 * * 1-6',
                // Nonaktif sementara: sumber SAP butuh VPN (lihat sap-sync-ci4).
                'is_active'       => 0,
            ],
            [
                'task_code'       => 'sap_customer',
                'task_name'       => 'Customer Master Data',
                'category'        => 'SAP_MASTER',
                'source_type'     => 'HTTP_GET',
                'source_endpoint' => 'https://my433897-api.s4hana.cloud.sap/sap/opu/odata/sap/API_BUSINESS_PARTNER/A_Customer',
                'target_table'    => 'sap_customer_master',
                'batch_size'      => 1500,
                'cron_expression' => '15 8 * * 1-6',
                // Nonaktif sementara: sumber SAP butuh VPN (lihat sap-sync-ci4).
                'is_active'       => 0,
            ],
            [
                'task_code'       => 'sap_customer_material',
                'task_name'       => 'Customer Material Mapping',
                'category'        => 'SAP_MASTER',
                'source_type'     => 'HTTP_GET',
                'source_endpoint' => 'https://my433897-api.s4hana.cloud.sap/sap/opu/odata/sap/API_CUSTOMER_MATERIAL_SRV/A_CustomerMaterial',
                'target_table'    => 'sap_customer_material',
                'batch_size'      => 1500,
                'cron_expression' => '20 8 * * 1-6',
                // Nonaktif sementara: sumber SAP butuh VPN (lihat sap-sync-ci4).
                'is_active'       => 0,
            ],
            [
                'task_code'       => 'sap_customer_sales_area',
                'task_name'       => 'Customer Sales Area Matrix',
                'category'        => 'SAP_MASTER',
                'source_type'     => 'HTTP_GET',
                'source_endpoint' => 'https://my433897-api.s4hana.cloud.sap/sap/opu/odata/sap/API_CUSTOMER_SRV/A_CustomerSalesArea',
                'target_table'    => 'sap_customer_sales_area',
                'batch_size'      => 1500,
                'cron_expression' => '25 8 * * 1-6',
                // Nonaktif sementara: sumber SAP butuh VPN (lihat sap-sync-ci4).
                'is_active'       => 0,
            ],
        ];

        $builder = $this->db->table('api_sync_tasks');

        foreach ($tasks as $task) {
            $exists = $builder->where('task_code', $task['task_code'])->countAllResults(false);

            if ($exists === 0) {
                $builder->insert($task);
                $builder->resetQuery();
                continue;
            }

            // Keep existing rows in sync with the new source configuration.
            $builder->resetQuery();
            $builder->where('task_code', $task['task_code'])
                ->update([
                    'source_type'     => $task['source_type'],
                    'source_endpoint' => $task['source_endpoint'],
                ]);
        }
    }
}
