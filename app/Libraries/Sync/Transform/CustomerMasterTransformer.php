<?php

namespace App\Libraries\Sync\Transform;

class CustomerMasterTransformer extends AbstractTransformer
{
    protected function mapping(array $task): array
    {
        return [
            'customer_code'           => ['customer_code', 'Customer', 'customer', 'CustomerCode', 'customerCode'],
            'customer_name'           => ['customer_name', 'CustomerName', 'customerName', 'BusinessPartnerName', 'Name', 'name'],
            'business_partner_group'  => ['business_partner_group', 'CustomerBPGroup', 'businessPartnerGroup', 'BPGroup', 'CustomerGroup', 'group'],
            'address_line'            => ['address_line', 'AddressLine', 'addressLine', 'Street', 'street', 'FullAddress'],
            'city'                    => ['city', 'City'],
            'postal_code'             => ['postal_code', 'PostalCode', 'postalCode', 'ZIPCode', 'ZipCode'],
            'country'                 => ['country', 'Country', 'countryCode', 'CountryCode'],
            'tax_number'              => ['tax_number', 'TaxNumber', 'taxNumber', 'TaxNumber1', 'BPTaxNumber', 'NPWP', 'npwp'],
            'is_active'               => ['is_active', 'IsActive', 'isActive', 'activeStatus'],
        ];
    }

    protected function primaryKey(array $task): array
    {
        return ['customer_code'];
    }

    protected function defaults(array $task): array
    {
        return [
            'country'   => 'ID',
            'is_active' => 1,
        ];
    }

    protected function lengths(): array
    {
        return [
            'customer_code'          => 40,
            'customer_name'          => 255,
            'business_partner_group' => 20,
            'city'                   => 100,
            'postal_code'            => 20,
            'country'                => 10,
            'tax_number'             => 50,
        ];
    }
}
