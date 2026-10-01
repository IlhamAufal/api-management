<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'name',
        'email',
        'password_hash',
        'is_active',
        'last_login_at',
    ];

    public function findActiveByEmail($email)
    {
        return $this->where('email', strtolower(trim($email)))
            ->where('is_active', 1)
            ->first();
    }
}
