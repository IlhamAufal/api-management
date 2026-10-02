<?php

use App\Libraries\Monitoring\CheckResult;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\MonitoringFixtureTrait;

/**
 * Feature test PENDING_CONFIG (plan bagian 8.3 & 8.5) pada checkAll()
 * sungguhan: source tanpa connection group di Config/Database.php
 * menghasilkan PENDING_CONFIG tanpa menghentikan source lain.
 *
 * Trait sudah men-seed source `npd` (id 1) dan `sap` (id 2); resolver
 * fixture mengarahkan semua code ke SQLite tests — jadi kalau langkah 0
 * tidak jalan, source `belum_ada_group` justru akan terlihat OK.
 *
 * @internal
 */
final class PendingConfigTest extends CIUnitTestCase
{
    use MonitoringFixtureTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMonitoringFixture();
    }

    public function testCheckAllKeepsCheckingConfiguredSourcesWhenOneIsPending()
    {
        $this->insertWatchedTable(); // fx_orders — ada di fixture remote, synced 1 jam lalu
        $pendingId = $this->insertSource([
            'code'  => 'belum_ada_group',
            'label' => 'Belum Dikonfigurasi',
        ]);

        $results = \Config\Services::freshnessChecker()->checkAll();

        // 1 tabel aktif x 3 source aktif (npd, sap, belum_ada_group).
        $this->assertCount(3, $results);

        $statusBySource = [];
        foreach ($results as $result) {
            $statusBySource[$result->sourceId] = $result->status;
        }

        $npdId = $this->sourceId('npd');
        $sapId = $this->sourceId('sap');

        $this->assertSame(CheckResult::PENDING_CONFIG, $statusBySource[$pendingId]);
        // Source terkonfigurasi tetap dicek sampai selesai — bukan berhenti di tengah.
        $this->assertSame(CheckResult::OK, $statusBySource[$npdId]);
        $this->assertSame(CheckResult::OK, $statusBySource[$sapId]);

        // Persist nyata (SQLite): snapshot source pending berisi pesan provisioning.
        $pendingSnapshot = $this->monitoringDb()
            ->table('table_snapshots')
            ->where('source_id', $pendingId)
            ->get()
            ->getRow();

        $this->assertNotNull($pendingSnapshot);
        $this->assertSame(CheckResult::PENDING_CONFIG, $pendingSnapshot->status);
        $this->assertSame('Harap tambahkan credential terlebih dahulu.', $pendingSnapshot->error_message);

        $this->assertSame(3, $this->countRows('table_snapshots'));
        $this->assertSame(3, $this->countRows('check_history'));
    }

    public function testPendingSnapshotIsReplacedOnceSourceBecomesConfigured()
    {
        $this->insertWatchedTable();
        $pendingId = $this->insertSource(['code' => 'belum_ada_group']);

        $checker = \Config\Services::freshnessChecker();
        $checker->checkAll();

        // "Developer" mendaftarkan group — di test: code diganti ke group
        // yang memang terdaftar (tests) sehingga check berikutnya lolos langkah 0.
        $this->monitoringDb()
            ->table('sources')
            ->where('id', $pendingId)
            ->update(['code' => 'tests']);

        $results = $checker->checkAll();
        $statuses = array_unique(array_column(
            array_filter($results, static fn ($r) => $r->sourceId === $pendingId),
            'status'
        ));

        $this->assertSame([CheckResult::OK], array_values($statuses));

        $snapshot = $this->monitoringDb()
            ->table('table_snapshots')
            ->where('source_id', $pendingId)
            ->get()
            ->getRow();
        $this->assertSame(CheckResult::OK, $snapshot->status);
        // Riwayat append-only: check pertama (PENDING_CONFIG) tetap tercatat.
        $historyStatuses = array_column(
            $this->monitoringDb()
                ->table('check_history')
                ->where('source_id', $pendingId)
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray(),
            'status'
        );
        $this->assertSame([CheckResult::PENDING_CONFIG, CheckResult::OK], $historyStatuses);
    }
}
