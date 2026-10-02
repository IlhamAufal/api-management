<?php

namespace App\Models;

use CodeIgniter\Model;

class TableSnapshotModel extends Model
{
    protected $table = 'table_snapshots';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'watched_table_id',
        'source_id',
        'row_count',
        'last_synced_at',
        'status',
        'error_message',
        'checked_at',
    ];

    protected $validationRules = [
        'watched_table_id' => 'required|is_natural_no_zero',
        'source_id'        => 'required|is_natural_no_zero',
        'row_count'        => 'permit_empty|is_natural',
        'last_synced_at'   => 'permit_empty',
        'status'           => 'required|in_list[NEVER_SYNCED,STALE,OK,MISSING_TABLE,CONN_ERROR]',
        'error_message'    => 'permit_empty|string',
        'checked_at'       => 'required',
    ];
}
