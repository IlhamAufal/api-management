<?php

namespace App\Controllers;

use App\Libraries\Monitoring\RelativeTime;
use App\Libraries\Monitoring\TableFreshnessChecker;
use App\Models\CheckHistoryModel;
use App\Models\SourceModel;
use App\Models\TableSnapshotModel;
use App\Models\WatchedTableModel;
use Config\Services;

/**
 * Dashboard matrix: baris = watched_tables, kolom = sources,
 * sel = status freshness terkini + waktu sync terakhir.
 * Menggantikan modul Monitoring lama (pipeline/per-app).
 */
class MonitoringMatrix extends BaseController
{
    private WatchedTableModel $watchedModel;
    private SourceModel $sourceModel;
    private TableSnapshotModel $snapshotModel;
    private CheckHistoryModel $historyModel;

    public function __construct()
    {
        $this->watchedModel  = new WatchedTableModel();
        $this->sourceModel   = new SourceModel();
        $this->snapshotModel = new TableSnapshotModel();
        $this->historyModel  = new CheckHistoryModel();
    }

    public function index()
    {
        $sources = $this->sourceModel->getActiveSources();

        if ($sources === []) {
            return $this->renderSource(null, $sources);
        }

        // Landing = source aktif pertama (tanpa redirect; URL tetap /monitoring).
        return $this->renderSource($sources[0], $sources);
    }

