<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(SapMaterialMasterSeeder::class);
        $this->call(SapCustomerMasterSeeder::class);
        $this->call(SapCustomerMaterialSeeder::class);
        $this->call(SapCustomerSalesAreaSeeder::class);
        $this->call(SysSyncLogsSeeder::class);
        $this->call(MonitoringAppsSeeder::class);
        $this->call(MonitoringAppTablesSeeder::class);
    }
}
