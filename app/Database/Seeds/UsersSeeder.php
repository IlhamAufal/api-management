<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

class UsersSeeder extends Seeder
{
    public function run()
    {
        $name = trim((string) env('auth.seedName', 'MD-Bridge Administrator'));
        $email = strtolower(trim((string) env('auth.seedEmail', '')));
        $password = (string) env('auth.seedPassword', '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('auth.seedEmail wajib diisi dengan email yang valid sebelum menjalankan UsersSeeder.');
        }

        if (strlen($password) < 12) {
            throw new RuntimeException('auth.seedPassword wajib diisi minimal 12 karakter sebelum menjalankan UsersSeeder.');
        }

        $user = [
            'name'          => $name,
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'is_active'     => 1,
        ];
        $builder = $this->db->table('users');
        $existing = $builder->where('email', $email)->get()->getRowArray();

        if ($existing === null) {
            $builder->insert($user);
            return;
        }

        $builder->where('id', $existing['id'])->update($user);
    }
}
