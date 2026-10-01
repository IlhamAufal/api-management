<?php

use App\Libraries\Sync\UpsertWriter;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit test pembangkit SQL upsert (tanpa database).
 *
 * @internal
 */
final class UpsertWriterTest extends CIUnitTestCase
{
    public function testBuildUpsertProducesInsertOnDuplicateKeyUpdate()
    {
        $columns = ['material_number', 'material_group', 'raw_payload'];
        $rows = [
            ['material_number' => 'M-1', 'material_group' => 'G1', 'raw_payload' => '{"a":1}'],
            ['material_number' => 'M-2', 'material_group' => 'G2', 'raw_payload' => '{"b":2}'],
        ];

        [$sql, $binds] = UpsertWriter::buildUpsert('sap_material_master', $columns, $rows);

        $this->assertStringContainsString('INSERT INTO `sap_material_master`', $sql);
        $this->assertStringContainsString('(`material_number`, `material_group`, `raw_payload`)', $sql);
        $this->assertStringContainsString('ON DUPLICATE KEY UPDATE', $sql);
        $this->assertStringContainsString('`material_number` = VALUES(`material_number`)', $sql);
        // created_at / updated_at dikelola database (default & ON UPDATE).
        $this->assertStringNotContainsString('created_at', $sql);
        $this->assertStringNotContainsString('updated_at', $sql);

        $this->assertSame(['M-1', 'G1', '{"a":1}', 'M-2', 'G2', '{"b":2}'], $binds);
    }

    public function testColumnsForReturnsWhitelistOrNull()
    {
        $this->assertContains('customer_code', UpsertWriter::columnsFor('sap_customer_master'));
        $this->assertNull(UpsertWriter::columnsFor('sys_sync_logs'));
    }

    public function testPrimaryKeyForMatchesUniqueKeys()
    {
        $this->assertSame(['material_number'], UpsertWriter::primaryKeyFor('sap_material_master'));
        $this->assertSame(['customer_code'], UpsertWriter::primaryKeyFor('sap_customer_master'));
        $this->assertSame(['customer_code', 'material_number'], UpsertWriter::primaryKeyFor('sap_customer_material'));
        $this->assertSame(
            ['customer_code', 'sales_organization', 'distribution_channel', 'division'],
            UpsertWriter::primaryKeyFor('sap_customer_sales_area')
        );
    }

    public function testBuildUpsertRejectsUnsafeIdentifiers()
    {
        $this->expectException(RuntimeException::class);

        UpsertWriter::buildUpsert('bad-table; DROP', ['material_number'], [['material_number' => 'M-1']]);
    }
}
