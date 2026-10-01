<?php

namespace App\Libraries\Sync\Transform;

/**
 * Base transform: mapping field longgar (kandidat path), casting,
 * pemotongan sesuai panjang kolom, validasi primary key, dan raw_payload.
 *
 * Subclass cukup mendefinisikan mapping(), primaryKey(), serta opsi
 * defaults()/decimals()/lengths().
 */
abstract class AbstractTransformer implements TransformerInterface
{
    private const MAX_ERROR_SAMPLES = 10;

    /**
     * column => list of candidate key paths pada source row.
     * Path bertitik didukung untuk nav-property OData (mis. to_Description.MaterialDescription).
     */
    abstract protected function mapping(array $task): array;

    /** Kolom yang wajib terisi (tanpa itu baris dilewati). */
    abstract protected function primaryKey(array $task): array;

    /** Nilai default untuk kolom tertentu sebelum mapping. */
    protected function defaults(array $task): array
    {
        return [];
    }

    /** column => jumlah desimal (casting numerik). */
    protected function decimals(): array
    {
        return [];
    }

    /** column => panjang maksimum karakter (dipotong bila lebih). */
    protected function lengths(): array
    {
        return [];
    }

    public function transform(array $rows, array $task): TransformResult
    {
        $mapping   = $this->mapping($task);
        $primary   = $this->primaryKey($task);
        $defaults  = $this->defaults($task);
        $decimals  = $this->decimals();
        $lengths   = $this->lengths();

        $prepared = [];
        $skipped  = 0;
        $errors   = [];

        foreach (array_values($rows) as $index => $source) {
            if (!is_array($source)) {
                $skipped++;
                $this->addError($errors, 'baris #' . ($index + 1) . ': bentuk data bukan objek.');
                continue;
            }

            $row = $defaults;

            foreach ($mapping as $column => $paths) {
                $value = $this->extract($source, $paths);

                if ($value === null) {
                    continue;
                }

                $row[$column] = $this->cast($column, $value, $decimals, $lengths);
            }

            $missing = [];
            foreach ($primary as $column) {
                if (!isset($row[$column]) || $row[$column] === '') {
                    $missing[] = $column;
                }
            }

            if ($missing !== []) {
                $skipped++;
                $this->addError(
                    $errors,
                    'baris #' . ($index + 1) . ': primary key kosong (' . implode(', ', $missing) . ').'
                );
                continue;
            }

            $row['raw_payload'] = json_encode($source, JSON_UNESCAPED_UNICODE);
            $prepared[]         = $row;
        }

        return new TransformResult($prepared, $skipped, $errors);
    }

    /** Ambil nilai pertama yang ditemukan dari daftar kandidat path. */
    private function extract(array $source, array $paths)
    {
        foreach ($paths as $path) {
            $current = $source;

            foreach (explode('.', (string) $path) as $segment) {
                if (!is_array($current) || !array_key_exists($segment, $current)) {
                    $current = null;
                    break;
                }
                $current = $current[$segment];
            }

            if ($current === null) {
                continue;
            }

            if (is_scalar($current)) {
                return $current;
            }
        }

        return null;
    }

    private function cast(string $column, $value, array $decimals, array $lengths)
    {
        if ($column === 'is_active') {
            return $this->toBool($value) ? 1 : 0;
        }

        if (isset($decimals[$column])) {
            return round((float) $value, $decimals[$column]);
        }

        $string = trim((string) $value);

        if (isset($lengths[$column])) {
            $string = mb_substr($string, 0, $lengths[$column]);
        }

        if ($column === 'currency' || $column === 'country') {
            $string = strtoupper($string);
        }

        return $string;
    }

    private function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtoupper(trim((string) $value));

        return in_array($normalized, ['1', 'X', 'Y', 'TRUE', 'ACTIVE'], true);
    }

    private function addError(array &$errors, string $message): void
    {
        if (count($errors) < self::MAX_ERROR_SAMPLES) {
            $errors[] = $message;
        }
    }
}
