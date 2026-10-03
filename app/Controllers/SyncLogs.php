<?php

namespace App\Controllers;

use App\Models\CheckHistoryModel;
use App\Models\SourceModel;

/**
 * Execution Logs: membaca check_history (riwayat append-only
 * setiap kali check dijalankan) — pengganti sys_sync_logs hardcoded.
 *
 * Query params (tanpa route baru, mengikuti pola halaman riwayat):
 *   ?page=N & ?status=X & ?source=CODE & ?trigger=MANUAL_UI|CRON
 * Nilai filter tidak valid diabaikan (tampil semua), bukan error.
 */
class SyncLogs extends BaseController
{
    private const PER_PAGE = 20;

    public function index()
    {
        $page    = (int) ($this->request->getGet('page') ?? 1);
        $status  = $this->stringParam('status');
        $source  = $this->stringParam('source');
        $trigger = $this->stringParam('trigger');

        // Source hanya valid jika ada di registry (hemat query join sia-sia).
        $sourceOptions = (new SourceModel())->findAll();
        if ($source !== null && ! in_array($source, array_column($sourceOptions, 'code'), true)) {
            $source = null;
        }

        $paged = (new CheckHistoryModel())->paginatedLogs(
            $page,
            self::PER_PAGE,
            $status,
            $source,
            $trigger
        );

        return view('pages/sync_logs/index', [
            'title'         => 'Execution Logs | MD-Bridge',
            'page'          => 'logs',
            'rows'          => $paged['rows'],
            'pagination'    => [
                'total'      => $paged['total'],
                'page'       => $paged['page'],
                'perPage'    => $paged['perPage'],
                'totalPages' => $paged['totalPages'],
            ],
            'statusFilter'   => $paged['status'],
            'statusOptions'  => CheckHistoryModel::STATUSES,
            'sourceFilter'   => $paged['source'],
            'sourceOptions'  => $sourceOptions,
            'triggerFilter'  => $paged['trigger'],
            'triggerOptions' => CheckHistoryModel::TRIGGERS,
        ]);
    }

    /**
     * Ambil query param string non-kosong; selain itu null.
     */
    private function stringParam(string $key): ?string
    {
        $value = $this->request->getGet($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
