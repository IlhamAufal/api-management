<?php

namespace App\Libraries\Sync\Transform;

/**
 * Hasil transform: baris siap-insert + jumlah baris dilewati + alasan.
 */
class TransformResult
{
    private array $rows;

    private int $skipped;

    private array $errors;

    public function __construct(array $rows, int $skipped = 0, array $errors = [])
    {
        $this->rows    = $rows;
        $this->skipped = $skipped;
        $this->errors  = $errors;
    }

    public function rows(): array
    {
        return $this->rows;
    }

    public function skipped(): int
    {
        return $this->skipped;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
