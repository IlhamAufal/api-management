<?php

namespace App\Libraries\Monitoring;

use App\Models\SourceModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Agregasi read-only dari `table_snapshots` + `check_history`
 * untuk dashboard analytics (pengganti chart lama berbasis `sys_sync_logs`).
 *
 * Portable MySQL & SQLite (tests): filter waktu memakai string ISO,
 * bucket harian dihitung di PHP — tanpa fungsi DATE() lintas dialect.
 */
class AnalyticsService
{
    /** Urutan tampil status di dashboard (kiri → kanan). */
    public const STATUS_ORDER = ['OK', 'STALE', 'NEVER_SYNCED', 'MISSING_TABLE', 'CONN_ERROR'];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Jumlah sel (tabel x source) per status dari snapshot terkini.
     * Seluruh status selalu ada (diisi 0) supaya UI stabil.
     *
     * @return array<string, int> status => count
     */
    public function statusBreakdown(): array
    {
        $rows = $this->db->table('table_snapshots')
            ->select('status, COUNT(*) AS cnt', false)
            ->groupBy('status')
            ->get()
            ->getResultArray();

        $counts = array_fill_keys(self::STATUS_ORDER, 0);
        foreach ($rows as $row) {
            $counts[(string) $row['status']] = (int) $row['cnt'];
        }

        return $counts;
    }

    /**
     * Tren check harian N hari terakhir (termasuk hari ini), kronologis,
     * hari tanpa aktivitas diisi total 0.
     *
     * @return array<int, array{date: string, label: string, total: int, byStatus: array<string, int>}>
     */
    public function trend(int $days = 7): array
    {
        $cutoff = (new \DateTimeImmutable('today'))
            ->modify('-' . (max(1, $days) - 1) . ' days');

        $rows = $this->db->table('check_history')
            ->select('executed_at, status')
            ->where('executed_at >=', $cutoff->format('Y-m-d H:i:s'))
            ->get()
            ->getResultArray();

        $buckets = [];
        for ($i = 0; $i < max(1, $days); $i++) {
            $day  = $cutoff->modify("+{$i} days");
            $key  = $day->format('Y-m-d');
            $buckets[$key] = [
                'date'     => $key,
                'label'    => $day->format('d/m'),
                'total'    => 0,
                'byStatus' => array_fill_keys(self::STATUS_ORDER, 0),
            ];
        }

        foreach ($rows as $row) {
            $date = substr((string) $row['executed_at'], 0, 10);
            if (! isset($buckets[$date])) {
                continue; // di luar jendela (mis. clock drift) — abaikan
            }
            $status = (string) $row['status'];
            $buckets[$date]['total']++;
            if (! isset($buckets[$date]['byStatus'][$status])) {
                $buckets[$date]['byStatus'][$status] = 0;
            }
            $buckets[$date]['byStatus'][$status]++;
        }

        return array_values($buckets);
    }

    /**
     * Kesehatan per source aktif: sel OK / total sel dari snapshot terkini.
     *
     * @return array<int, array{code: string, label: string, total: int, ok: int, percent: int}>
     */
    public function sourceHealth(): array
    {
        $rows = $this->db->table('table_snapshots')
            ->select('source_id, status, COUNT(*) AS cnt', false)
            ->groupBy('source_id, status')
            ->get()
            ->getResultArray();

        $bySource = [];
        foreach ($rows as $row) {
            $sid = (int) $row['source_id'];
            if (! isset($bySource[$sid])) {
                $bySource[$sid] = ['total' => 0, 'ok' => 0];
            }
            $bySource[$sid]['total'] += (int) $row['cnt'];
            if ($row['status'] === 'OK') {
                $bySource[$sid]['ok'] += (int) $row['cnt'];
            }
        }

        $health = [];
        foreach ((new SourceModel())->getActiveSources() as $source) {
            $sid    = (int) $source['id'];
            $total  = $bySource[$sid]['total'] ?? 0;
            $ok     = $bySource[$sid]['ok'] ?? 0;
            $health[] = [
                'code'    => (string) $source['code'],
                'label'   => (string) $source['label'],
                'total'   => $total,
                'ok'      => $ok,
                'percent' => $total > 0 ? (int) round($ok / $total * 100) : 0,
            ];
        }

        return $health;
    }
}
