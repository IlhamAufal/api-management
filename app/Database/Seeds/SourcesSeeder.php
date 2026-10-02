<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Registry source database read-only. `code` harus sama dengan
 * connection group di Config/Database.php (dan database.*.di .env).
 */
class SourcesSeeder extends Seeder
{
    private const SOURCES = [
        ['code' => 'npd', 'label' => 'YP NPD (yp_npd)'],
        ['code' => 'sap', 'label' => 'YP SAP (yp_sap)'],
    ];

    public function run()
    {
        foreach (self::SOURCES as $source) {
            $builder = $this->db->table('sources');
            $existing = $builder->where('code', $source['code'])->get()->getRowArray();

            if ($existing === null) {
                $builder->insert($source + ['is_active' => 1]);
                continue;
            }

            $builder->where('id', $existing['id'])->update($source);
        }
    }
}
