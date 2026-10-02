<?php

namespace Tests\Support;

use App\Libraries\Monitoring\CheckResult;
use App\Libraries\Monitoring\TableFreshnessChecker;
use CodeIgniter\Database\BaseConnection;

/**
 * Checker untuk feature test: probe jalan sungguhan terhadap SQLite
 * tests (resolver disuntikkan), tapi persist ditulis dengan query
 * builder SQLite-compatible (tanpa ON DUPLICATE KEY UPDATE MySQL)
 * sambil mencatat pasangan (tabel, source) yang dicek.
 */
class FixtureFreshnessChecker extends TableFreshnessChecker
{
    /** @var string[] pasangan "watchedTableId:sourceId" yang sudah dicek */
    public array $checkedPairs = [];

    public function __construct(callable $resolver)
    {
        parent::__construct($resolver, db_connect());
    }

    protected function persist(CheckResult $result, string $triggerType): void
    {
        $this->checkedPairs[] = $result->watchedTableId . ':' . $result->sourceId;

        $db = $this->fixtureDb();

        $snapshot = [
            'row_count'      => $result->rowCount,
            'last_synced_at' => $result->lastSyncedAt,
            'status'         => $result->status,
            'error_message'  => $result->errorMessage,
            'checked_at'     => $result->checkedAt,
        ];

        $existing = $db->table('table_snapshots')
            ->where('watched_table_id', $result->watchedTableId)
            ->where('source_id', $result->sourceId)
            ->get()
            ->getRow();

        if ($existing !== null) {
            $db->table('table_snapshots')
                ->where('id', $existing->id)
                ->update($snapshot);
        } else {
            $db->table('table_snapshots')->insert($snapshot + [
                'watched_table_id' => $result->watchedTableId,
                'source_id'        => $result->sourceId,
            ]);
        }

        $db->table('check_history')->insert([
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

    private function fixtureDb(): BaseConnection
    {
        return db_connect();
    }
}
