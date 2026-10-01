<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        if (session()->get('auth_logged_in') === true) {
            return redirect()->to(base_url('/'));
        }

        return view('auth/login', [
            'title' => 'Sign In | MD-Bridge',
        ]);
    }

    public function attempt()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');
        $validation = service('validation');
        $validation->setRules([
            'email' => [
                'label' => 'Email',
                'rules' => 'required|valid_email|max_length[190]',
            ],
            'password' => [
                'label' => 'Password',
                'rules' => 'required|max_length[255]',
            ],
        ]);

        if (!$validation->run(['email' => $email, 'password' => $password])) {
            return redirect()->to(base_url('login'))
                ->with('auth_errors', $validation->getErrors())
                ->with('login_email', $email);
        }

        $userModel = new UserModel();
        $user = $userModel->findActiveByEmail($email);

        if ($user === null || !password_verify($password, $user['password_hash'])) {
            return redirect()->to(base_url('login'))
                ->with('auth_error', 'Email atau password tidak valid.')
                ->with('login_email', $email);
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $userModel->update($user['id'], [
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $userModel->update($user['id'], ['last_login_at' => $now]);

        $session = session();
        $session->regenerate(true);
        $session->set([
            'auth_logged_in' => true,
            'auth_user_id'   => (int) $user['id'],
            'auth_user_name' => $user['name'],
            'auth_user_email'=> $user['email'],
            'auth_login_at'  => $now,
        ]);

        return redirect()->to(base_url('/'));
    }

    public function logout()
    {
        $session = session();
        foreach ([
            'auth_logged_in',
            'auth_user_id',
            'auth_user_name',
            'auth_user_email',
            'auth_login_at',
        ] as $key) {
            $session->remove($key);
        }
        $session->regenerate(true);
        $session->setFlashdata('auth_success', 'Anda berhasil logout.');

        return redirect()->to(base_url('login'));
    }
}
