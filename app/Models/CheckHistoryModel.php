<?php

namespace App\Models;

use CodeIgniter\Model;

class CheckHistoryModel extends Model
{
    /** Status valid untuk filter riwayat (sinkron dengan ENUM migrasi). */
    public const STATUSES = ['NEVER_SYNCED', 'STALE', 'OK', 'MISSING_TABLE', 'CONN_ERROR'];

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
        'status'           => 'required|in_list[NEVER_SYNCED,STALE,OK,MISSING_TABLE,CONN_ERROR]',
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
}
