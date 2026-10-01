<?php

namespace App\Controllers;

use App\Models\ApiSyncTaskModel;
use App\Models\MonitoringAppModel;
use App\Models\SysSyncLogModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $logModel = new SysSyncLogModel();
        $summary = $logModel->getDashboardSummary();
        $dailyRows = $logModel->getDailyStats(7);
        $dailyByDate = [];

        foreach ($dailyRows as $row) {
            $dailyByDate[$row['run_date']] = $row;
        }

        $dailyStats = [];
        for ($daysAgo = 6; $daysAgo >= 0; $daysAgo--) {
            $date = date('Y-m-d', strtotime('-' . $daysAgo . ' days'));
            $row = $dailyByDate[$date] ?? [];
            $dailyStats[] = [
                'date'    => $date,
                'label'   => date('D', strtotime($date)),
                'success' => (int) ($row['success_count'] ?? 0),
                'failed'  => (int) ($row['failed_count'] ?? 0),
                'warning' => (int) ($row['warning_count'] ?? 0),
                'total'   => (int) ($row['total_count'] ?? 0),
            ];
        }

        $completedRuns = (int) $summary['completed_runs'];
        $successfulRuns = (int) $summary['successful_runs'];

        return view('dashboard/index', [
            'title'          => 'Dashboard | MD-Bridge',
            'page'           => 'dashboard',
            'summary'        => $summary,
            'successRate'    => $completedRuns > 0 ? round(($successfulRuns / $completedRuns) * 100, 1) : 0,
            'activeTasks'    => (new ApiSyncTaskModel())->where('is_active', 1)->countAllResults(),
            'activeApps'     => (new MonitoringAppModel())->where('is_active', 1)->countAllResults(),
            'dailyStats'     => $dailyStats,
            'recentFailures' => $logModel->getRecentFailures(5),
            'latestRun'      => $logModel->getLatestRun(),
        ]);
    }
}
