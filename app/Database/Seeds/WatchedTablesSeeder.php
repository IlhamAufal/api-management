<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * 4 tabel master SAP yang sebelumnya dipantau, kini lewat model
 * watched_tables (lepas dari source). sync_column disesuaikan per tabel
 * hasil introspeksi source (plan amendmen #5): dua tabel punya
 * `updated_at`, dua lagi hanya `synced_at`.
 * stale_after_minutes = 1440 (24 jam, amendmen #4).
 */
class WatchedTablesSeeder extends Seeder
{
    private const TABLES = [
        ['table_name' => 'sap_material_master', 'label' => 'Material Master', 'sync_column' => 'updated_at'],
        ['table_name' => 'sap_customer_master', 'label' => 'Customer Master', 'sync_column' => 'updated_at'],
        ['table_name' => 'sap_customer_material', 'label' => 'Customer Material', 'sync_column' => 'synced_at'],
        ['table_name' => 'sap_customer_sales_area', 'label' => 'Customer Sales Area', 'sync_column' => 'synced_at'],
    ];

    public function run()
    {
        foreach (self::TABLES as $table) {
            $row = $table + [
                'stale_after_minutes' => 1440,
                'is_active'           => 1,
            ];

            $builder = $this->db->table('watched_tables');
            $existing = $builder->where('table_name', $row['table_name'])->get()->getRowArray();

            if ($existing === null) {
                $builder->insert($row);
                continue;
            }

            $builder->where('id', $existing['id'])->update($row);
        }
    }
}
