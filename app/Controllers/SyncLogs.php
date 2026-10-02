<?php

namespace App\Controllers;

/**
 * Execution Logs: membaca check_history (riwayat append-only
 * setiap kali check dijalankan) — pengganti sys_sync_logs hardcoded.
 */
class SyncLogs extends BaseController
{
    public function index()
    {
        $rows = db_connect()
            ->table('check_history')
            ->select('check_history.*, watched_tables.table_name, watched_tables.label AS table_label, sources.code AS source_code, sources.label AS source_label')
            ->join('watched_tables', 'watched_tables.id = check_history.watched_table_id', 'left')
            ->join('sources', 'sources.id = check_history.source_id', 'left')
            ->orderBy('check_history.executed_at', 'DESC')
            ->orderBy('check_history.id', 'DESC')
            ->limit(200)
            ->get()
            ->getResultArray();

        return view('pages/sync_logs/index', [
            'title' => 'Execution Logs | MD-Bridge',
            'page'  => 'logs',
            'rows'  => $rows,
        ]);
    }
}
