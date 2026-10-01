<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MonitoringAppTablesSeeder extends Seeder
{
    /**
     * Sinkron task code per aplikasi.
     *
     * - sap-sync-ci4 : task fetch HTTP yang sudah ada (tetap).
     * - yp_npd       : riwayat cek read-only ke grup koneksi `npd`.
     * - sap-get-ci3  : riwayat cek read-only ke grup koneksi `sap`.
     */
    private const APP_TASK_PREFIX = [
        'sap-sync-ci4' => 'sap',
        'yp_npd'       => 'npd',
        'sap-get-ci3'  => 'ypsap',
    ];

    /**
     * Grup koneksi Database.php yang dipakai tiap aplikasi (NULL = bukan cek DB).
     */
    private const APP_DB_GROUP = [
        'sap-sync-ci4' => null,
        'yp_npd'       => 'npd',
        'sap-get-ci3'  => 'sap',
    ];

    public function run()
    {
        $tables = [
            ['table_code' => 'sap_material_master', 'table_name' => 'Material Master', 'suffix' => 'material', 'sort_order' => 10],
            ['table_code' => 'sap_customer_master', 'table_name' => 'Customer Master', 'suffix' => 'customer', 'sort_order' => 20],
            ['table_code' => 'sap_customer_material', 'table_name' => 'Customer Material', 'suffix' => 'customer_material', 'sort_order' => 30],
            ['table_code' => 'sap_customer_sales_area', 'table_name' => 'Customer Sales Area', 'suffix' => 'customer_sales_area', 'sort_order' => 40],
        ];

        $apps = $this->db
            ->table('monitoring_apps')
            ->whereIn('app_code', array_keys(self::APP_TASK_PREFIX))
            ->get()
            ->getResultArray();

        foreach ($apps as $app) {
            $appCode    = $app['app_code'];
            $taskPrefix = self::APP_TASK_PREFIX[$appCode];
            $dbGroup    = self::APP_DB_GROUP[$appCode];

            foreach ($tables as $table) {
                $data = $table + [
                    'monitoring_app_id' => (int) $app['id'],
                    'is_active'         => 1,
                ];
                unset($data['suffix']);

                // Task code: sap-sync-ci4 memakai kode task fetch lama, aplikasi
                // lain memakai kode riwayat cek (npd_*, ypsap_*).
                $data['sync_task_code'] = $appCode === 'sap-sync-ci4'
                    ? 'sap_' . $table['suffix']
                    : $taskPrefix . '_' . $table['suffix'];

                // Descriptor sumber baca: rujuk grup koneksi, bukan URL fisik.
                $data['api_resource'] = $dbGroup !== null
                    ? 'db://' . $dbGroup
                    : '/api/monitoring/tables/' . $table['table_code'];

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
