<?php

use App\Libraries\Sync\Transform\TransformerRegistry;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit test logika transform source row -> baris tabel target.
 *
 * @internal
 */
final class TransformersTest extends CIUnitTestCase
{
    private function task(string $targetTable): array
    {
        return [
            'task_code'    => 'task_under_test',
            'target_table' => $targetTable,
            'batch_size'   => 1500,
        ];
    }

    public function testMaterialMasterMapsSapStylePayload()
    {
        $result = (new TransformerRegistry())->forTask($this->task('sap_material_master'))
            ->transform([
                [
                    'Product'           => 'P-100',
                    'ProductType'       => 'FERT',
                    'BaseUnit'          => 'EA',
                    'ProductGroup'      => 'GROUP1',
                    'GrossWeight'       => '1.5555',
                    'NetWeight'         => '1.2',
                    'WeightUnit'        => 'KG',
                    'Price'             => '1500.999',
                    'Currency'          => 'idr',
                    'to_Description'    => ['MaterialDescription' => 'Widget A'],
                ],
            ], $this->task('sap_material_master'));

        $this->assertSame(0, $result->skipped());
        $this->assertCount(1, $result->rows());

        $row = $result->rows()[0];
        $this->assertSame('P-100', $row['material_number']);
        $this->assertSame('FERT', $row['material_type']);
        $this->assertSame('Widget A', $row['material_description']);
        $this->assertSame('EA', $row['base_unit']);
        $this->assertSame(1.556, $row['gross_weight']);
        $this->assertSame(1.2, $row['net_weight']);
        $this->assertSame(1501.0, $row['standard_price']);
        $this->assertSame('IDR', $row['currency']);
        $this->assertStringContainsString('P-100', $row['raw_payload']);
    }

    public function testMaterialMasterSkipsRowsWithoutPrimaryKey()
    {
        $result = (new TransformerRegistry())->forTask($this->task('sap_material_master'))
            ->transform([
                ['ProductType' => 'FERT'],
                ['Product' => 'P-2'],
            ], $this->task('sap_material_master'));

        $this->assertSame(1, $result->skipped());
        $this->assertCount(1, $result->rows());
        $this->assertNotEmpty($result->errors());
        $this->assertStringContainsString('material_number', $result->errors()[0]);
    }

    public function testCustomerMasterAppliesDefaultsAndNormalizesActiveFlag()
    {
        $task = $this->task('sap_customer_master');
        $result = (new TransformerRegistry())->forTask($task)
            ->transform([
                ['Customer' => 'C-1', 'CustomerName' => 'PT Maju', 'IsActive' => 'X'],
                ['Customer' => 'C-2', 'CustomerName' => 'PT Mundur', 'IsActive' => '0'],
            ], $task);

        $this->assertSame(0, $result->skipped());

        [$first, $second] = $result->rows();
        $this->assertSame('ID', $first['country']);
        $this->assertSame(1, $first['is_active']);
        $this->assertSame(0, $second['is_active']);
    }

    public function testCustomerNameIsTruncatedToColumnLength()
    {
        $task = $this->task('sap_customer_master');
        $result = (new TransformerRegistry())->forTask($task)
            ->transform([
                ['Customer' => 'C-1', 'CustomerName' => str_repeat('x', 300)],
            ], $task);

        $this->assertSame(255, mb_strlen($result->rows()[0]['customer_name']));
    }

    public function testCustomerMaterialRequiresBothPrimaryKeys()
    {
        $task = $this->task('sap_customer_material');
        $result = (new TransformerRegistry())->forTask($task)
            ->transform([
                ['Customer' => 'C-1', 'Material' => 'M-1', 'CustomerMaterial' => 'CM-1'],
                ['Customer' => 'C-1'],
            ], $task);

        $this->assertSame(1, $result->skipped());
        $this->assertSame('CM-1', $result->rows()[0]['customer_material_number']);
    }

    public function testCustomerSalesAreaMapsCompositeKey()
    {
        $task = $this->task('sap_customer_sales_area');
        $result = (new TransformerRegistry())->forTask($task)
            ->transform([
                [
                    'Customer'            => 'C-1',
                    'SalesOrganization'   => 'OR01',
                    'DistributionChannel' => '01',
                    'Division'            => '00',
                    'PaymentTerms'        => '0001',
                ],
            ], $task);

        $this->assertSame(0, $result->skipped());

        $row = $result->rows()[0];
        $this->assertSame('C-1', $row['customer_code']);
        $this->assertSame('OR01', $row['sales_organization']);
        $this->assertSame('01', $row['distribution_channel']);
        $this->assertSame('00', $row['division']);
        $this->assertSame('IDR', $row['currency']);
        $this->assertSame('0001', $row['payment_terms']);
    }

    public function testPassthroughCopiesKnownColumnsAndRejectsUnknownTable()
    {
        $task = $this->task('sap_material_master');
        $result = (new TransformerRegistry())->forTask($task)
            ->transform([['material_number' => 'M-1', 'material_group' => 'G1', 'unknown_col' => 'x']], $task);

        $row = $result->rows()[0];
        $this->assertSame('M-1', $row['material_number']);
        $this->assertSame('G1', $row['material_group']);
        $this->assertArrayNotHasKey('unknown_col', $row);

        $this->expectException(RuntimeException::class);
        (new TransformerRegistry())->forTask($this->task('not_a_table'))->transform([], $this->task('not_a_table'));
    }
}
