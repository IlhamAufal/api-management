<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session()->get('auth_logged_in') === true) {
            return null;
        }

        if (strpos($request->getHeaderLine('Accept'), 'application/json') !== false) {
            return Services::response()
                ->setStatusCode(401)
                ->setJSON([
                    'status'  => 'UNAUTHENTICATED',
                    'message' => 'Sesi berakhir. Silakan login kembali.',
                ]);
        }

        return redirect()->to(base_url('login'))
            ->with('auth_error', 'Silakan login untuk mengakses MD-Bridge.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
