<?php

namespace App\Controllers;

class SyncLogs extends BaseController
{
    public function index()
    {
        return view('pages/sync_logs/index', [
            'title' => 'Execution Logs | MD-Bridge',
            'page'  => 'logs',
            'apps'  => [
                [
                    'id'          => 'sap-sync-ci4',
                    'name'        => 'sap-sync-ci4',
                    'service'     => 'SAP Ingestion',
                    'health'      => 'Healthy',
                    'healthTone'  => 'success',
                    'badgeDetail' => 'Active Cron 08:00 WIB',
                    'description' => 'Pulls master catalog data from SAP S/4HANA Cloud and upserts into AWS RDS (yp_so).',
                    'metrics'     => 'Today: 4/4 Synced | Latency: 12.4s',
                ],
                [
                    'id'          => 'sap-post-ci3',
                    'name'        => 'sap-post-ci3',
                    'service'     => 'Batch Dispatcher',
                    'health'      => 'Warning',
                    'healthTone'  => 'warning',
                    'badgeDetail' => 'Intermittent Timeout',
                    'description' => 'Reads staging records and pushes 1,500-row chunks to receiver endpoint.',
                    'metrics'     => 'Today: 3/4 Synced | 1 Timeout (300s)',
                ],
                [
                    'id'          => 'sap-get-ci3-g2sp',
                    'name'        => 'sap-get-ci3 / G2SP',
                    'service'     => 'Final Receiver',
                    'health'      => 'Active Receiving',
                    'healthTone'  => 'success',
                    'badgeDetail' => 'Receiver Online',
                    'description' => 'Final receiver ingestion writing into destination database (yp_sap).',
                    'metrics'     => 'Total Ingested: 3,340 Rows',
                ],
            ],
            'logs' => [
                ['id' => 101, 'app' => 'sap-sync-ci4', 'executedAt' => '2026-09-30 08:00:12', 'table' => 'sap_material_master', 'trigger' => 'CRON', 'records' => 1500, 'duration' => '12.4s', 'status' => 'SUCCESS', 'stepFailed' => 'NONE', 'errorCode' => null, 'errorPayload' => null],
                ['id' => 102, 'app' => 'sap-sync-ci4', 'executedAt' => '2026-09-30 08:10:05', 'table' => 'sap_customer_master', 'trigger' => 'CRON', 'records' => 840, 'duration' => '9.8s', 'status' => 'SUCCESS', 'stepFailed' => 'NONE', 'errorCode' => null, 'errorPayload' => null],
                ['id' => 103, 'app' => 'sap-sync-ci4', 'executedAt' => '2026-09-30 08:20:08', 'table' => 'sap_customer_material', 'trigger' => 'MANUAL_UI', 'records' => 0, 'duration' => '1.1s', 'status' => 'RUNNING', 'stepFailed' => 'NONE', 'errorCode' => null, 'errorPayload' => null],
                ['id' => 104, 'app' => 'sap-sync-ci4', 'executedAt' => '2026-09-29 08:25:19', 'table' => 'sap_customer_sales_area', 'trigger' => 'CRON', 'records' => 1000, 'duration' => '15.7s', 'status' => 'SUCCESS', 'stepFailed' => 'NONE', 'errorCode' => null, 'errorPayload' => null],
                ['id' => 201, 'app' => 'sap-post-ci3', 'executedAt' => '2026-09-30 08:14:08', 'table' => 'sap_customer_material', 'trigger' => 'CRON', 'records' => 1500, 'duration' => '300.0s', 'status' => 'FAILED', 'stepFailed' => 'FETCH_GET', 'errorCode' => 'ETIMEDOUT', 'errorPayload' => '{"error":"ETIMEDOUT","message":"Receiver endpoint did not respond within 300 seconds.","retryable":true}'],
                ['id' => 202, 'app' => 'sap-post-ci3', 'executedAt' => '2026-09-30 08:05:33', 'table' => 'sap_material_master', 'trigger' => 'CRON', 'records' => 1500, 'duration' => '14.2s', 'status' => 'SUCCESS', 'stepFailed' => 'NONE', 'errorCode' => null, 'errorPayload' => null],
                ['id' => 203, 'app' => 'sap-post-ci3', 'executedAt' => '2026-09-29 08:40:40', 'table' => 'sap_customer_sales_area', 'trigger' => 'MANUAL_UI', 'records' => 340, 'duration' => '11.6s', 'status' => 'SUCCESS', 'stepFailed' => 'NONE', 'errorCode' => null, 'errorPayload' => null],
                ['id' => 301, 'app' => 'sap-get-ci3-g2sp', 'executedAt' => '2026-09-30 08:16:21', 'table' => 'sap_customer_material', 'trigger' => 'CRON', 'records' => 1500, 'duration' => '8.6s', 'status' => 'SUCCESS', 'stepFailed' => 'NONE', 'errorCode' => null, 'errorPayload' => null],
                ['id' => 302, 'app' => 'sap-get-ci3-g2sp', 'executedAt' => '2026-09-30 08:27:12', 'table' => 'sap_customer_sales_area', 'trigger' => 'CRON', 'records' => 1840, 'duration' => '10.3s', 'status' => 'SUCCESS', 'stepFailed' => 'NONE', 'errorCode' => null, 'errorPayload' => null],
            ],
        ]);
    }
}
