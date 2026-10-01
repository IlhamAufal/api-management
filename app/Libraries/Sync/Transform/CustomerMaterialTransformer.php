<?php

namespace App\Libraries\Sync\Transform;

class CustomerMaterialTransformer extends AbstractTransformer
{
    protected function mapping(array $task): array
    {
        return [
            'customer_code'             => ['customer_code', 'Customer', 'customer', 'CustomerCode'],
            'material_number'           => ['material_number', 'Material', 'material', 'Product', 'product'],
            'customer_material_number'  => ['customer_material_number', 'CustomerMaterial', 'customerMaterial', 'CustMatNumber'],
            'customer_material_desc'    => ['customer_material_desc', 'ProductDescription', 'CustomerMaterialName', 'MaterialName', 'description', 'Description'],
        ];
    }

    protected function primaryKey(array $task): array
    {
        return ['customer_code', 'material_number'];
    }

    protected function lengths(): array
    {
        return [
            'customer_code'            => 40,
            'material_number'          => 40,
            'customer_material_number' => 100,
            'customer_material_desc'   => 255,
        ];
    }
}
