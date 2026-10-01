<?php

namespace App\Libraries\Sync;

use App\Models\SysSyncLogModel;

/**
 * Pemeriksaan riwayat sinkronisasi secara READ-ONLY.
 *
 * Hanya membaca COUNT(*) dan MAX(updated_at) dari database sumber
 * (grup koneksi `npd` dan `sap`); tidak ada data yang disalin ke
 * database lokal. Hasil setiap pemeriksaan dicatat ke sys_sync_logs
 * (trigger MANUAL_UI) sehingga riwayat menumpuk seiring waktu.
 */
class HistoryCheckService
{
    /**
     * app_code monitoring => grup koneksi di Config\Database.
     * Aplikasi tanpa entri (mis. sap-sync-ci4) bukan sumber cek DB.
     */
    public const APP_SOURCES = [
        'yp_npd'      => 'npd',
        'sap-get-ci3' => 'sap',
    ];

    /**
     * Label tampilan per grup koneksi.
     */
    public const SOURCE_LABELS = [
        'npd' => 'yp_npd',
        'sap' => 'yp_sap',
    ];

    /**
     * Whitelist tabel + nama tampilan. Hanya tabel ini yang pernah
     * disentuh, nama kolom/ tabel tidak datang dari input pengguna.
     */
    private const TABLES = [
        'sap_material_master'     => 'Material Master',
        'sap_customer_master'     => 'Customer Master',
        'sap_customer_material'   => 'Customer Material',
        'sap_customer_sales_area' => 'Customer Sales Area',
    ];

    /**
     * Grup koneksi untuk aplikasi monitoring, atau null jika bukan sumber DB.
     */
    public function sourceForApp(string $appCode): ?string
    {
        return self::sourceForAppCode($appCode);
    }

    /**
     * Versi statis dari sourceForApp (untuk pemakaian ringan tanpa instance).
     */
    public static function sourceForAppCode(string $appCode): ?string
    {
        return self::APP_SOURCES[$appCode] ?? null;
    }

    /**
     * Apakah credential untuk grup sudah terisi di .env?
     * Hostname & username wajib; jika belum, jangan coba connect.
     */
    public function isConfigured(?string $source): bool
    {
        if ($source === null) {
            return false;
        }

        $group = config('Database')->{$source} ?? null;
        if (!is_array($group)) {
            return false;
        }

        return trim((string) ($group['hostname'] ?? '')) !== ''
            && trim((string) ($group['username'] ?? '')) !== '';
    }

    /**
     * Statistik 4 tabel dari satu sumber (COUNT + MAX(updated_at)).
     * Key = table_code; tidak pernah melepas whitelist TABLES.
     *
     * @return array<string,array{table_code:string,table_name:string,rows:?int,last_updated:?string,error:?string,duration:float}>
     */
    public function fetchStats(?string $source): array
    {
        $stats = [];
        foreach (self::TABLES as $code => $name) {
            $stats[$code] = [
                'table_code'   => $code,
                'table_name'   => $name,
                'rows'         => null,
                'last_updated' => null,
                'error'        => null,
                'duration'     => 0.0,
            ];
        }

        if ($source === null) {
            return $this->markAllFailed($stats, 'Sumber database tidak tersedia.');
        }

        if (! $this->isConfigured($source)) {
            return $this->markAllFailed(
                $stats,
                'Kredensial belum diisi di .env (database.' . $source . '.*).'
            );
        }

        try {
            $db = db_connect($source);
            $db->query('SELECT 1');
        } catch (\Throwable $e) {
            return $this->markAllFailed($stats, $this->shortError($e->getMessage()));
        }

        foreach ($stats as $code => $row) {
            try {
                $stats[$code] = $this->readTable($db, $row);
            } catch (\Throwable $e) {
                $stats[$code]['error'] = $this->shortError($e->getMessage());
            }
        }

        return $stats;
    }

    /**
     * Perbandingan langsung npd vs sap untuk keempat tabel.
     *
     * @return array<int,array>
     */
    public function comparison(): array
    {
        return self::mergeComparison($this->fetchStats('npd'), $this->fetchStats('sap'));
    }

    /**
     * Gabungkan statistik dua sumber menjadi baris perbandingan (murni, tanpa DB).
     *
     * @return array<int,array{table_code:string,table_name:string,npd_rows:?int,npd_updated:?string,npd_error:?string,sap_rows:?int,sap_updated:?string,sap_error:?string,delta:?int,status:string}>
     */
    public static function mergeComparison(array $npdStats, array $sapStats): array
    {
        $rows = [];

        foreach (self::TABLES as $code => $name) {
            $n = $npdStats[$code] ?? ['rows' => null, 'last_updated' => null, 'error' => 'Tidak tersedia.'];
            $s = $sapStats[$code] ?? ['rows' => null, 'last_updated' => null, 'error' => 'Tidak tersedia.'];

            $delta = null;
            if ($n['rows'] !== null && $s['rows'] !== null) {
                $delta = (int) $n['rows'] - (int) $s['rows'];
            }

            $rows[] = [
                'table_code'  => $code,
                'table_name'  => $name,
                'npd_rows'    => $n['rows'],
                'npd_updated' => $n['last_updated'],
                'npd_error'   => $n['error'],
                'sap_rows'    => $s['rows'],
                'sap_updated' => $s['last_updated'],
                'sap_error'   => $s['error'],
                'delta'       => $delta,
                'status'      => self::rowStatus($n, $s),
            ];
        }

        return $rows;
    }

