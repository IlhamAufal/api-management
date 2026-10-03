<?php

namespace App\Libraries\Monitoring;

use App\Models\SourceModel;
use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\Config\Services;
use CodeIgniter\Database\BaseConnection;
use Throwable;

/**
 * Introspeksi schema source aktif (union tabel & kolom) untuk
 * registry UI — pengganti hardcode information_schema.
 *
 * Hasil introspeksi di-cache singkat (CACHE_TTL) karena tiap
 * listTables/listColumns = koneksi remote (npd/sap ~0.1–0.3s).
 * Cache di-flush saat source berubah lewat UI (daftar union).
 */
class SourceIntrospector
{
    /** TTL cache introspeksi (detik) */
    public const CACHE_TTL = 60;

    private const CACHE_INDEX = 'intro_index';
    /** @var callable|null fn(string $code): BaseConnection */
    private $sourceResolver;

    /** @var array<string,BaseConnection>|null cache per sesi */
    private ?array $connections = null;

    public function __construct(?callable $sourceResolver = null)
    {
        $this->sourceResolver = $sourceResolver;
    }

    /**
     * Koneksi ke setiap source aktif yang credential-nya terisi.
     * Source yang gagal di-skip (bukan error) supaya UI tetap jalan
     * saat salah satu source down.
     *
     * @return array<string,BaseConnection> code => koneksi
     */
    public function activeConnections(): array
    {
        if ($this->connections !== null) {
            return $this->connections;
        }

        $this->connections = [];

        foreach ((new SourceModel())->getActiveSources() as $source) {
            $code = (string) $source['code'];

            try {
                if ($this->sourceResolver !== null) {
                    $this->connections[$code] = ($this->sourceResolver)($code);
                    continue;
                }

                if (! TableFreshnessChecker::isConfiguredGroup(config('Database')->{$code} ?? null)) {
                    continue;
                }

                $db = db_connect($code);
                $db->query('SELECT 1');
                $this->connections[$code] = $db;
            } catch (Throwable $e) {
                // Source tidak tersedia — lewati, jangan gagalkan seluruh UI.
            }
        }

        return $this->connections;
    }

    /**
     * Union nama tabel, terurut & tanpa duplikat.
     *
     * @param string|null $sourceCode bila diisi, hanya source tersebut
     *        yang diintrospeksi (filter UX di form registrasi —
     *        skema watched_table tetap lintas source)
     *
     * @return string[]
     */
    public function listTables(?string $sourceCode = null): array
    {
        $key    = $this->cacheKey('tables', $sourceCode);
        $cached = $this->cache()->get($key);

        if (is_array($cached)) {
            return $cached;
        }

        $tables = [];

        foreach ($this->scopedConnections($sourceCode) as $db) {
            foreach ($db->listTables() as $name) {
                $tables[$name] = true;
            }
        }

        $names = array_keys($tables);
        sort($names);

        $this->remember($key, $names);

        return $names;
    }

    /**
     * Kolom sebuah tabel, diambil dari source pertama yang memilikinya.
     *
     * @param string|null $sourceCode bila diisi, hanya source itu yang dicek
     *
     * @return string[]|null null bila tabel tidak ada di lingkup source
     */
    public function listColumns(string $table, ?string $sourceCode = null): ?array
    {
        if (! TableFreshnessChecker::isValidIdentifier($table)) {
            return null;
        }

        $key    = $this->cacheKey('cols:' . $table, $sourceCode);
        $cached = $this->cache()->get($key);

        if (is_array($cached)) {
            return $cached;
        }

        foreach ($this->scopedConnections($sourceCode) as $db) {
            if ($db->tableExists($table)) {
                $fields = $db->getFieldNames($table);
                $fields = is_array($fields) ? $fields : null;

                if ($fields !== null) {
                    $this->remember($key, $fields);
                }

                return $fields;
            }
        }

        return null;
    }

    /**
     * Buang seluruh cache introspeksi (dipanggil saat source
     * ditambah/diubah/di-toggle/dihapus — daftar union bisa berubah).
     */
    public static function flushCache(): void
    {
        $cache = self::serviceCache();
        $index = $cache->get(self::CACHE_INDEX);

        if (is_array($index)) {
            foreach ($index as $key) {
                $cache->delete((string) $key);
            }
        }

        $cache->delete(self::CACHE_INDEX);
    }

    private static function serviceCache(): CacheInterface
    {
        return Services::cache();
    }

    private function cache(): CacheInterface
    {
        return self::serviceCache();
    }

    /**
     * Format key: titik sebagai pemisah — ":" terlarang di cache
     * key CI (reservedCharacters '{}()/\@:').
     */
    private function cacheKey(string $kind, ?string $sourceCode): string
    {
        $scope = ($sourceCode === null || $sourceCode === '') ? 'all' : $sourceCode;

        return 'intro_' . str_replace(':', '.', $kind) . '.' . $scope;
    }

    /**
     * Simpan + catat ke index supaya flushCache bisa menghapusnya.
     *
     * @param string[] $value
     */
    private function remember(string $key, array $value): void
    {
        $cache = $this->cache();
        $cache->save($key, $value, self::CACHE_TTL);

        $index = $cache->get(self::CACHE_INDEX);
        $index = is_array($index) ? $index : [];

        if (! in_array($key, $index, true)) {
            $index[] = $key;
        }

        $cache->save(self::CACHE_INDEX, $index, self::CACHE_TTL * 60);
    }

    /**
     * Koneksi aktif, dibatasi satu source bila $sourceCode diisi.
     * Kode tidak valid / tidak dikenal → lingkup kosong (union bila null/'').
     *
     * @return array<string,BaseConnection>
     */
    private function scopedConnections(?string $sourceCode): array
    {
        if ($sourceCode === null || $sourceCode === '') {
            return $this->activeConnections();
        }

        if (! TableFreshnessChecker::isValidIdentifier($sourceCode)) {
            return [];
        }

        $connections = $this->activeConnections();

        return isset($connections[$sourceCode])
            ? [$sourceCode => $connections[$sourceCode]]
            : [];
    }

    /**
     * Validasi registrasi watched_table:
     * - table_name harus ada di minimal satu source aktif
     * - sync_column harus ada di tabel itu pada minimal satu source
     *   yang juga memiliki tabel tersebut
     *
     * @return string|null pesan error, atau null bila valid
     */
    public function validateRegistration(string $table, string $syncColumn): ?string
    {
        if ($table === '' || ! TableFreshnessChecker::isValidIdentifier($table)) {
            return 'Nama tabel tidak valid.';
        }

        if ($syncColumn === '' || ! TableFreshnessChecker::isValidIdentifier($syncColumn)) {
            return 'Nama kolom sync tidak valid.';
        }

        $connections = $this->activeConnections();

        if ($connections === []) {
            return 'Tidak ada source aktif yang bisa diintrospeksi.';
        }

        $foundIn = [];
        foreach ($connections as $code => $db) {
            try {
                if ($db->tableExists($table)) {
                    $foundIn[$code] = $db;
                }
            } catch (Throwable $e) {
                // source bermasalah — abaikan untuk validasi ini
            }
        }

        if ($foundIn === []) {
            return 'Tabel "' . $table . '" tidak ditemukan di source aktif manapun.';
        }

        foreach ($foundIn as $code => $db) {
            $fields = $db->getFieldNames($table);
            if (is_array($fields) && in_array($syncColumn, $fields, true)) {
                return null;
            }
        }

        return 'Kolom "' . $syncColumn . '" tidak ada di tabel "' . $table . '" '
            . 'pada source: ' . implode(', ', array_keys($foundIn)) . '.';
    }
}
