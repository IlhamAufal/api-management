<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MonitoringAppTablesSeeder extends Seeder
{
    public function run()
    {
        $tables = [
            ['table_code' => 'sap_material_master', 'table_name' => 'Material Master', 'api_resource' => '/api/monitoring/tables/sap_material_master', 'sync_task_code' => 'sap_material', 'sort_order' => 10],
            ['table_code' => 'sap_customer_master', 'table_name' => 'Customer Master', 'api_resource' => '/api/monitoring/tables/sap_customer_master', 'sync_task_code' => 'sap_customer', 'sort_order' => 20],
            ['table_code' => 'sap_customer_material', 'table_name' => 'Customer Material', 'api_resource' => '/api/monitoring/tables/sap_customer_material', 'sync_task_code' => 'sap_customer_material', 'sort_order' => 30],
            ['table_code' => 'sap_customer_sales_area', 'table_name' => 'Customer Sales Area', 'api_resource' => '/api/monitoring/tables/sap_customer_sales_area', 'sync_task_code' => 'sap_customer_sales_area', 'sort_order' => 40],
        ];

        $apps = $this->db
            ->table('monitoring_apps')
            ->whereIn('app_code', ['sap-sync-ci4', 'yp_npd', 'sap-get-ci3'])
            ->get()
            ->getResultArray();

        foreach ($apps as $app) {
            foreach ($tables as $table) {
                $data = $table + [
                    'monitoring_app_id' => (int) $app['id'],
                    'is_active'         => 1,
                ];
                $data['sync_task_code'] = $app['app_code'] === 'sap-sync-ci4'
                    ? $table['sync_task_code']
                    : null;

                $builder = $this->db->table('monitoring_app_tables');
                $existing = $builder
                    ->where('monitoring_app_id', $data['monitoring_app_id'])
                    ->where('table_code', $data['table_code'])
                    ->get()
                    ->getRowArray();

                if ($existing === null) {
                    $builder->insert($data);
                    continue;
                }

                $builder->where('id', $existing['id'])->update($data);
            }
        }
    }
}
