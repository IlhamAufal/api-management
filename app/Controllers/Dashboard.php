<?php

namespace App\Controllers;

use App\Libraries\Monitoring\AnalyticsService;
use App\Models\SourceModel;
use App\Models\WatchedTableModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $analytics = new AnalyticsService();

        return view('dashboard/index', [
            'title'           => 'Dashboard | MD-Bridge',
            'page'            => 'dashboard',
            'sources'         => (new SourceModel())->getActiveSources(),
            'tables'          => (new WatchedTableModel())->getActiveTables(),
            'statusBreakdown' => $analytics->statusBreakdown(),
            'trend'           => $analytics->trend(7),
            'sourceHealth'    => $analytics->sourceHealth(),
        ]);
    }
}
