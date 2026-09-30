<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SysSyncLogsSeeder extends Seeder
{
    public function run()
    {
        $logs = [
            [
                'task_code'       => 'sap_material',
                'trigger_type'    => 'CRON',
                'status'          => 'SUCCESS',
                'step_failed'     => 'NONE',
                'records_read'    => 248,
                'records_written' => 248,
                'duration_sec'    => '4.82',
                'error_message'   => null,
                'executed_at'     => '2026-09-30 08:10:00',
                'finished_at'     => '2026-09-30 08:10:05',
            ],
            [
                'task_code'       => 'sap_customer',
                'trigger_type'    => 'CRON',
                'status'          => 'SUCCESS',
                'step_failed'     => 'NONE',
                'records_read'    => 96,
                'records_written' => 96,
                'duration_sec'    => '2.31',
                'error_message'   => null,
                'executed_at'     => '2026-09-30 08:15:00',
                'finished_at'     => '2026-09-30 08:15:02',
            ],
            [
                'task_code'       => 'sap_customer_material',
                'trigger_type'    => 'MANUAL_UI',
                'status'          => 'WARNING',
                'step_failed'     => 'NONE',
                'records_read'    => 351,
                'records_written' => 347,
                'duration_sec'    => '7.46',
                'error_message'   => '4 baris dilewati karena data material tidak lengkap.',
                'executed_at'     => '2026-09-30 09:20:00',
                'finished_at'     => '2026-09-30 09:20:07',
            ],
            [
                'task_code'       => 'sap_customer_sales_area',
                'trigger_type'    => 'WEBHOOK',
                'status'          => 'FAILED',
                'step_failed'     => 'FETCH_GET',
                'records_read'    => 0,
                'records_written' => 0,
                'duration_sec'    => '1.15',
                'error_message'   => 'Sumber data tidak dapat dihubungi.',
                'executed_at'     => '2026-09-30 09:25:00',
                'finished_at'     => '2026-09-30 09:25:01',
            ],
        ];

        foreach ($logs as $log) {
            $builder = $this->db->table('sys_sync_logs');
            $existing = $builder
                ->where('task_code', $log['task_code'])
                ->where('executed_at', $log['executed_at'])
                ->get()
                ->getRowArray();

            if ($existing === null) {
                $builder->insert($log);
                continue;
            }

            $builder->where('id', $existing['id'])->update($log);
        }
    }
}