    /**
     * Status perbandingan satu tabel (murni).
     *
     * MATCH  = jumlah baris sama
     * GAP    = jumlah baris berbeda
     * ERROR  = salah satu sumber gagal dibaca
     * UNKNOWN = salah satu sumber belum punya angka
     */
    public static function rowStatus(array $npd, array $sap): string
    {
        if (($npd['error'] ?? null) !== null || ($sap['error'] ?? null) !== null) {
            return 'ERROR';
        }

        if ($npd['rows'] === null || $sap['rows'] === null) {
            return 'UNKNOWN';
        }

        return (int) $npd['rows'] === (int) $sap['rows'] ? 'MATCH' : 'GAP';
    }

    /**
     * Jalankan pemeriksaan untuk satu aplikasi dan catat hasilnya
     * ke sys_sync_logs (1 log per tabel, trigger MANUAL_UI).
     *
     * @return array{status:string,message:string,source:?string,logs_written:int,results:array}
     */
    public function runCheck(string $appCode): array
    {
        $source = $this->sourceForApp($appCode);

        if ($source === null) {
            return [
                'status'       => 'FAILED',
                'message'      => 'Aplikasi ini bukan sumber database read-only.',
                'source'       => null,
                'logs_written' => 0,
                'results'      => [],
            ];
        }

        $stats  = $this->fetchStats($source);
        $codes  = $this->taskCodesFor($appCode);
        $logged = $this->writeLogs($stats, $codes);

        $total   = count($stats);
        $okCount = count(array_filter($stats, static fn (array $row) => $row['error'] === null));
        $label   = self::SOURCE_LABELS[$source] ?? $source;

        if ($okCount === $total) {
            $status  = 'SUCCESS';
            $message = "Pemeriksaan selesai: $total tabel terbaca dari $label.";
        } elseif ($okCount > 0) {
            $status  = 'WARNING';
            $message = "Pemeriksaan sebagian: $okCount/$total tabel terbaca dari $label.";
        } else {
            $status  = 'FAILED';
            $first   = reset($stats)['error'] ?? 'Koneksi gagal.';
            $message = "Pemeriksaan gagal di $label: $first";
        }

        return [
            'status'       => $status,
            'message'      => $message,
            'source'       => $source,
            'logs_written' => $logged,
            'results'      => array_values($stats),
        ];
    }

    /**
     * Baca satu tabel: COUNT(*) + MAX(updated_at) jika kolomnya ada.
     */
    private function readTable($db, array $row): array
    {
        $start = microtime(true);
        $code  = $row['table_code'];

        if (! $db->tableExists($code)) {
            $row['error']    = "Tabel $code tidak ditemukan di database sumber.";
            $row['duration'] = round(microtime(true) - $start, 2);

            return $row;
        }

        $fields = $db->getFieldNames($code);
        $rows   = (int) $db->table($code)->countAllResults();

        $lastUpdated = null;
        if (in_array('updated_at', $fields, true)) {
            $max  = $db->table($code)->selectMax('updated_at', 'mu')->get()->getRow();
            $lastUpdated = $max->mu ?? null;
        }

        $row['rows']         = $rows;
        $row['last_updated'] = $lastUpdated;
        $row['duration']     = round(microtime(true) - $start, 2);

        return $row;
    }

    /**
     * Peta table_code => sync_task_code untuk aplikasi (dari monitoring_app_tables).
     *
     * @return array<string,string>
     */
    private function taskCodesFor(string $appCode): array
    {
        $rows = db_connect()
            ->table('monitoring_app_tables')
            ->select('monitoring_app_tables.table_code, monitoring_app_tables.sync_task_code')
            ->join('monitoring_apps', 'monitoring_apps.id = monitoring_app_tables.monitoring_app_id')
            ->where('monitoring_apps.app_code', $appCode)
            ->where('monitoring_app_tables.is_active', 1)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $code = (string) ($row['sync_task_code'] ?? '');
            if ($code !== '') {
                $map[$row['table_code']] = $code;
            }
        }

        return $map;
    }

    /**
     * Tulis 1 log per tabel yang punya sync_task_code.
     *
     * @param array<string,array> $stats
     * @param array<string,string> $taskCodes
     */
    private function writeLogs(array $stats, array $taskCodes): int
    {
        if ($taskCodes === []) {
            return 0;
        }

        $logModel = new SysSyncLogModel();
        $now      = date('Y-m-d H:i:s');
        $written  = 0;

        foreach ($stats as $tableCode => $row) {
            if (! isset($taskCodes[$tableCode])) {
                continue;
            }

            $failed = $row['error'] !== null;

            $logModel->insert([
                'task_code'       => $taskCodes[$tableCode],
                'trigger_type'    => 'MANUAL_UI',
                'status'          => $failed ? 'FAILED' : 'SUCCESS',
                'step_failed'     => $failed ? 'FETCH_GET' : 'NONE',
                'records_read'    => $row['rows'] ?? 0,
                'records_written' => 0,
                'duration_sec'    => $row['duration'],
                'error_message'   => $row['error'],
                'executed_at'     => $now,
                'finished_at'     => $now,
            ]);

            $written++;
        }

        return $written;
    }

    /**
     * @param array<string,array> $stats
     * @return array<string,array>
     */
    private function markAllFailed(array $stats, string $message): array
    {
        foreach ($stats as $code => $row) {
            $stats[$code]['error'] = $message;
        }

        return $stats;
    }

    private function shortError(string $message): string
    {
        $message = preg_replace('/\s+/', ' ', trim($message));

        return mb_strlen($message) > 180
            ? mb_substr($message, 0, 180) . '…'
            : $message;
    }
}
