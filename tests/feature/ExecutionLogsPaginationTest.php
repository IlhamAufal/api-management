<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\MonitoringFixtureTrait;

/**
 * Feature test pagination + filter (status/source/trigger) di halaman
 * Execution Logs (`/logs`). Mengikuti pola HistoryPaginationTest:
 * query param ?page= ?status= ?source= ?trigger= tanpa route baru.
 *
 * @internal
 */
final class ExecutionLogsPaginationTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use MonitoringFixtureTrait;

    private int $tableId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMonitoringFixture();

        $this->tableId = $this->insertWatchedTable(['label' => 'Pesanan FX']);
    }

    /**
     * Isi N baris riwayat; baris ke-i makin lama ke belakang.
     * row_count = 1000 + i → terbaru = 1000, tertua = 1000 + (N-1).
     */
    private function seedHistory(int $count, array $overrides = []): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->insertHistory($overrides + [
                'watched_table_id' => $this->tableId,
                'source_id'        => $this->sourceId('npd'),
                'status'           => 'OK',
                'row_count'        => 1000 + $i,
                'executed_at'      => date('Y-m-d H:i:s', time() - ($i * 60)),
            ]);
        }
    }

    private function logsUrl(array $params = []): string
    {
        return $params === [] ? 'logs' : 'logs?' . http_build_query($params);
    }

    public function testFirstPageShowsAtMost20RowsWhenMoreExist()
    {
        $this->seedHistory(25);

        $result = $this->withSession($this->authSession())->get('logs');

        $result->assertOK();
        // 25 baris total, 20 per halaman -> halaman 1 dari 2.
        $this->assertBodySee('25 baris', $result);
        $this->assertBodySee('halaman 1 dari 2', $result);
        $this->assertBodySee('1,000', $result);   // terbaru, halaman 1
        $this->assertBodyNotSee('1,024', $result); // tertua, halaman 2
        $this->assertBodySee('Berikutnya', $result);
    }

    public function testSecondPageShowsRemainingRows()
    {
        $this->seedHistory(25);

        $result = $this->withSession($this->authSession())->get($this->logsUrl(['page' => 2]));

        $result->assertOK();
        $this->assertBodySee('halaman 2 dari 2', $result);
        $this->assertBodySee('1,024', $result);
        $this->assertBodySee('Sebelumnya', $result);
    }

    public function testPageBeyondRangeClampsToLastPage()
    {
        $this->seedHistory(25);

        $result = $this->withSession($this->authSession())->get($this->logsUrl(['page' => 99]));

        $result->assertOK();
        $this->assertBodySee('halaman 2 dari 2', $result);
        $this->assertBodySee('1,024', $result);
    }

    public function testStatusFilterLimitsRowsAndCount()
    {
        $this->seedHistory(5, ['status' => 'OK']);
        $this->seedHistory(3, ['status' => 'STALE']);

        $result = $this->withSession($this->authSession())->get($this->logsUrl(['status' => 'STALE']));

        $result->assertOK();
        $this->assertBodySee('3 baris', $result);
        $this->assertBodySee('status <code', $result); // ringkasan menandai filter aktif
        $this->assertStatusBadge($result, 'STALE');
    }

    public function testSourceFilterLimitsRows()
    {
        $this->seedHistory(4); // npd
        $this->seedHistory(2, ['source_id' => $this->sourceId('sap')]);

        $result = $this->withSession($this->authSession())->get($this->logsUrl(['source' => 'sap']));

        $result->assertOK();
        $this->assertBodySee('2 baris', $result);
        $this->assertBodySee('source <code', $result);
        // Tabel fixture tetap "Pesanan FX" untuk kedua source — bedakan lewat badge jumlah.
        $this->assertBodyNotSee('4 baris', $result);
    }

    public function testTriggerFilterLimitsRows()
    {
        $this->seedHistory(4, ['trigger_type' => 'MANUAL_UI']);
        $this->seedHistory(2, ['trigger_type' => 'CRON']);

        $result = $this->withSession($this->authSession())->get($this->logsUrl(['trigger' => 'CRON']));

        $result->assertOK();
        $this->assertBodySee('2 baris', $result);
        $this->assertBodySee('trigger <code', $result);
    }

    public function testInvalidFilterValuesAreIgnoredAndShowAll()
    {
        $this->seedHistory(5, ['status' => 'OK']);
        $this->seedHistory(3, ['status' => 'STALE']);

        $result = $this->withSession($this->authSession())->get($this->logsUrl([
            'status'  => 'NGACO',
            'source'  => 'tidak_ada',
            'trigger' => 'APA_SAJA',
        ]));

        $result->assertOK();
        // Ketiga nilai tidak valid diabaikan -> semua 8 baris terhitung.
        $this->assertBodySee('8 baris', $result);
        $this->assertBodyNotSee('filter:', $result);
    }

    public function testFilteredEmptyStateShowsResetHint()
    {
        $this->seedHistory(5, ['status' => 'OK']);

        $result = $this->withSession($this->authSession())->get($this->logsUrl(['status' => 'CONN_ERROR']));

        $result->assertOK();
        $this->assertBodySee('Tidak ada log yang cocok dengan filter', $result);
        $this->assertBodySee('Coba ganti atau reset filter.', $result);
        $this->assertBodySee('Reset', $result);
    }

    public function testPaginationNavPreservesActiveFilters()
    {
        // 25 baris STALE -> nav muncul dan membawa filter di URL.
        $this->seedHistory(25, ['status' => 'STALE']);

        $result = $this->withSession($this->authSession())->get($this->logsUrl(['status' => 'STALE']));

        $result->assertOK();
        $this->assertBodySee('status=STALE', $result); // link Sebelumnya/Berikutnya mempertahankan filter
        $this->assertBodySee('page=2', $result);
    }

    public function testNoPaginationNavWhenSinglePage()
    {
        $this->seedHistory(5);

        $result = $this->withSession($this->authSession())->get('logs');

        $result->assertOK();
        $this->assertBodySee('5 baris', $result);
        $this->assertBodyNotSee('Navigasi halaman log', $result);
    }
}
