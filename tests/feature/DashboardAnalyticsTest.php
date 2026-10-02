<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\MonitoringFixtureTrait;

/**
 * Feature test dashboard analytics: distribusi status snapshot,
 * tren check 7 hari, dan health per source.
 *
 * Trait sudah men-seed source `npd` (id 1) dan `sap` (id 2).
 *
 * @internal
 */
final class DashboardAnalyticsTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use MonitoringFixtureTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMonitoringFixture();
    }

    public function testDashboardShowsEmptyAnalyticsState()
    {
        $result = $this->withSession($this->authSession())->get('/');

        $result->assertOK();
        // Placeholder lama sudah diganti.
        $this->assertBodyNotSee('Analytics menyusul', $result);

        // Snapshot kosong: semua status 0 + ajakan Check All.
        $this->assertBodySee('data-status="OK" data-count="0"', $result);
        $this->assertBodySee('data-status="PENDING_CONFIG" data-count="0"', $result);
        $this->assertBodySee('data-status="CONN_ERROR" data-count="0"', $result);
        $this->assertBodySee('Belum ada hasil check', $result);

        // Tren kosong: empty state tujuh hari.
        $this->assertBodySee('Belum ada check dalam 7 hari terakhir.', $result);

        // Source aktif tetap tampil dengan nol sel.
        $this->assertBodySee('data-source="npd" data-total="0" data-ok="0"', $result);
        $this->assertBodySee('data-source="sap" data-total="0" data-ok="0"', $result);
    }

    public function testDashboardCountsSnapshotsPerStatus()
    {
        $npd = $this->sourceId('npd');
        $sap = $this->sourceId('sap');

        $fx   = $this->insertWatchedTable(['label' => 'Pesanan FX']);
        $void = $this->insertWatchedTable([
            'table_name' => 'fx_kosong',
            'label'      => 'Kosong',
        ]);

        $this->insertSnapshot(['watched_table_id' => $fx, 'source_id' => $npd, 'status' => 'OK']);
        $this->insertSnapshot(['watched_table_id' => $fx, 'source_id' => $sap, 'status' => 'OK']);
        $this->insertSnapshot(['watched_table_id' => $void, 'source_id' => $npd, 'status' => 'STALE']);

        $result = $this->withSession($this->authSession())->get('/');

        $result->assertOK();
        $this->assertBodySee('data-status="OK" data-count="2"', $result);
        $this->assertBodySee('data-status="STALE" data-count="1"', $result);
        $this->assertBodySee('data-status="NEVER_SYNCED" data-count="0"', $result);
        $this->assertBodyNotSee('Belum ada hasil check', $result);

        // Health per source: npd 1 OK dari 2 sel; sap 1 OK dari 1 sel.
        $this->assertBodySee('data-source="npd" data-total="2" data-ok="1"', $result);
        $this->assertBodySee('data-source="sap" data-total="1" data-ok="1"', $result);
    }

    public function testDashboardTrendAggregatesSevenDayWindow()
    {
        $today   = date('Y-m-d');
        $d3      = date('Y-m-d', strtotime('-3 days'));
        $d10     = date('Y-m-d', strtotime('-10 days'));
        $tableId = $this->insertWatchedTable();
        $npd     = $this->sourceId('npd');
        $base    = ['watched_table_id' => $tableId, 'source_id' => $npd];

        $this->insertHistory($base + ['status' => 'OK', 'executed_at' => $today . ' 08:00:00']);
        $this->insertHistory($base + ['status' => 'STALE', 'executed_at' => $today . ' 13:00:00']);
        $this->insertHistory($base + ['status' => 'MISSING_TABLE', 'executed_at' => $d3 . ' 09:00:00']);
        $this->insertHistory($base + ['status' => 'CONN_ERROR', 'executed_at' => $d10 . ' 09:00:00']); // di luar jendela

        $result = $this->withSession($this->authSession())->get('/');

        $result->assertOK();
        $this->assertBodySee('data-trend-day="' . $today . '" data-total="2" data-breakdown="OK:1,STALE:1"', $result);
        $this->assertBodySee('data-trend-day="' . $d3 . '" data-total="1" data-breakdown="MISSING_TABLE:1"', $result);
        $this->assertBodyNotSee('data-trend-day="' . $d10 . '"', $result);
        $this->assertBodyNotSee('Belum ada check dalam 7 hari terakhir.', $result);
    }

    public function testDashboardSourceHealthShowsPercentBar()
    {
        $npd = $this->sourceId('npd');
        $sap = $this->sourceId('sap');

        $fx = $this->insertWatchedTable(['label' => 'Pesanan FX']);
        $ng = $this->insertWatchedTable([
            'table_name' => 'fx_ngawur',
            'label'      => 'Ngawur',
        ]);
        $this->insertSnapshot(['watched_table_id' => $fx, 'source_id' => $npd, 'status' => 'OK']);
        $this->insertSnapshot(['watched_table_id' => $ng, 'source_id' => $npd, 'status' => 'CONN_ERROR']);
        // sap sengaja tanpa snapshot -> 0%

        $result = $this->withSession($this->authSession())->get('/');

        $result->assertOK();
        $this->assertBodySee('data-source="npd" data-total="2" data-ok="1"', $result);
        $this->assertBodySee('data-source="sap" data-total="0" data-ok="0"', $result);
        $this->assertBodySee('style="width: 50%"', $result); // bar npd 1/2 = 50%
    }
}
