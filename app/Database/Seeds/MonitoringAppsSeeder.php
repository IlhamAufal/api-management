<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MonitoringAppsSeeder extends Seeder
{
    public function run()
    {
        $apps = [
            [
                'app_code'      => 'sap-sync-ci4',
                'app_name'      => 'SAP Sync CI4',
                'description'   => 'Mengambil master data dari SAP S/4HANA dan menyimpannya ke yp_so.',
                'base_url'      => 'http://10.16.248.188',
                'database_name' => 'yp_so',
                'is_active'     => 1,
                'sort_order'    => 10,
            ],
            [
                'app_code'      => 'yp_npd',
                'app_name'      => 'YP NPD',
                'description'   => 'Aplikasi NPD yang menggunakan master data SAP sebelum proses pengiriman.',
                'base_url'      => null,
                'database_name' => 'yp_npd',
                'is_active'     => 1,
                'sort_order'    => 20,
            ],
            [
                'app_code'      => 'sap-get-ci3',
                'app_name'      => 'SAP Get CI3',
                'description'   => 'Penerima akhir master data SAP yang menyimpan data ke yp_sap.',
                'base_url'      => 'http://g2sp.yupindo.com',
                'database_name' => 'yp_sap',
                'is_active'     => 1,
                'sort_order'    => 30,
            ],
        ];

        foreach ($apps as $app) {
            $builder = $this->db->table('monitoring_apps');
            $existing = $builder->where('app_code', $app['app_code'])->get()->getRowArray();

            if ($existing === null) {
                $builder->insert($app);
                continue;
            }

            $builder->where('id', $existing['id'])->update($app);
        }
    }
}
