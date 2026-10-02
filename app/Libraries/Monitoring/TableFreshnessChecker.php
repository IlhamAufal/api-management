<?php

namespace App\Libraries\Monitoring;

use App\Models\SourceModel;
use App\Models\WatchedTableModel;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

/**
 * Checker generik data-freshness per pasangan (watched_table, source).
 *
 * Pengganti HistoryCheckService: tanpa logika khusus SAP, tanpa
 * perbandingan row_count antar source. Status murni dari umur
 * `sync_column` dibanding `stale_after_minutes` (lihat plan bagian 5).
 *
 * Urutan status: PENDING_CONFIG (langkah 0) → CONN_ERROR → MISSING_TABLE
 * → NEVER_SYNCED → STALE → OK. Lihat plan bagian 8.3 untuk PENDING_CONFIG.
 */
class TableFreshnessChecker
{
    public const TRIGGER_MANUAL = 'MANUAL_UI';
    public const TRIGGER_CRON   = 'CRON';

    /** @var callable|null fn(string $code): BaseConnection */
    private $sourceResolver;

    /** @var callable|null fn(): string — jam yang bisa dipalsukan untuk test */
    private $clock;

    private BaseConnection $local;

    public function __construct(
        ?callable $sourceResolver = null,
        ?BaseConnection $local = null,
        ?callable $clock = null
    ) {
        $this->sourceResolver = $sourceResolver;
        $this->local          = $local ?? db_connect();
        $this->clock          = $clock;
    }

    /**
     * Jalankan check untuk satu pasangan (tabel, source),
     * lalu persist ke table_snapshots (upsert) + check_history (append).
     */
    public function check(array $table, array $source, string $triggerType = self::TRIGGER_MANUAL): CheckResult
    {
        $checkedAt = $this->now();

        // Langkah 0 (plan bagian 8.3): connection group belum terdaftar di
        // Config/Database.php → PENDING_CONFIG. Gagal cepat: murni baca
        // konfigurasi, tanpa db_connect() sama sekali (tanpa timeout jaringan).
        if (! self::hasConnectionGroup((string) $source['code'])) {
            $result = new CheckResult(
                CheckResult::PENDING_CONFIG,
                (int) $table['id'],
                (int) $source['id'],
                $checkedAt,
                null,
                null,
                'Harap tambahkan credential terlebih dahulu.'
            );
            $this->persist($result, $triggerType);

            return $result;
        }

        $status        = null;
        $rowCount      = null;
        $lastSyncedAt  = null;
        $errorMessage  = null;

        try {
            $db     = $this->resolveSourceConnection((string) $source['code']);
            $probe  = $this->probe($db, $table, $source);
            $status = $probe['status'];
            $rowCount     = $probe['row_count'];
            $lastSyncedAt = $probe['last_synced_at'];
            $errorMessage = $probe['error_message'];
        } catch (Throwable $e) {
            $status       = CheckResult::CONN_ERROR;
            $errorMessage = self::shortError($e->getMessage());
        }

        if ($status === null) {
            $status = self::resolveStatus(
                $lastSyncedAt,
                $checkedAt,
                (int) $table['stale_after_minutes']
            );
        }

        $result = new CheckResult(
            $status,
            (int) $table['id'],
            (int) $source['id'],
            $checkedAt,
            $rowCount,
            $lastSyncedAt,
            $errorMessage
        );

        $this->persist($result, $triggerType);

        return $result;
    }

    /**
     * Check satu tabel terhadap semua source aktif.
     *
     * @return CheckResult[]
     */
    public function checkTable(array $table, string $triggerType = self::TRIGGER_MANUAL): array
    {
        return $this->runFor([$table], (new SourceModel())->getActiveSources(), $triggerType);
    }

