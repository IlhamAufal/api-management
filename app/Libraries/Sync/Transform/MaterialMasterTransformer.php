<?php

namespace App\Libraries\Sync\Transform;

class MaterialMasterTransformer extends AbstractTransformer
{
    protected function mapping(array $task): array
    {
        return [
            'material_number'      => ['material_number', 'Product', 'product', 'Material', 'material', 'MATNR'],
            'material_type'        => ['material_type', 'ProductType', 'productType', 'MaterialType', 'type'],
            'material_description' => ['material_description', 'MaterialDescription', 'ProductDescription', 'description', 'Description', 'to_Description.MaterialDescription'],
            'base_unit'            => ['base_unit', 'BaseUnit', 'ProductBaseUnit', 'baseUnit', 'Unit', 'UOM', 'unit'],
            'material_group'       => ['material_group', 'ProductGroup', 'materialGroup', 'MaterialGroup', 'group'],
            'gross_weight'         => ['gross_weight', 'GrossWeight', 'grossWeight'],
            'net_weight'           => ['net_weight', 'NetWeight', 'netWeight'],
            'weight_unit'          => ['weight_unit', 'WeightUnit', 'weightUnit'],
            'standard_price'       => ['standard_price', 'StandardPrice', 'standardPrice', 'Price', 'price'],
            'currency'             => ['currency', 'Currency', 'currencyCode'],
        ];
    }

    protected function primaryKey(array $task): array
    {
        return ['material_number'];
    }

    protected function defaults(array $task): array
    {
        return ['currency' => 'IDR'];
    }

    protected function decimals(): array
    {
        return [
            'gross_weight'   => 3,
            'net_weight'     => 3,
            'standard_price' => 2,
        ];
    }

    protected function lengths(): array
    {
        return [
            'material_number'      => 40,
            'material_type'        => 10,
            'material_description' => 255,
            'base_unit'            => 10,
            'material_group'       => 20,
            'weight_unit'          => 10,
            'currency'             => 5,
        ];
    }
}
