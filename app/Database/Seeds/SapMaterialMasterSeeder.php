<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SapMaterialMasterSeeder extends Seeder
{
    public function run()
    {
        $materials = [
            [
                'material_number'      => 'MAT-100001',
                'material_type'        => 'FERT',
                'material_description' => 'Produk Jadi Reguler 500 ml',
                'base_unit'            => 'EA',
                'material_group'       => 'FG-REG',
                'gross_weight'         => '0.650',
                'net_weight'           => '0.500',
                'weight_unit'          => 'KG',
                'standard_price'       => '12500.00',
                'currency'             => 'IDR',
                'raw_payload'          => json_encode([
                    'source' => 'seed',
                    'material_number' => 'MAT-100001',
                    'description' => 'Produk Jadi Reguler 500 ml',
                ]),
            ],
            [
                'material_number'      => 'MAT-100002',
                'material_type'        => 'FERT',
                'material_description' => 'Produk Premium 1 Liter',
                'base_unit'            => 'EA',
                'material_group'       => 'FG-PRM',
                'gross_weight'         => '1.200',
                'net_weight'           => '1.000',
                'weight_unit'          => 'KG',
                'standard_price'       => '28500.00',
                'currency'             => 'IDR',
                'raw_payload'          => json_encode([
                    'source' => 'seed',
                    'material_number' => 'MAT-100002',
                    'description' => 'Produk Premium 1 Liter',
                ]),
            ],
            [
                'material_number'      => 'MAT-200001',
                'material_type'        => 'ROH',
                'material_description' => 'Bahan Baku Konsentrat',
                'base_unit'            => 'KG',
                'material_group'       => 'RM-RAW',
                'gross_weight'         => '25.500',
                'net_weight'           => '25.000',
                'weight_unit'          => 'KG',
                'standard_price'       => '18000.00',
                'currency'             => 'IDR',
                'raw_payload'          => json_encode([
                    'source' => 'seed',
                    'material_number' => 'MAT-200001',
                    'description' => 'Bahan Baku Konsentrat',
                ]),
            ],
        ];

        foreach ($materials as $material) {
            $builder = $this->db->table('sap_material_master');
            $existing = $builder->where('material_number', $material['material_number'])->get()->getRowArray();

            if ($existing === null) {
                $builder->insert($material);
                continue;
            }

            $builder->where('id', $existing['id'])->update($material);
        }
    }
}