    /**
     * Halaman satu source berdasarkan `sources.code`. Tiap tab adalah
     * URL nyata yang bisa di-share. Code tidak ada / nonaktif → 404.
     */
    public function source(string $code)
    {
        $source = $this->sourceModel->findByCode($code);

        if ($source === null || (int) $source['is_active'] !== 1) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'Source "' . $code . '" tidak ditemukan atau tidak aktif.'
            );
        }

        return $this->renderSource($source, $this->sourceModel->getActiveSources());
    }

    /**
     * Render halaman per-source: tab bar semua source aktif + daftar
     * semua watched table aktif pada source terpilih (worst-first).
     *
     * @param array<string,mixed>|null       $source
     * @param array<int,array<string,mixed>> $sources
     */
    private function renderSource(?array $source, array $sources)
    {
        $tables = $this->watchedModel->getActiveTables();

        $rows    = [];
        $summary = [];
        if ($source !== null) {
            // Snapshot untuk source ini saja (lookup by watched_table_id).
            $snapshots = [];
            foreach ($this->snapshotModel->where('source_id', (int) $source['id'])->findAll() as $snapshot) {
                $snapshots[(int) $snapshot['watched_table_id']] = $snapshot;
            }

            foreach ($tables as $table) {
                $snapshot = $snapshots[(int) $table['id']] ?? null;
                $status   = $snapshot['status'] ?? 'NEVER_SYNCED';
                $rows[] = [
                    'table'    => $table,
                    'snapshot' => $snapshot,
                    'status'   => $status,
                ];
                $summary[$status] = ($summary[$status] ?? 0) + 1;
            }

            // Urut worst-first lalu label (plan amendmen bagian 0).
            usort($rows, function (array $a, array $b): int {
                $cmp = $this->statusRank($b['status']) <=> $this->statusRank($a['status']);

                return $cmp !== 0
                    ? $cmp
                    : strcasecmp((string) $a['table']['label'], (string) $b['table']['label']);
            });
        }

        return view('pages/monitoring_source/index', [
            'title'        => 'Monitoring | MD-Bridge',
            'page'         => 'monitoring',
            'sources'      => $sources,
            'activeSource' => $source,
            'rows'         => $rows,
            'summary'      => $summary,
            'relative'     => static fn (?string $dt): string => RelativeTime::format($dt),
            'flashSuccess' => session()->getFlashdata('flash_success'),
            'flashError'   => session()->getFlashdata('flash_error'),
        ]);
    }

    /**
     * "Check All": satu request memeriksa seluruh sel aktif.
     * Mengembalikan JSON untuk permintaan AJAX (tombol navbar),
     * atau redirect kembali ke matrix untuk form biasa.
     */
    public function checkAll()
    {
        $wantsJson = $this->request->getHeaderLine('Accept') === 'application/json';

        try {
            $results = (Services::freshnessChecker())->checkAll();
            $summary = $this->summarize($results);

            if ($wantsJson) {
                return $this->response->setJSON([
                    'status'  => 'SUCCESS',
                    'message' => 'Check selesai: ' . $summary . '.',
                ]);
            }

            return redirect()->to(base_url('monitoring'))
                ->with('flash_success', 'Check selesai: ' . $summary . '.');
        } catch (\Throwable $e) {
            $message = 'Check gagal: ' . TableFreshnessChecker::shortError($e->getMessage());

            if ($wantsJson) {
                return $this->response->setStatusCode(500)->setJSON([
                    'status'  => 'FAILED',
                    'message' => $message,
                ]);
            }

            return redirect()->to(base_url('monitoring'))
                ->with('flash_error', $message);
        }
    }

    /**
     * "Check Sel": satu pasangan (watched_table, source).
     * Redirect balik ke halaman source asal.
     */
    public function checkCell($tableId, $sourceId)
    {
        $table  = $this->watchedModel->find((int) $tableId);
        $source = $this->sourceModel->find((int) $sourceId);

        if ($table === null || (int) $table['is_active'] !== 1 || $source === null) {
            return redirect()->to(base_url('monitoring'))
                ->with('flash_error', 'Watched table atau source tidak ditemukan / tidak aktif.');
        }

        $back = base_url('monitoring/source/' . $source['code']);

        try {
            $result  = (Services::freshnessChecker())->check($table, $source);
            $summary = $this->summarize([$result]);

            return redirect()->to($back)
                ->with('flash_success', 'Check "' . $table['label'] . '" di ' . $source['label'] . ' selesai: ' . $summary . '.');
        } catch (\Throwable $e) {
            return redirect()->to($back)
                ->with('flash_error', 'Check gagal: ' . TableFreshnessChecker::shortError($e->getMessage()));
        }
    }

    /**
     * "Check Sumber Ini": semua watched table aktif pada satu source.
     * Redirect balik ke halaman source tersebut.
     */
    public function checkSource(string $code)
    {
        $source = $this->sourceModel->findByCode($code);

        if ($source === null || (int) $source['is_active'] !== 1) {
            return redirect()->to(base_url('monitoring'))
                ->with('flash_error', 'Source tidak ditemukan atau tidak aktif.');
        }

        $back   = base_url('monitoring/source/' . $source['code']);
        $tables = $this->watchedModel->getActiveTables();

        try {
            $results = (Services::freshnessChecker())->runFor($tables, [$source]);
            $summary = $this->summarize($results);

            return redirect()->to($back)
                ->with('flash_success', 'Check ' . $source['label'] . ' selesai: ' . $summary . '.');
        } catch (\Throwable $e) {
            return redirect()->to($back)
                ->with('flash_error', 'Check gagal: ' . TableFreshnessChecker::shortError($e->getMessage()));
        }
    }

    /**
     * Detail riwayat per pasangan (tabel, source) — dipakai untuk
     * debugging "sejak kapan mulai STALE".
     */
    public function history($tableId, $sourceId)
    {
        $table  = $this->watchedModel->find((int) $tableId);
        $source = $this->sourceModel->find((int) $sourceId);

        if ($table === null || $source === null) {
            return redirect()->to(base_url('monitoring'))
                ->with('flash_error', 'Tabel atau source tidak ditemukan.');
        }

        $snapshot = $this->snapshotModel
            ->where('watched_table_id', (int) $tableId)
            ->where('source_id', (int) $sourceId)
            ->first();

        // Pagination + filter status via query param (tanpa route baru).
        $page    = (int) ($this->request->getGet('page') ?? 1);
        $status  = $this->request->getGet('status');
        $status  = is_string($status) && $status !== '' ? $status : null;
        $perPage = 20;

        $paged = $this->historyModel->paginatedFor(
            (int) $tableId,
            (int) $sourceId,
            $page,
            $perPage,
            $status
        );

        return view('pages/monitoring_matrix/history', [
            'title'    => 'Riwayat Check | ' . $table['label'] . ' | MD-Bridge',
            'page'     => 'monitoring',
            'table'    => $table,
            'source'   => $source,
            'snapshot' => $snapshot,
            'history'  => $paged['rows'],
            'pagination' => [
                'total'      => $paged['total'],
                'page'       => $paged['page'],
                'perPage'    => $paged['perPage'],
                'totalPages' => $paged['totalPages'],
            ],
            'statusFilter'  => $paged['status'],
            'statusOptions' => CheckHistoryModel::STATUSES,
            'relative'      => static fn (?string $dt): string => RelativeTime::format($dt),
        ]);
    }

    /**
     * Halaman alur workflow (Drawflow) — visualisasi read-only:
     * source -> checker -> watched table -> matrix dashboard.
     * Pengganti canvas "Alur Workflow" di modul Monitoring lama.
     */
    public function workflow()
    {
        $tables  = $this->watchedModel->getActiveTables();
        $sources = $this->sourceModel->getActiveSources();

        $snapshots = [];
        foreach ($this->snapshotModel->findAll() as $snapshot) {
            $snapshots[$snapshot['watched_table_id'] . ':' . $snapshot['source_id']] = $snapshot;
        }

        return view('pages/monitoring_matrix/workflow', [
            'title'    => 'Alur Workflow | MD-Bridge',
            'page'     => 'monitoring',
            'workflow' => $this->buildWorkflow($tables, $sources, $snapshots),
            'tables'   => $tables,
            'sources'  => $sources,
        ]);
    }

    /**
     * Susun nodes + edges untuk komponen components/workflow_canvas.
     *
     * Kolom 0 = source, kolom 1 = checker, kolom 2 = watched table,
     * kolom 3 = matrix dashboard. Warna node = status terburuk dari
     * seluruh snapshot pasangan (tabel, source).
     *
     * @return array{nodes: array<int, array<string, mixed>>, edges: array<int, array{from: string, to: string}>}
     */
    private function buildWorkflow(array $tables, array $sources, array $snapshots): array
    {
        $nodes = [];
        $edges = [];

        // Kolom 0 - sumber data aktif.
        foreach ($sources as $source) {
            $key = 'source_' . $source['code'];
            $nodes[] = [
                'key'      => $key,
                'column'   => 0,
                'variant'  => 'source',
                'icon'     => 'cloud',
                'title'    => $source['label'],
                'subtitle' => $source['code'],
                'status'   => 'SOURCE',
                'meta'     => [
                    ['label' => 'Tabel dipantau', 'value' => (string) count($tables)],
                ],
            ];
            $edges[] = ['from' => $key, 'to' => 'checker'];
        }

        // Ringkasan seluruh sel (tabel x source).
        $okCount    = 0;
        $worst      = null;
        $lastCheck  = null;
        foreach ($tables as $table) {
            foreach ($sources as $source) {
                $snapshot = $snapshots[$table['id'] . ':' . $source['id']] ?? null;
                $status   = $snapshot['status'] ?? 'NEVER_SYNCED';
                $worst    = $this->worstStatus($worst, $status);
                if ($status === 'OK') {
                    $okCount++;
                }
                if ($snapshot !== null && ($lastCheck === null || (string) $snapshot['checked_at'] > $lastCheck)) {
                    $lastCheck = (string) $snapshot['checked_at'];
                }
            }
        }
        $worst ??= 'NEVER_SYNCED';

        // Kolom 1 - checker.
        $nodes[] = [
            'key'      => 'checker',
            'column'   => 1,
            'variant'  => 'process',
            'icon'     => 'worker',
            'title'    => 'TableFreshnessChecker',
            'subtitle' => 'Pemeriksaan freshness (manual)',
            'status'   => $worst,
            'meta'     => [
                ['label' => 'Sel (tabel x source)', 'value' => count($tables) . ' x ' . count($sources)],
                ['label' => 'Check terakhir', 'value' => $lastCheck ? RelativeTime::format($lastCheck) : '-'],
            ],
        ];

        // Kolom 2 - satu node per watched table.
        foreach ($tables as $table) {
            $meta  = [];
            $tableWorst = null;
            foreach ($sources as $source) {
                $snapshot = $snapshots[$table['id'] . ':' . $source['id']] ?? null;
                $status   = $snapshot['status'] ?? 'NEVER_SYNCED';
                $tableWorst = $this->worstStatus($tableWorst, $status);

                $value = 'belum dicek';
                if ($snapshot !== null) {
                    $value = $status;
                    if ($snapshot['row_count'] !== null) {
                        $value .= ' (' . number_format((int) $snapshot['row_count']) . ')';
                    }
                }
                $meta[] = ['label' => $source['code'], 'value' => $value];
            }
            $meta[] = ['label' => 'Stale >', 'value' => (int) $table['stale_after_minutes'] . 'm'];

            $tableKey = 'table_' . $table['id'];
            $nodes[] = [
                'key'      => $tableKey,
                'column'   => 2,
                'variant'  => 'table',
                'icon'     => 'table',
                'title'    => $table['label'],
                'subtitle' => $table['table_name'],
                'status'   => $tableWorst ?? 'NEVER_SYNCED',
                'meta'     => $meta,
            ];
            $edges[] = ['from' => 'checker', 'to' => $tableKey];
            $edges[] = ['from' => $tableKey, 'to' => 'sink'];
        }

        // Kolom 3 - dashboard matrix sebagai tujuan akhir.
        $totalCells = count($tables) * count($sources);
        $nodes[] = [
            'key'      => 'sink',
            'column'   => 3,
            'variant'  => 'sink',
            'icon'     => 'database',
            'title'    => 'Monitoring Matrix',
            'subtitle' => 'Dashboard MD-Bridge',
            'status'   => $worst,
            'meta'     => [
                ['label' => 'Sel sehat (OK)', 'value' => $okCount . '/' . $totalCells],
            ],
        ];

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    /**
     * Rank severity (plan amendmen bagian 0):
     * CONN_ERROR > PENDING_CONFIG > MISSING_TABLE > NEVER_SYNCED > STALE > OK.
     * Dipakai untuk sort worst-first dan menentukan status terburuk.
     */
    private function statusRank(string $status): int
    {
        $rank = [
            'CONN_ERROR'     => 6,
            'PENDING_CONFIG' => 5,
            'MISSING_TABLE'  => 4,
            'NEVER_SYNCED'   => 3,
            'STALE'          => 2,
            'OK'             => 1,
        ];

        return $rank[$status] ?? 0;
    }

    /**
     * Status terburuk berdasarkan urutan plan amendmen bagian 0.
     */
    private function worstStatus(?string $current, string $candidate): string
    {
        if ($current === null) {
            return $candidate;
        }

        return $this->statusRank($candidate) > $this->statusRank($current) ? $candidate : $current;
    }

    /**
     * @param \App\Libraries\Monitoring\CheckResult[] $results
     */
    private function summarize(array $results): string
    {
        $counts = [];
        foreach ($results as $result) {
            $counts[$result->status] = ($counts[$result->status] ?? 0) + 1;
        }

        if ($counts === []) {
            return 'tidak ada sel untuk diperiksa';
        }

        $parts = [];
        foreach ($counts as $status => $count) {
            $parts[] = $count . ' ' . $status;
        }

        return count($results) . ' sel (' . implode(', ', $parts) . ')';
    }
}
