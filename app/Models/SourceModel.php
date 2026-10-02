<?php

namespace App\Models;

use CodeIgniter\Model;

class SourceModel extends Model
{
    protected $table = 'sources';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'code',
        'label',
        'is_active',
    ];

    protected $validationRules = [
        'id'       => 'permit_empty|is_natural_no_zero',
        'code'     => 'required|regex_match[/^[a-z][a-z0-9_]*$/]|max_length[50]|is_unique[sources.code,id,{id}]',
        'label'    => 'required|string|max_length[100]',
        'is_active' => 'required|in_list[0,1]',
    ];

    protected $validationMessages = [
        'code' => [
            'is_unique'     => 'Kode source sudah dipakai.',
            'regex_match'   => 'Kode source hanya boleh huruf kecil, angka, dan underscore.',
        ],
    ];

    public function getActiveSources(): array
    {
        return $this->where('is_active', 1)
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function findByCode(string $code): ?array
    {
        return $this->where('code', $code)->first();
    }
}
