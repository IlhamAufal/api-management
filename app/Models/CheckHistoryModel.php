<?php

namespace App\Models;

use CodeIgniter\Model;

class CheckHistoryModel extends Model
{
    /** Status valid untuk filter riwayat (sinkron dengan ENUM migrasi). */
    public const STATUSES = ['NEVER_SYNCED', 'STALE', 'OK', 'MISSING_TABLE', 'PENDING_CONFIG', 'CONN_ERROR'];

    /** Trigger valid untuk filter riwayat (sinkron dengan ENUM migrasi). */
    public const TRIGGERS = ['MANUAL_UI', 'CRON'];

    protected $table = 'check_history';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'watched_table_id',
        'source_id',
        'trigger_type',
        'row_count',
        'last_synced_at',
        'status',
        'error_message',
        'executed_at',
    ];

    protected $validationRules = [
        'watched_table_id' => 'required|is_natural_no_zero',
        'source_id'        => 'required|is_natural_no_zero',
        'trigger_type'     => 'required|in_list[MANUAL_UI,CRON]',
        'row_count'        => 'permit_empty|is_natural',
        'last_synced_at'   => 'permit_empty',
        'status'           => 'required|in_list[NEVER_SYNCED,STALE,OK,MISSING_TABLE,PENDING_CONFIG,CONN_ERROR]',
        'error_message'    => 'permit_empty|string',
        'executed_at'      => 'permit_empty',
    ];

    public function recentFor(int $watchedTableId, int $sourceId, int $limit = 50): array
    {
        return $this->where('watched_table_id', $watchedTableId)
            ->where('source_id', $sourceId)
            ->orderBy('executed_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Riwayat check untuk satu pasangan (tabel, source) dengan pagination
     * dan filter status opsional. Mengganti pemotongan diam-diam di 100 baris
     * terakhir pada halaman riwayat.
     *
     * @param int         $watchedTableId ID watched table
     * @param int         $sourceId       ID source
     * @param int         $page           Halaman (1-based; nilai < 1 dipaksa 1)
     * @param int         $perPage        Jumlah baris per halaman (dipaksa >= 1)
     * @param string|null $status         Filter status; null / tidak valid = semua
     *
     * @return array{rows: array<int, array<string, mixed>>, total: int, page: int, perPage: int, totalPages: int, status: string|null}
     */
    public function paginatedFor(
        int $watchedTableId,
        int $sourceId,
        int $page = 1,
        int $perPage = 20,
        ?string $status = null
    ): array {
        $page    = max(1, $page);
        $perPage = max(1, $perPage);
        $status  = ($status !== null && in_array($status, self::STATUSES, true)) ? $status : null;

        $applyFilters = function ($builder) use ($watchedTableId, $sourceId, $status) {
            $builder->where('watched_table_id', $watchedTableId)
                ->where('source_id', $sourceId);

            if ($status !== null) {
                $builder->where('status', $status);
            }

            return $builder;
        };

        $total = (int) $applyFilters($this->builder())->countAllResults(true);

        $totalPages = (int) max(1, (int) ceil($total / $perPage));
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * $perPage;

        $rows = $applyFilters($this)
            ->orderBy('executed_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll($perPage, $offset);

        return [
            'rows'       => $rows,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => $totalPages,
            'status'     => $status,
        ];
    }

    /**
     * Seluruh riwayat check (halaman Execution Logs) dengan pagination dan
     * filter status / source / trigger. Join ke watched_tables & sources
     * untuk menampilkan label — join 1:1 sehingga tidak melipatgandakan baris.
     *
     * Builder dibuat segar per query (`db->table()`) karena join tidak boleh
     * menumpuk di builder model yang di-cache.
     *
     * @param int         $page       Halaman (1-based; nilai < 1 dipaksa 1)
     * @param int         $perPage    Jumlah baris per halaman (dipaksa >= 1)
     * @param string|null $status     Filter status; null / tidak valid = semua
     * @param string|null $sourceCode Filter kode source; null / tidak valid = semua
     * @param string|null $trigger    Filter trigger; null / tidak valid = semua
     *
     * @return array{rows: array<int, array<string, mixed>>, total: int, page: int, perPage: int, totalPages: int, status: string|null, source: string|null, trigger: string|null}
     */
    public function paginatedLogs(
        int $page = 1,
        int $perPage = 20,
        ?string $status = null,
        ?string $sourceCode = null,
        ?string $trigger = null
    ): array {
        $page    = max(1, $page);
        $perPage = max(1, $perPage);
        $status  = ($status !== null && in_array($status, self::STATUSES, true)) ? $status : null;
        $trigger = ($trigger !== null && in_array($trigger, self::TRIGGERS, true)) ? $trigger : null;

        $applyFilters = static function ($builder) use ($status, $sourceCode, $trigger) {
            if ($status !== null) {
                $builder->where('check_history.status', $status);
            }
            if ($sourceCode !== null) {
                $builder->where('sources.code', $sourceCode);
            }
            if ($trigger !== null) {
                $builder->where('check_history.trigger_type', $trigger);
            }

            return $builder;
        };

        $total = (int) $applyFilters($this->db->table($this->table))
            ->join('sources', 'sources.id = check_history.source_id', 'left')
            ->countAllResults();

        $totalPages = (int) max(1, (int) ceil($total / $perPage));
        $page       = min($page, $totalPages);
        $offset     = ($page - 1) * $perPage;

        $rows = $applyFilters($this->db->table($this->table))
            ->select('check_history.*, watched_tables.table_name, watched_tables.label AS table_label, sources.code AS source_code, sources.label AS source_label')
            ->join('watched_tables', 'watched_tables.id = check_history.watched_table_id', 'left')
            ->join('sources', 'sources.id = check_history.source_id', 'left')
            ->orderBy('check_history.executed_at', 'DESC')
            ->orderBy('check_history.id', 'DESC')
            ->get($perPage, $offset)
            ->getResultArray();

        return [
            'rows'       => $rows,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => $totalPages,
            'status'     => $status,
            'source'     => $sourceCode,
            'trigger'    => $trigger,
        ];
    }
}
