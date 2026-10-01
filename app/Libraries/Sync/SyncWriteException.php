<?php

namespace App\Libraries\Sync;

use RuntimeException;

/**
 * Exception saat tahap penulisan (DB_INSERT) gagal.
 * Membawa jumlah baris yang sudah sempat tertulis (batch sebelum kegagalan).
 */
class SyncWriteException extends RuntimeException
{
    private int $written;

    public function __construct(string $message, int $written = 0)
    {
        parent::__construct($message);
        $this->written = $written;
    }

    public function getWritten(): int
    {
        return $this->written;
    }
}
