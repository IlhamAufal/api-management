<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SapCustomerMaterialSeeder extends Seeder
{
    public function run()
    {
        $mappings = [
            [
                'customer_code'            => 'CUST-100001',
                'material_number'          => 'MAT-100001',
                'customer_material_number' => 'ND-REG-500',
                'customer_material_desc'   => 'Produk Reguler 500 ml - Nusantara',
                'raw_payload'              => json_encode([
                    'source' => 'seed',
                    'customer_code' => 'CUST-100001',
                    'material_number' => 'MAT-100001',
                ]),
            ],
            [
                'customer_code'            => 'CUST-100001',
                'material_number'          => 'MAT-100002',
                'customer_material_number' => 'ND-PRM-1000',
                'customer_material_desc'   => 'Produk Premium 1 Liter - Nusantara',
                'raw_payload'              => json_encode([
                    'source' => 'seed',
                    'customer_code' => 'CUST-100001',
                    'material_number' => 'MAT-100002',
                ]),
            ],
            [
                'customer_code'            => 'CUST-100002',
                'material_number'          => 'MAT-100001',
                'customer_material_number' => 'MNS-R500',
                'customer_material_desc'   => 'Regular 500 ml',
                'raw_payload'              => json_encode([
                    'source' => 'seed',
                    'customer_code' => 'CUST-100002',
                    'material_number' => 'MAT-100001',
                ]),
            ],
            [
                'customer_code'            => 'CUST-100003',
                'material_number'          => 'MAT-100002',
                'customer_material_number' => 'SRI-PP1L',
                'customer_material_desc'   => 'Premium Product 1 Liter',
                'raw_payload'              => json_encode([
                    'source' => 'seed',
                    'customer_code' => 'CUST-100003',
                    'material_number' => 'MAT-100002',
                ]),
            ],
        ];

        foreach ($mappings as $mapping) {
            $builder = $this->db->table('sap_customer_material');
            $existing = $builder
                ->where('customer_code', $mapping['customer_code'])
                ->where('material_number', $mapping['material_number'])
                ->get()
                ->getRowArray();

            if ($existing === null) {
                $builder->insert($mapping);
                continue;
            }

            $builder->where('id', $existing['id'])->update($mapping);
        }
    }
}
