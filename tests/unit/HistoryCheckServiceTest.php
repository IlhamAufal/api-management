<?php

use App\Libraries\Sync\HistoryCheckService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit test logika murni HistoryCheckService (tanpa koneksi DB remote).
 *
 * @internal
 */
final class HistoryCheckServiceTest extends CIUnitTestCase
{
    private function stat(?int $rows, ?string $updated = null, ?string $error = null): array
    {
        return ['rows' => $rows, 'last_updated' => $updated, 'error' => $error];
    }

    public function testSourceForAppMapsKnownAppsOnly()
    {
        $service = new HistoryCheckService();

        $this->assertSame('npd', $service->sourceForApp('yp_npd'));
        $this->assertSame('sap', $service->sourceForApp('sap-get-ci3'));
        $this->assertNull($service->sourceForApp('sap-sync-ci4'));
        $this->assertNull($service->sourceForApp('tidak-ada'));
    }

    public function testStaticSourceForAppCodeMatchesInstance()
    {
        $this->assertSame('npd', HistoryCheckService::sourceForAppCode('yp_npd'));
        $this->assertNull(HistoryCheckService::sourceForAppCode('sap-sync-ci4'));
    }

    public function testIsConfiguredFalseForNullOrEmptyCredentials()
    {
        $service = new HistoryCheckService();

        $this->assertFalse($service->isConfigured(null));
        $this->assertFalse($service->isConfigured('tidak-ada-grup'));
    }

    public function testRowStatusMatchWhenCountsEqual()
    {
        $this->assertSame('MATCH', HistoryCheckService::rowStatus($this->stat(100), $this->stat(100)));
    }

    public function testRowStatusGapWhenCountsDiffer()
    {
        $this->assertSame('GAP', HistoryCheckService::rowStatus($this->stat(100), $this->stat(80)));
    }

    public function testRowStatusErrorWhenAnySideFailed()
    {
        $this->assertSame('ERROR', HistoryCheckService::rowStatus($this->stat(null, null, 'boom'), $this->stat(100)));
        $this->assertSame('ERROR', HistoryCheckService::rowStatus($this->stat(100), $this->stat(null, null, 'boom')));
    }

    public function testRowStatusUnknownWhenNoNumbers()
    {
        $this->assertSame('UNKNOWN', HistoryCheckService::rowStatus($this->stat(null), $this->stat(100)));
    }

    public function testMergeComparisonComputesDeltaAndStatusPerTable()
    {
        $npd = [
            'sap_material_master'     => $this->stat(29081, '2026-10-01 07:00:00'),
            'sap_customer_master'     => $this->stat(1502),
            'sap_customer_material'   => $this->stat(1804),
            'sap_customer_sales_area' => $this->stat(33632),
        ];
        $sap = [
            'sap_material_master'     => $this->stat(29081, '2026-10-01 14:00:00'),
            'sap_customer_master'     => $this->stat(1502),
            'sap_customer_material'   => $this->stat(1804),
            'sap_customer_sales_area' => $this->stat(8373),
        ];

        $rows = HistoryCheckService::mergeComparison($npd, $sap);

        $this->assertCount(4, $rows);

        $byCode = [];
        foreach ($rows as $row) {
            $byCode[$row['table_code']] = $row;
        }

        $this->assertSame('MATCH', $byCode['sap_material_master']['status']);
        $this->assertSame(0, $byCode['sap_material_master']['delta']);

        $this->assertSame('GAP', $byCode['sap_customer_sales_area']['status']);
        $this->assertSame(25259, $byCode['sap_customer_sales_area']['delta']);
        $this->assertSame(29081, $byCode['sap_material_master']['npd_rows']);
        $this->assertSame(8373, $byCode['sap_customer_sales_area']['sap_rows']);
    }

    public function testMergeComparisonHandlesMissingSideAsError()
    {
        $npd = [
            'sap_material_master'     => $this->stat(10),
            'sap_customer_master'     => $this->stat(10),
            'sap_customer_material'   => $this->stat(10),
            'sap_customer_sales_area' => $this->stat(10),
        ];

        $rows = HistoryCheckService::mergeComparison($npd, []);

        foreach ($rows as $row) {
            $this->assertSame('ERROR', $row['status']);
            $this->assertNull($row['delta']);
        }
    }

    public function testFetchStatsReturnsAllWhitelistedTablesWithErrorWhenSourceNull()
    {
        $stats = (new HistoryCheckService())->fetchStats(null);

        $this->assertCount(4, $stats);
        $this->assertArrayHasKey('sap_material_master', $stats);
        foreach ($stats as $row) {
            $this->assertNull($row['rows']);
            $this->assertNotNull($row['error']);
        }
    }

    public function testRunCheckRejectsUnsupportedApp()
    {
        $result = (new HistoryCheckService())->runCheck('sap-sync-ci4');

        $this->assertSame('FAILED', $result['status']);
        $this->assertSame(0, $result['logs_written']);
        $this->assertSame([], $result['results']);
    }
}
