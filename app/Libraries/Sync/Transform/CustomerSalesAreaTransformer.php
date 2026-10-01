<?php

namespace App\Libraries\Sync\Transform;

class CustomerSalesAreaTransformer extends AbstractTransformer
{
    protected function mapping(array $task): array
    {
        return [
            'customer_code'        => ['customer_code', 'Customer', 'customer', 'CustomerCode'],
            'sales_organization'   => ['sales_organization', 'SalesOrganization', 'salesOrganization', 'SalesOrg'],
            'distribution_channel' => ['distribution_channel', 'DistributionChannel', 'distributionChannel', 'DistChannel'],
            'division'             => ['division', 'Division'],
            'sales_office'         => ['sales_office', 'SalesOffice', 'salesOffice'],
            'currency'             => ['currency', 'Currency'],
            'payment_terms'        => ['payment_terms', 'PaymentTerms', 'paymentTerms', 'TermsOfPayment'],
        ];
    }

    protected function primaryKey(array $task): array
    {
        return ['customer_code', 'sales_organization', 'distribution_channel', 'division'];
    }

    protected function defaults(array $task): array
    {
        return ['currency' => 'IDR'];
    }

    protected function lengths(): array
    {
        return [
            'customer_code'        => 40,
            'sales_organization'   => 20,
            'distribution_channel' => 10,
            'division'             => 10,
            'sales_office'         => 20,
            'currency'             => 5,
            'payment_terms'        => 20,
        ];
    }
}
