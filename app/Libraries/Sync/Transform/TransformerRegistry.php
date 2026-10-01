<?php

namespace App\Libraries\Sync\Transform;

class TransformerRegistry
{
    private const MAP = [
        'sap_material_master'      => MaterialMasterTransformer::class,
        'sap_customer_master'      => CustomerMasterTransformer::class,
        'sap_customer_material'    => CustomerMaterialTransformer::class,
        'sap_customer_sales_area'  => CustomerSalesAreaTransformer::class,
    ];

    public function forTask(array $task): TransformerInterface
    {
        $table = (string) ($task['target_table'] ?? '');
        $class = self::MAP[$table] ?? PassthroughTransformer::class;

        return new $class();
    }
}
