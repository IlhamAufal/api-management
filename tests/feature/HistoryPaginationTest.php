<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\MonitoringFixtureTrait;

/**
 * Feature test B3: pagination + filter status pada halaman riwayat check
 * (`monitoring/history/{tableId}/{sourceId}`). Mengganti pemotongan diam-diam
 * di 100 baris. Query param: ?page= dan ?status= (tanpa route baru).
 *
 * @internal
 */
final class HistoryPaginationTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use MonitoringFixtureTrait;

    private int $tableId;
    private int $sourceId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMonitoringFixture();

        $this->sourceId = $this->sourceId('npd');
        $this->tableId  = $this->insertWatchedTable(['label' => 'Pesanan FX']);
    }

    /**
     * Isi N baris riwayat. Baris ke-i dibuat makin lama ke belakang,
     * dengan row_count = 1000 + i. Jadi:
     *   - baris TERBARU  = row_count 1000 (i=0)
     *   - baris TERTUA   = row_count 1000 + (N-1)
     * Urutan tampil halaman = terbaru dulu (executed_at DESC).
     */
    private function seedHistory(int $count, string $status = 'OK'): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->insertHistory([
                'watched_table_id' => $this->tableId,
                'source_id'        => $this->sourceId,
                'status'           => $status,
                'row_count'        => 1000 + $i,
                'executed_at'      => date('Y-m-d H:i:s', time() - ($i * 60)),
            ]);
        }
    }

    private function historyUrl(array $params = []): string
    {
        $url = 'monitoring/history/' . $this->tableId . '/' . $this->sourceId;

        return $params === [] ? $url : $url . '?' . http_build_query($params);
    }

    public function testFirstPageShowsAtMost20RowsWhenMoreExist()
    {
        $this->seedHistory(25);

        $result = $this->withSession($this->authSession())->get($this->historyUrl());

        $result->assertOK();
        // 25 baris total, 20 per halaman -> halaman 1 dari 2.
        $this->assertBodySee('halaman 1 dari 2', $result);
        $this->assertBodySee('25 baris', $result);
        // Baris terbaru (row_count 1000) tampil di halaman 1;
        // baris tertua (1024) berada di halaman 2.
        $this->assertBodySee('1,000', $result);
        $this->assertBodyNotSee('1,024', $result);
        // Kontrol pagination muncul.
        $this->assertBodySee('Berikutnya', $result);
    }

    public function testSecondPageShowsRemainingRows()
    {
        $this->seedHistory(25);

        $result = $this->withSession($this->authSession())
            ->get($this->historyUrl(['page' => 2]));

        $result->assertOK();
        $this->assertBodySee('halaman 2 dari 2', $result);
        // Sisa 5 baris tertua ada di halaman 2 (termasuk 1024).
        $this->assertBodySee('1,024', $result);
        $this->assertBodySee('Sebelumnya', $result);
    }

    public function testPageBeyondRangeClampsToLastPage()
    {
        $this->seedHistory(25);

        $result = $this->withSession($this->authSession())
            ->get($this->historyUrl(['page' => 99]));

        $result->assertOK();
        // Dipaksa ke halaman terakhir (2), bukan error / kosong.
        $this->assertBodySee('halaman 2 dari 2', $result);
        $this->assertBodySee('1,024', $result);
    }

    public function testStatusFilterLimitsRowsAndCount()
    {
        $this->seedHistory(5, 'OK');
        $this->seedHistory(3, 'STALE');

        $result = $this->withSession($this->authSession())
            ->get($this->historyUrl(['status' => 'STALE']));

        $result->assertOK();
        // Hanya 3 baris STALE yang dihitung (membuktikan filter bekerja).
        $this->assertBodySee('3 baris', $result);
        $this->assertBodySee('status <code', $result); // ringkasan menandai filter aktif
        $this->assertStatusBadge($result, 'STALE');
    }

    public function testInvalidStatusFilterIsIgnoredAndShowsAll()
    {
        $this->seedHistory(5, 'OK');
        $this->seedHistory(3, 'STALE');

        $result = $this->withSession($this->authSession())
            ->get($this->historyUrl(['status' => 'NGACO']));

        $result->assertOK();
        // Status tidak valid diabaikan -> semua 8 baris terhitung.
        $this->assertBodySee('8 baris', $result);
    }

    public function testEmptyStatusFilterShowsFilteredEmptyState()
    {
        $this->seedHistory(5, 'OK');

        $result = $this->withSession($this->authSession())
            ->get($this->historyUrl(['status' => 'CONN_ERROR']));

        $result->assertOK();
        $this->assertBodySee('Tidak ada riwayat dengan status ini', $result);
    }

    public function testNoPaginationNavWhenSinglePage()
    {
        $this->seedHistory(5);

        $result = $this->withSession($this->authSession())->get($this->historyUrl());

        $result->assertOK();
        $this->assertBodySee('5 baris', $result);
        // Hanya 1 halaman -> nav prev/next tidak dirender.
        $this->assertBodyNotSee('Navigasi halaman riwayat', $result);
    }
}
