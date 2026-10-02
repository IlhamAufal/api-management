<?php

namespace App\Models;

use CodeIgniter\Model;

class WatchedTableModel extends Model
{
    protected $table = 'watched_tables';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'table_name',
        'label',
        'sync_column',
        'stale_after_minutes',
        'is_active',
    ];

    protected $validationRules = [
        'id'                 => 'permit_empty|is_natural_no_zero',
        'table_name'         => 'required|regex_match[/^[a-z][a-z0-9_]*$/]|max_length[100]|is_unique[watched_tables.table_name,id,{id}]',
        'label'              => 'required|string|max_length[150]',
        'sync_column'        => 'required|regex_match[/^[a-z][a-z0-9_]*$/]|max_length[100]',
        'stale_after_minutes' => 'required|is_natural_no_zero|max_length[10]',
        'is_active'          => 'required|in_list[0,1]',
    ];

    protected $validationMessages = [
        'table_name' => [
            'is_unique'   => 'Nama tabel sudah terdaftar sebagai watched table.',
            'regex_match' => 'Nama tabel hanya boleh huruf, angka, dan underscore.',
        ],
        'sync_column' => [
            'regex_match' => 'Nama kolom sync hanya boleh huruf, angka, dan underscore.',
        ],
        'stale_after_minutes' => [
            'is_natural_no_zero' => 'Stale after harus bilangan bulat lebih dari 0.',
        ],
    ];

    public function getActiveTables(): array
    {
        return $this->where('is_active', 1)
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function findByTableName(string $tableName): ?array
    {
        return $this->where('table_name', $tableName)->first();
    }
}
