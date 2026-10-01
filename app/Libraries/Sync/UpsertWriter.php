<?php

namespace App\Libraries\Sync;

use CodeIgniter\Database\BaseConnection;
use RuntimeException;

/**
 * Penulisan batch ke tabel target memakai
 * INSERT ... ON DUPLICATE KEY UPDATE (berbasis unique key tiap tabel).
 *
 * Catatan: CodeIgniter 4.1.9 belum punya Builder::upsert(), jadi SQL
 * dirakit manual dengan binding parameter.
 */
class UpsertWriter
{
    /**
     * Whitelist tabel target: unique key (pk) + kolom yang boleh ditulis.
     * created_at / updated_at sengaja tidak disertakan (default & ON UPDATE).
     */
    private const TABLES = [
        'sap_material_master' => [
            'pk'      => ['material_number'],
            'columns' => [
                'material_number', 'material_type', 'material_description', 'base_unit',
                'material_group', 'gross_weight', 'net_weight', 'weight_unit',
                'standard_price', 'currency', 'raw_payload',
            ],
        ],
        'sap_customer_master' => [
            'pk'      => ['customer_code'],
            'columns' => [
                'customer_code', 'customer_name', 'business_partner_group', 'address_line',
                'city', 'postal_code', 'country', 'tax_number', 'is_active', 'raw_payload',
            ],
        ],
        'sap_customer_material' => [
            'pk'      => ['customer_code', 'material_number'],
            'columns' => [
                'customer_code', 'material_number', 'customer_material_number',
                'customer_material_desc', 'raw_payload',
            ],
        ],
        'sap_customer_sales_area' => [
            'pk'      => ['customer_code', 'sales_organization', 'distribution_channel', 'division'],
            'columns' => [
                'customer_code', 'sales_organization', 'distribution_channel', 'division',
                'sales_office', 'currency', 'payment_terms', 'raw_payload',
            ],
        ],
    ];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    public static function columnsFor(string $table): ?array
    {
        return self::TABLES[$table]['columns'] ?? null;
    }

    public static function primaryKeyFor(string $table): ?array
    {
        return self::TABLES[$table]['pk'] ?? null;
    }

    public static function isSupported(string $table): bool
    {
        return isset(self::TABLES[$table]);
    }

    /**
     * Upsert batch. Mengembalikan jumlah baris yang ditulis; bila tengah gagal,
     * SyncWriteException membawa jumlah baris dari batch yang sudah sukses.
     *
     * @param int $batchSize jumlah baris per statement
     */
    public function write(string $table, array $rows, int $batchSize): int
    {
        $columns = self::columnsFor($table);

        if ($columns === null) {
            throw new SyncWriteException('Tabel target tidak didukung: "' . $table . '".', 0);
        }

        if ($rows === []) {
            return 0;
        }

        $batchSize = max(1, $batchSize);
        $written   = 0;

        foreach (array_chunk($rows, $batchSize) as $chunk) {
            $normalized = [];

            foreach ($chunk as $row) {
                $clean = [];
                foreach ($columns as $column) {
                    $clean[$column] = $row[$column] ?? null;
                }
                $normalized[] = $clean;
            }

            [$sql, $binds] = self::buildUpsert($table, $columns, $normalized);

            try {
                $this->db->query($sql, $binds);
            } catch (\Throwable $exception) {
                throw new SyncWriteException(
                    'Insert ke ' . $table . ' gagal: ' . $exception->getMessage(),
                    $written
                );
            }

            $written += count($normalized);
        }

        return $written;
    }

    /**
     * Bangun SQL upsert + binding (static agar bisa diuji tanpa database).
     *
     * @return array{0:string,1:array} [sql, binds]
     */
    public static function buildUpsert(string $table, array $columns, array $rows): array
    {
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $table)) {
            throw new RuntimeException('Nama tabel tidak valid: "' . $table . '".');
        }

        foreach ($columns as $column) {
            if (!preg_match('/^[a-z][a-z0-9_]*$/', $column)) {
                throw new RuntimeException('Nama kolom tidak valid: "' . $column . '".');
            }
        }

        if ($columns === [] || $rows === []) {
            throw new RuntimeException('Kolom atau baris kosong untuk upsert.');
        }

        $quotedColumns = array_map(static function ($column) {
            return '`' . $column . '`';
        }, $columns);

        $placeholder = implode(', ', array_fill(0, count($columns), '?'));
        $valueGroups = [];
        $binds       = [];

        foreach ($rows as $row) {
            $valueGroups[] = '(' . $placeholder . ')';

            foreach ($columns as $column) {
                $binds[] = $row[$column] ?? null;
            }
        }

        $updates = [];
        foreach ($columns as $column) {
            $updates[] = '`' . $column . '` = VALUES(`' . $column . '`)';
        }

        $sql = 'INSERT INTO `' . $table . '` (' . implode(', ', $quotedColumns) . ')'
            . ' VALUES ' . implode(', ', $valueGroups)
            . ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);

        return [$sql, $binds];
    }
}
