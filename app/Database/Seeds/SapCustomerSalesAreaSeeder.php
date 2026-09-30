<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SapCustomerSalesAreaSeeder extends Seeder
{
    public function run()
    {
        $salesAreas = [
            [
                'customer_code'        => 'CUST-100001',
                'sales_organization'   => '1000',
                'distribution_channel' => '10',
                'division'             => '00',
                'sales_office'         => 'JKT01',
                'currency'             => 'IDR',
                'payment_terms'        => 'NET30',
                'raw_payload'          => json_encode([
                    'source' => 'seed',
                    'customer_code' => 'CUST-100001',
                    'sales_area' => '1000-10-00',
                ]),
            ],
            [
                'customer_code'        => 'CUST-100002',
                'sales_organization'   => '1000',
                'distribution_channel' => '10',
                'division'             => '00',
                'sales_office'         => 'BDG01',
                'currency'             => 'IDR',
                'payment_terms'        => 'NET14',
                'raw_payload'          => json_encode([
                    'source' => 'seed',
                    'customer_code' => 'CUST-100002',
                    'sales_area' => '1000-10-00',
                ]),
            ],
            [
                'customer_code'        => 'CUST-100003',
                'sales_organization'   => '2000',
                'distribution_channel' => '20',
                'division'             => '00',
                'sales_office'         => 'SBY01',
                'currency'             => 'IDR',
                'payment_terms'        => 'NET45',
                'raw_payload'          => json_encode([
                    'source' => 'seed',
                    'customer_code' => 'CUST-100003',
                    'sales_area' => '2000-20-00',
                ]),
            ],
        ];

        foreach ($salesAreas as $salesArea) {
            $builder = $this->db->table('sap_customer_sales_area');
            $existing = $builder
                ->where('customer_code', $salesArea['customer_code'])
                ->where('sales_organization', $salesArea['sales_organization'])
                ->where('distribution_channel', $salesArea['distribution_channel'])
                ->where('division', $salesArea['division'])
                ->get()
                ->getRowArray();

            if ($existing === null) {
                $builder->insert($salesArea);
                continue;
            }

            $builder->where('id', $existing['id'])->update($salesArea);
        }
    }
}
