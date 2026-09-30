<?php

namespace App\Controllers;

use App\Models\MonitoringAppModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Monitoring extends BaseController
{
    public function index()
    {
        $appModel = new MonitoringAppModel();

        return view('pages/monitoring/index', [
            'title' => 'Monitoring Aplikasi | MD-Bridge',
            'page'  => 'monitoring',
            'apps'  => $appModel->getActiveAppsWithTableCount(),
        ]);
    }

    public function show($appCode)
    {
        $appModel = new MonitoringAppModel();
        $app = $appModel->findActiveByCode($appCode);

        if ($app === null) {
            throw PageNotFoundException::forPageNotFound('Aplikasi monitoring tidak ditemukan.');
        }

        return view('pages/monitoring/show', [
            'title'  => $app['app_name'] . ' | Monitoring MD-Bridge',
            'page'   => 'monitoring',
            'app'    => $app,
            'tables' => $appModel->getActiveTables((int) $app['id']),
        ]);
    }
}
