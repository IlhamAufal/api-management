<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\MonitoringFixtureTrait;

/**
 * Feature test monitoring per-source (sub-tab navigation, Plan-2 Task 5):
 * tab bar source, daftar tabel per source (worst-first), empty state,
 * check-cell / check-source, dan nonaktifnya route check-table lama.
 *
 * Trait sudah men-seed source `npd` (id 1) dan `sap` (id 2).
 *
 * @internal
 */
final class MonitoringMatrixTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use MonitoringFixtureTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMonitoringFixture();
    }

    // ------------------------------------------------------------------
    // Render halaman per-source
    // ------------------------------------------------------------------

    public function testLandingRendersFirstActiveSourceWithTabBar()
    {
        $this->insertWatchedTable(['label' => 'Pesanan FX']);

        $result = $this->withSession($this->authSession())->get('monitoring');

        $result->assertOK();
        // Tab bar menampilkan kedua source.
        $this->assertBodySee('NPD (RDS)', $result);
        $this->assertBodySee('SAP', $result);
        $this->assertBodySee('monitoring/source/npd', $result);
        $this->assertBodySee('monitoring/source/sap', $result);
        // Landing = source aktif pertama (npd) tanpa redirect.
        $this->assertBodySee('Pesanan FX', $result);
        $this->assertBodySee('Check Sumber Ini', $result);
    }

    public function testEmptyStateWhenNoActiveSources()
    {
        $this->deleteSource('npd');
        $this->deleteSource('sap');

        $result = $this->withSession($this->authSession())->get('monitoring');

        $result->assertOK();
        $this->assertBodySee('Belum ada source aktif', $result);
    }

    public function testSourcePageShowsEmptyStateWhenNoWatchedTables()
    {
        $result = $this->withSession($this->authSession())->get('monitoring/source/npd');

        $result->assertOK();
        $this->assertBodySee('Belum ada watched table aktif', $result);
    }

    public function testSourcePageRendersTableWithStatusBadge()
    {
        $npd     = $this->sourceId('npd');
        $tableId = $this->insertWatchedTable(['label' => 'Pesanan FX']);
        $this->insertSnapshot([
            'watched_table_id' => $tableId,
            'source_id'        => $npd,
            'status'           => 'OK',
            'row_count'        => 1234,
        ]);

        $result = $this->withSession($this->authSession())->get('monitoring/source/npd');

        $result->assertOK();
        $this->assertBodySee('Pesanan FX', $result);
        $this->assertBodySee('fx_orders', $result);
        $this->assertStatusBadge($result, 'OK');
        $this->assertBodySee('1,234', $result);
    }

    public function testSourcePageOnlyShowsSnapshotForThatSource()
    {
        $npd     = $this->sourceId('npd');
        $sap     = $this->sourceId('sap');
        $tableId = $this->insertWatchedTable(['label' => 'Pesanan FX']);
        // STALE di npd, CONN_ERROR di sap.
        $this->insertSnapshot([
            'watched_table_id' => $tableId,
            'source_id'        => $npd,
            'status'           => 'STALE',
        ]);
        $this->insertSnapshot([
            'watched_table_id' => $tableId,
            'source_id'        => $sap,
            'status'           => 'CONN_ERROR',
            'row_count'        => null,
            'last_synced_at'   => null,
            'error_message'    => 'Gagal koneksi ke host sap',
        ]);

        // Halaman npd: hanya STALE, bukan CONN_ERROR.
        $npdPage = $this->withSession($this->authSession())->get('monitoring/source/npd');
        $npdPage->assertOK();
        $this->assertStatusBadge($npdPage, 'STALE');
        $this->assertBodyNotSee('Gagal koneksi ke host sap', $npdPage);

        // Halaman sap: CONN_ERROR + pesan error.
        $sapPage = $this->withSession($this->authSession())->get('monitoring/source/sap');
        $sapPage->assertOK();
        $this->assertStatusBadge($sapPage, 'CONN_ERROR');
        $this->assertBodySee('Gagal koneksi ke host sap', $sapPage);
    }

    public function testSourcePageCellWithoutSnapshotShowsBelumDicek()
    {
        $this->insertWatchedTable();

        $result = $this->withSession($this->authSession())->get('monitoring/source/npd');

        $result->assertOK();
        $this->assertStatusBadge($result, 'NEVER_SYNCED');
        $this->assertBodySee('belum dicek', $result);
    }

    public function testSourcePageRendersPendingConfigBadgeWithProvisioningMessage()
    {
        // Snapshot source tanpa credential (hasil langkah 0 checker Agent A).
        $npd     = $this->sourceId('npd');
        $tableId = $this->insertWatchedTable(['label' => 'Pesanan FX']);
        $this->insertSnapshot([
            'watched_table_id' => $tableId,
            'source_id'        => $npd,
            'status'           => 'PENDING_CONFIG',
            'row_count'        => null,
            'last_synced_at'   => null,
            'error_message'    => 'Harap tambahkan credential terlebih dahulu.',
        ]);

        $result = $this->withSession($this->authSession())->get('monitoring/source/npd');

        $result->assertOK();
        $this->assertStatusBadge($result, 'PENDING_CONFIG');
        $this->assertBodySee('Harap tambahkan credential terlebih dahulu.', $result);
        // Bukan CONN_ERROR — provisioning normal, bukan kerusakan.
        $this->assertDoesNotMatchRegularExpression(
            '/>\s*CONN_ERROR\s*</',
            $result->response()->getBody(),
            'Source PENDING_CONFIG tidak boleh tampil sebagai CONN_ERROR.'
        );
    }

    public function testPendingConfigSortsAboveOkWorstFirst()
    {
        $npd     = $this->sourceId('npd');
        $okId    = $this->insertWatchedTable(['table_name' => 'fx_orders', 'label' => 'AAA Pending']);
        $pendId  = $this->insertWatchedTable(['table_name' => 'fx_lain', 'label' => 'ZZZ Pending']);
        $this->insertSnapshot(['watched_table_id' => $okId, 'source_id' => $npd, 'status' => 'OK']);
        $this->insertSnapshot([
            'watched_table_id' => $pendId,
            'source_id'        => $npd,
            'status'           => 'PENDING_CONFIG',
            'row_count'        => null,
            'last_synced_at'   => null,
        ]);

        $result = $this->withSession($this->authSession())->get('monitoring/source/npd');
        $result->assertOK();

        $body = $result->response()->getBody();
        // Severity: PENDING_CONFIG (5) > OK (1) — ZZZ harus muncul sebelum AAA.
        $this->assertLessThan(
            strpos($body, 'AAA Pending'),
            strpos($body, 'ZZZ Pending'),
            'Baris worst-first: PENDING_CONFIG harus di atas OK.'
        );
    }

    public function testUnknownSourceCodeReturns404()
    {
        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->withSession($this->authSession())->get('monitoring/source/ngaco');
    }

    public function testInactiveSourceReturns404()
    {
        // Nonaktifkan sap -> halaman source-nya 404.
        $this->monitoringDb()->table('sources')->where('code', 'sap')->update(['is_active' => 0]);

        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->withSession($this->authSession())->get('monitoring/source/sap');
    }

    public function testRowsAreSortedWorstFirst()
    {
        $npd    = $this->sourceId('npd');
        $okId   = $this->insertWatchedTable(['table_name' => 'fx_orders', 'label' => 'AAA Ok']);
        $errId  = $this->insertWatchedTable(['table_name' => 'fx_ngawur', 'label' => 'ZZZ Error']);
        $this->insertSnapshot(['watched_table_id' => $okId, 'source_id' => $npd, 'status' => 'OK']);
        $this->insertSnapshot([
            'watched_table_id' => $errId,
            'source_id'        => $npd,
            'status'           => 'CONN_ERROR',
            'row_count'        => null,
            'last_synced_at'   => null,
        ]);

        $result = $this->withSession($this->authSession())->get('monitoring/source/npd');
        $result->assertOK();

        $body = $result->response()->getBody();
        // CONN_ERROR (worst) harus muncul sebelum OK di urutan baris.
        $this->assertLessThan(
            strpos($body, 'AAA Ok'),
            strpos($body, 'ZZZ Error'),
            'Baris worst-first: CONN_ERROR harus di atas OK.'
        );
    }

    // ------------------------------------------------------------------
    // Check All / Check Cell / Check Source
    // ------------------------------------------------------------------

    public function testCheckAllReturnsJsonSummaryAndSkipsInactiveTables()
    {
        $npd       = $this->sourceId('npd');
        $sap       = $this->sourceId('sap');
        $activeId  = $this->insertWatchedTable(['table_name' => 'fx_orders']);
        $inactiveId = $this->insertWatchedTable([
            'table_name' => 'fx_ngawur',
            'label'      => 'Tabel Nonaktif',
            'is_active'  => 0,
        ]);

        $result = $this->withSession($this->authSession())
            ->withHeaders(['Accept' => 'application/json'])
            ->post('monitoring/check-all');

        $result->assertOK();
        $result->assertJSONFragment(['status' => 'SUCCESS']);
        $this->assertBodySee('2 sel', $result); // 1 tabel aktif × 2 source fixture

        $checker = \Config\Services::freshnessChecker();
        $this->assertSame(
            [$activeId . ':' . $npd, $activeId . ':' . $sap],
            $checker->checkedPairs
        );
        $this->assertNotContains($inactiveId . ':' . $npd, $checker->checkedPairs);

        $this->assertSame(2, $this->countRows('table_snapshots'));
        $this->assertSame(2, $this->countRows('check_history'));
    }

    public function testCheckCellChecksOnlyOnePairAndRedirectsToSource()
    {
        $npd = $this->sourceId('npd');
        $id  = $this->insertWatchedTable(['table_name' => 'fx_orders', 'label' => 'Pesanan FX']);

        $result = $this->withSession($this->authSession())
            ->post('monitoring/check-cell/' . $id . '/' . $npd);

        $result->assertRedirectTo(base_url('monitoring/source/npd'));
        $result->assertSessionHas('flash_success');

        // Tepat 1 sel ter-check.
        $this->assertSame(1, $this->countRows('table_snapshots'));
        $this->assertSame(1, $this->countRows('check_history'));

        $checker = \Config\Services::freshnessChecker();
        $this->assertSame([$id . ':' . $npd], $checker->checkedPairs);
    }

    public function testCheckCellRejectsInactiveTable()
    {
        $npd = $this->sourceId('npd');
        $id  = $this->insertWatchedTable(['is_active' => 0]);

        $result = $this->withSession($this->authSession())
            ->post('monitoring/check-cell/' . $id . '/' . $npd);

        $result->assertRedirect();
        $result->assertSessionHas('flash_error');
        $this->assertSame(0, $this->countRows('check_history'));
    }

    public function testCheckSourceChecksAllActiveTablesForThatSourceOnly()
    {
        $npd      = $this->sourceId('npd');
        $sap      = $this->sourceId('sap');
        $tableId  = $this->insertWatchedTable(['table_name' => 'fx_orders']);
        $table2   = $this->insertWatchedTable(['table_name' => 'fx_lain', 'label' => 'Tabel Lain']);

        $result = $this->withSession($this->authSession())
            ->post('monitoring/check-source/npd');

        $result->assertRedirectTo(base_url('monitoring/source/npd'));
        $result->assertSessionHas('flash_success');

        $checker = \Config\Services::freshnessChecker();
        // Hanya pasangan dengan source npd — sap tidak ikut.
        $this->assertSame(
            [$tableId . ':' . $npd, $table2 . ':' . $npd],
            $checker->checkedPairs
        );
        $this->assertNotContains($tableId . ':' . $sap, $checker->checkedPairs);
    }

    public function testCheckSourceRejectsUnknownSource()
    {
        $result = $this->withSession($this->authSession())
            ->post('monitoring/check-source/ngaco');

        $result->assertRedirect();
        $result->assertSessionHas('flash_error');
        $this->assertSame(0, $this->countRows('check_history'));
    }

    public function testOldCheckTableRouteIsGone()
    {
        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->post('monitoring/check-table/1');
    }

    // ------------------------------------------------------------------
    // Riwayat + logs
    // ------------------------------------------------------------------

    public function testHistoryPageRendersSnapshotAndRows()
    {
        $npd     = $this->sourceId('npd');
        $tableId = $this->insertWatchedTable(['label' => 'Pesanan FX']);
        $this->insertSnapshot([
            'watched_table_id' => $tableId,
            'source_id'        => $npd,
            'status'           => 'OK',
            'row_count'        => 1234,
        ]);
        $this->insertHistory([
            'watched_table_id' => $tableId,
            'source_id'        => $npd,
            'status'           => 'STALE',
            'row_count'        => 1200,
        ]);

        $result = $this->withSession($this->authSession())
            ->get('monitoring/history/' . $tableId . '/' . $npd);

        $result->assertOK();
        $this->assertBodySee('Pesanan FX', $result);
        $this->assertStatusBadge($result, 'OK');     // snapshot terkini
        $this->assertStatusBadge($result, 'STALE');  // baris riwayat
        $this->assertBodySee('MANUAL_UI', $result);
        $this->assertBodySee('1,234', $result);
    }

    public function testHistoryPageForUnknownIdsRedirectsWithError()
    {
        $result = $this->withSession($this->authSession())
            ->get('monitoring/history/999/999');

        $result->assertRedirect();
        $result->assertSessionHas('flash_error');
    }

    public function testLogsPageReadsCheckHistory()
    {
        $sap     = $this->sourceId('sap');
        $tableId = $this->insertWatchedTable(['label' => 'Master Material']);
        $this->insertHistory([
            'watched_table_id' => $tableId,
            'source_id'        => $sap,
            'status'           => 'MISSING_TABLE',
            'error_message'    => 'Tabel "fx_orders" tidak ditemukan di source "sap".',
        ]);

        $result = $this->withSession($this->authSession())->get('logs');

        $result->assertOK();
        $this->assertBodySee('Master Material', $result);
        $this->assertStatusBadge($result, 'MISSING_TABLE');
        $this->assertBodySee('tidak ditemukan', $result);
    }

    public function testLogsPageShowsEmptyStateWithoutHistory()
    {
        $result = $this->withSession($this->authSession())->get('logs');

        $result->assertOK();
        $this->assertBodySee('Belum ada log check', $result);
    }
}