    /**
     * Check semua kombinasi watched_table aktif × source aktif ("Check All").
     *
     * @return CheckResult[]
     */
    public function checkAll(string $triggerType = self::TRIGGER_MANUAL): array
    {
        return $this->runFor(
            (new WatchedTableModel())->getActiveTables(),
            (new SourceModel())->getActiveSources(),
            $triggerType
        );
    }

    /**
     * Loop kombinasi tabel × source. Public agar bisa diuji tanpa
     * bergantung pada isi tabel registry (pakai array fixture).
     *
     * @return CheckResult[]
     */
    public function runFor(array $tables, array $sources, string $triggerType = self::TRIGGER_MANUAL): array
    {
        $results = [];
        foreach ($tables as $table) {
            foreach ($sources as $source) {
                $results[] = $this->check($table, $source, $triggerType);
            }
        }

        return $results;
    }

    /**
     * Ambil koneksi source: resolver yang disuntikkan (untuk test) atau
     * koneksi default berbasis Config/Database.php.
     */
    protected function resolveSourceConnection(string $code): BaseConnection
    {
        if ($this->sourceResolver !== null) {
            return ($this->sourceResolver)($code);
        }

        return $this->connectSource($code);
    }

    /**
     * Koneksi ke source: validasi credential di config dulu, lalu ping.
     * Sengaja memakai db_connect($code) — group yang tidak dikenal TIDAK
     * boleh diam-diam jatuh ke koneksi default.
     */
    protected function connectSource(string $code): BaseConnection
    {
        if (! self::isConfiguredGroup(config('Database')->{$code} ?? null)) {
            throw new RuntimeException(
                'Kredensial belum diisi di .env (database.' . $code . '.*).'
            );
        }

        $db = db_connect($code);
        $db->query('SELECT 1');

        return $db;
    }

    /**
     * Introspeksi tabel/kolom + baca COUNT/MAX(sync_column).
     * Nama tabel/kolom divalidasi dan di-escape lewat query builder
     * (protectIdentifiers) — bukan string concatenation mentah.
     *
     * @return array{status:?string,row_count:?int,last_synced_at:?string,error_message:?string}
     *         status = null bila probe sukses (status final menyusul).
     */
    protected function probe(BaseConnection $db, array $table, array $source): array
    {
        $missing = static function (?string $message): array {
            return [
                'status'          => CheckResult::MISSING_TABLE,
                'row_count'       => null,
                'last_synced_at'  => null,
                'error_message'   => $message,
            ];
        };

        $tableName  = (string) $table['table_name'];
        $syncColumn = (string) $table['sync_column'];
        $sourceCode = (string) $source['code'];

        if (! self::isValidIdentifier($tableName) || ! self::isValidIdentifier($syncColumn)) {
            return $missing('Nama tabel/kolom tidak valid: "' . $tableName . '.' . $syncColumn . '".');
        }

        if (! $db->tableExists($tableName)) {
            return $missing('Tabel "' . $tableName . '" tidak ditemukan di source "' . $sourceCode . '".');
        }

        $fields = $db->getFieldNames($tableName);
        if (! is_array($fields) || ! in_array($syncColumn, $fields, true)) {
            return $missing(
                'Kolom "' . $syncColumn . '" tidak ada di tabel "' . $tableName . '" (source "' . $sourceCode . '").'
            );
        }

        $row = $db->table($tableName)
            ->select('COUNT(*) AS cnt', false)
            ->selectMax($syncColumn, 'last_sync')
            ->get()
            ->getRow();

        return [
            'status'         => null,
            'row_count'      => (int) ($row->cnt ?? 0),
            'last_synced_at' => $row->last_sync ?? null,
            'error_message'  => null,
        ];
    }

    /**
     * Status final murni dari umur data (tanpa membandingkan antar source).
     */
    public static function resolveStatus(?string $lastSyncedAt, string $checkedAt, int $staleAfterMinutes): string
    {
        if ($lastSyncedAt === null || $lastSyncedAt === '') {
            return CheckResult::NEVER_SYNCED;
        }

        $last  = strtotime($lastSyncedAt);
        $now   = strtotime($checkedAt);
        $age   = ($last !== false && $now !== false) ? ($now - $last) : PHP_INT_MAX;

        return $age > $staleAfterMinutes * 60
            ? CheckResult::STALE
            : CheckResult::OK;
    }

