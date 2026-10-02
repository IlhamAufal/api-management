<?php

namespace App\Libraries\Monitoring;

/**
 * Hasil satu kali check untuk pasangan (watched_table, source).
 * Nilai murni data — tanpa akses database.
 */
class CheckResult
{
    public const NEVER_SYNCED  = 'NEVER_SYNCED';
    public const STALE         = 'STALE';
    public const OK            = 'OK';
    public const MISSING_TABLE = 'MISSING_TABLE';
    public const CONN_ERROR    = 'CONN_ERROR';

    public const ALL_STATUSES = [
        self::NEVER_SYNCED,
        self::STALE,
        self::OK,
        self::MISSING_TABLE,
        self::CONN_ERROR,
    ];

    public string $status;
    public ?int $rowCount;
    public ?string $lastSyncedAt;
    public ?string $errorMessage;
    public string $checkedAt;
    public int $watchedTableId;
    public int $sourceId;

    public function __construct(
        string $status,
        int $watchedTableId,
        int $sourceId,
        string $checkedAt,
        ?int $rowCount = null,
        ?string $lastSyncedAt = null,
        ?string $errorMessage = null
    ) {
        $this->status          = $status;
        $this->watchedTableId  = $watchedTableId;
        $this->sourceId        = $sourceId;
        $this->checkedAt       = $checkedAt;
        $this->rowCount        = $rowCount;
        $this->lastSyncedAt    = $lastSyncedAt;
        $this->errorMessage    = $errorMessage;
    }

    public function isOk(): bool
    {
        return $this->status === self::OK;
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'status'           => $this->status,
            'watched_table_id' => $this->watchedTableId,
            'source_id'        => $this->sourceId,
            'row_count'        => $this->rowCount,
            'last_synced_at'   => $this->lastSyncedAt,
            'error_message'    => $this->errorMessage,
            'checked_at'       => $this->checkedAt,
        ];
    }
}
