<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SapCustomerMasterSeeder extends Seeder
{
    public function run()
    {
        $customers = [
            [
                'customer_code'          => 'CUST-100001',
                'customer_name'          => 'PT Nusantara Distribusi',
                'business_partner_group' => 'DISTRIBUTOR',
                'address_line'           => 'Jl. Gatot Subroto No. 88',
                'city'                   => 'Jakarta Selatan',
                'postal_code'            => '12950',
                'country'                => 'ID',
                'tax_number'             => '01.234.567.8-091.000',
                'is_active'              => 1,
                'raw_payload'            => json_encode([
                    'source' => 'seed',
                    'customer_code' => 'CUST-100001',
                    'customer_name' => 'PT Nusantara Distribusi',
                ]),
            ],
            [
                'customer_code'          => 'CUST-100002',
                'customer_name'          => 'CV Mitra Niaga Sejahtera',
                'business_partner_group' => 'WHOLESALE',
                'address_line'           => 'Jl. Ahmad Yani No. 120',
                'city'                   => 'Bandung',
                'postal_code'            => '40281',
                'country'                => 'ID',
                'tax_number'             => '02.345.678.9-428.000',
                'is_active'              => 1,
                'raw_payload'            => json_encode([
                    'source' => 'seed',
                    'customer_code' => 'CUST-100002',
                    'customer_name' => 'CV Mitra Niaga Sejahtera',
                ]),
            ],
            [
                'customer_code'          => 'CUST-100003',
                'customer_name'          => 'PT Sentosa Retail Indonesia',
                'business_partner_group' => 'RETAIL',
                'address_line'           => 'Jl. Pemuda No. 15',
                'city'                   => 'Surabaya',
                'postal_code'            => '60271',
                'country'                => 'ID',
                'tax_number'             => '03.456.789.0-609.000',
                'is_active'              => 1,
                'raw_payload'            => json_encode([
                    'source' => 'seed',
                    'customer_code' => 'CUST-100003',
                    'customer_name' => 'PT Sentosa Retail Indonesia',
                ]),
            ],
        ];

        foreach ($customers as $customer) {
            $builder = $this->db->table('sap_customer_master');
            $existing = $builder->where('customer_code', $customer['customer_code'])->get()->getRowArray();

            if ($existing === null) {
                $builder->insert($customer);
                continue;
            }

            $builder->where('id', $existing['id'])->update($customer);
        }
    }
}