    /**
     * Langkah 0 (plan bagian 8.3): apakah Config/Database.php punya
     * connection group dengan nama persis $code? Murni baca konfigurasi —
     * tidak melakukan percobaan koneksi jaringan.
     */
    public static function hasConnectionGroup(string $code): bool
    {
        if ($code === '' || ! self::isValidIdentifier($code)) {
            return false;
        }

        $config = config('Database');

        return property_exists($config, $code) && $config->{$code} !== null;
    }

    /**
     * Apakah group config sudah terisi credential-nya?
     * (hostname & username wajib; kalau belum, jangan coba connect.)
     */
    public static function isConfiguredGroup($group): bool
    {
        if (! is_array($group)) {
            return false;
        }

        return trim((string) ($group['hostname'] ?? '')) !== ''
            && trim((string) ($group['username'] ?? '')) !== '';
    }

    /**
     * SQL upsert snapshot (MySQL ON DUPLICATE KEY UPDATE).
     * CodeIgniter 4.1.9 belum punya Builder::upsert() — dirakit manual
     * dengan binding parameter (pola UpsertWriter).
     *
     * @return array{0:string,1:array} [sql, binds] binds diisi saat runtime
     */
    public static function buildSnapshotUpsert(): string
    {
        return 'INSERT INTO `table_snapshots`'
            . ' (`watched_table_id`, `source_id`, `row_count`, `last_synced_at`, `status`, `error_message`, `checked_at`)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?)'
            . ' ON DUPLICATE KEY UPDATE'
            . ' `row_count` = VALUES(`row_count`),'
            . ' `last_synced_at` = VALUES(`last_synced_at`),'
            . ' `status` = VALUES(`status`),'
            . ' `error_message` = VALUES(`error_message`),'
            . ' `checked_at` = VALUES(`checked_at`)';
    }

    public static function isValidIdentifier(string $name): bool
    {
        return preg_match('/^[a-z][a-z0-9_]*$/i', $name) === 1;
    }

    /**
     * Pesan error aman: rapikan whitespace, potong 180 char, dan
     * sembunyikan potensi credential.
     */
    public static function shortError(string $message): string
    {
        $message = preg_replace('/\s+/', ' ', trim($message));
        $message = preg_replace(
            '/((?:pass(?:word|wd)?|pwd)\s*[:=]\s*)\S+/i',
            '$1[redacted]',
            $message
        );

        return mb_strlen($message) > 180
            ? mb_substr($message, 0, 180) . '…'
            : $message;
    }

    protected function now(): string
    {
        return $this->clock !== null
            ? ($this->clock)()
            : date('Y-m-d H:i:s');
    }

    /**
     * Tulis snapshot (upsert) + riwayat (append).
     * Dipisah protected supaya bisa dioverride di test tanpa database.
     */
    protected function persist(CheckResult $result, string $triggerType): void
    {
        $sql = self::buildSnapshotUpsert();
        $this->local->query($sql, [
            $result->watchedTableId,
            $result->sourceId,
            $result->rowCount,
            $result->lastSyncedAt,
            $result->status,
            $result->errorMessage,
            $result->checkedAt,
        ]);

        $this->local->table('check_history')->insert([
            'watched_table_id' => $result->watchedTableId,
            'source_id'        => $result->sourceId,
            'trigger_type'     => $triggerType,
            'row_count'        => $result->rowCount,
            'last_synced_at'   => $result->lastSyncedAt,
            'status'           => $result->status,
            'error_message'    => $result->errorMessage,
            'executed_at'      => $result->checkedAt,
        ]);
    }
}
