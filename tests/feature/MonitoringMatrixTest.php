<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\MonitoringFixtureTrait;

/**
 * Feature test matrix monitoring + execution logs:
 * empty state, render sel per status, halaman riwayat/check-all,
 * dan nonaktifnya route pipeline lama.
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
    // Render matrix
    // ------------------------------------------------------------------

    public function testMatrixShowsEmptyStateWhenNoWatchedTables()
    {
        $result = $this->withSession($this->authSession())->get('monitoring');

        $result->assertOK();
        $this->assertBodySee('Belum ada watched table aktif', $result);
    }

    public function testMatrixRendersSingleSourceColumnWithStatusBadge()
    {
        $this->deleteSource('sap'); // sisa npd saja -> matrix 1 kolom source
        $npd     = $this->sourceId('npd');
        $tableId = $this->insertWatchedTable(['label' => 'Pesanan FX']);
        $this->insertSnapshot([
            'watched_table_id' => $tableId,
            'source_id'        => $npd,
            'status'           => 'OK',
        ]);

        $result = $this->withSession($this->authSession())->get('monitoring');

        $result->assertOK();
        $this->assertBodySee('Pesanan FX', $result);
        $this->assertBodySee('fx_orders', $result);
        $this->assertBodySee('NPD (RDS)', $result); // header kolom source
        $this->assertStatusBadge($result, 'OK');
        $this->assertBodyNotSee('SAP', $result); // kolom SAP tidak ikut tampil
    }

    public function testMatrixRendersStaleAndConnErrorBadges()
    {
        $npd     = $this->sourceId('npd');
        $staleId = $this->insertWatchedTable([
            'table_name' => 'fx_orders',
            'label'      => 'Tabel STALE',
        ]);
        $errorId = $this->insertWatchedTable([
            'table_name' => 'fx_ngawur',
            'label'      => 'Tabel Error',
        ]);
        $this->insertSnapshot([
            'watched_table_id' => $staleId,
            'source_id'        => $npd,
            'status'           => 'STALE',
        ]);
        $this->insertSnapshot([
            'watched_table_id' => $errorId,
            'source_id'        => $npd,
            'status'           => 'CONN_ERROR',
            'row_count'        => null,
            'last_synced_at'   => null,
            'error_message'    => 'Gagal koneksi ke host npd',
        ]);

        $result = $this->withSession($this->authSession())->get('monitoring');

        $result->assertOK();
        $this->assertBodySee('Tabel STALE', $result);
        $this->assertBodySee('Tabel Error', $result);
        $this->assertStatusBadge($result, 'STALE');
        $this->assertStatusBadge($result, 'CONN_ERROR');
        $this->assertBodySee('Gagal koneksi ke host npd', $result);
    }

    public function testMatrixCellWithoutSnapshotShowsNeverSynced()
    {
        $this->insertWatchedTable();

        $result = $this->withSession($this->authSession())->get('monitoring');

        $result->assertOK();
        $this->assertStatusBadge($result, 'NEVER_SYNCED');
        $this->assertBodySee('belum pernah dicek', $result);
    }

    // ------------------------------------------------------------------
    // Check All / Check Now
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

        // Persist sungguhan (SQLite) terisi untuk 2 sel tersebut.
        $this->assertSame(2, $this->countRows('table_snapshots'));
        $this->assertSame(2, $this->countRows('check_history'));
    }

    public function testCheckTableRejectsInactiveTable()
    {
        $id = $this->insertWatchedTable(['is_active' => 0]);

        $result = $this->withSession($this->authSession())
            ->post('monitoring/check-table/' . $id);

        $result->assertRedirect();
        $result->assertSessionHas('flash_error');
        $this->assertSame(0, $this->countRows('check_history'));
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
