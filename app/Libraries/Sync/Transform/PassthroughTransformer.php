<?php

namespace App\Libraries\Sync\Transform;

use App\Libraries\Sync\UpsertWriter;
use RuntimeException;

/**
 * Fallback untuk target_table yang belum punya transformer khusus:
 * kolom disalin apa adanya (sesuai whitelist tabel), primary key wajib terisi.
 */
class PassthroughTransformer extends AbstractTransformer
{
    protected function mapping(array $task): array
    {
        $table = (string) ($task['target_table'] ?? '');
        $columns = UpsertWriter::columnsFor($table);

        if ($columns === null) {
            throw new RuntimeException('Tabel target tidak didukung: "' . $table . '".');
        }

        $mapping = [];
        foreach ($columns as $column) {
            if ($column !== 'raw_payload') {
                $mapping[$column] = [$column];
            }
        }

        return $mapping;
    }

    protected function primaryKey(array $task): array
    {
        $table = (string) ($task['target_table'] ?? '');
        $pk = UpsertWriter::primaryKeyFor($table);

        if ($pk === null) {
            throw new RuntimeException('Tabel target tidak didukung: "' . $table . '".');
        }

        return $pk;
    }
}
