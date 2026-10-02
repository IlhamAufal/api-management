<?php

namespace App\Libraries\Monitoring;

use App\Models\SourceModel;
use CodeIgniter\Database\BaseConnection;
use Throwable;

/**
 * Introspeksi schema source aktif (union tabel & kolom) untuk
 * registry UI — pengganti hardcode information_schema.
 */
class SourceIntrospector
{
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
        $tables = [];

        foreach ($this->scopedConnections($sourceCode) as $db) {
            foreach ($db->listTables() as $name) {
                $tables[$name] = true;
            }
        }

        $names = array_keys($tables);
        sort($names);

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

        foreach ($this->scopedConnections($sourceCode) as $db) {
            if ($db->tableExists($table)) {
                $fields = $db->getFieldNames($table);

                return is_array($fields) ? $fields : null;
            }
        }

        return null;
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
