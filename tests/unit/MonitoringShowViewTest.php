<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Smoke test render view monitoring/show (mode cek read-only vs cron).
 *
 * @internal
 */
final class MonitoringShowViewTest extends CIUnitTestCase
{
    private function baseVars(): array
    {
        return [
            'title'   => 'YP NPD | Monitoring MD-Bridge',
            'page'    => 'monitoring',
            'app'     => [
                'app_code'      => 'yp_npd',
                'app_name'      => 'YP NPD',
                'description'   => 'Aplikasi NPD.',
                'database_name' => 'yp_npd',
            ],
            'tables'  => [[
                'table_code'       => 'sap_material_master',
                'table_name'       => 'Material Master',
                'sync_task_code'   => 'npd_material',
                'last_cron'        => null,
                'cron_expression'  => null,
                'operational_status' => 'UNKNOWN',
            ]],
            'history' => [],
            'summary' => [
                'operational_status' => 'UNKNOWN',
                'mapped_tables'      => 1,
                'reported_tables'    => 0,
                'failed_tables'      => 0,
                'last_cron_at'       => null,
            ],
            'workflow' => ['nodes' => [], 'edges' => []],
            'check'    => null,
        ];
    }

    private function comparisonRow(): array
    {
        return [[
            'table_code'  => 'sap_material_master',
            'table_name'  => 'Material Master',
            'npd_rows'    => 29081,
            'npd_updated' => '2026-10-01 07:00:00',
            'npd_error'   => null,
            'sap_rows'    => 29081,
            'sap_updated' => '2026-10-01 14:00:00',
            'sap_error'   => null,
            'delta'       => 0,
            'status'      => 'MATCH',
        ]];
    }

    public function testCheckModeRendersComparisonAndCheckButton()
    {
        $vars = $this->baseVars();
        $vars['check'] = [
            'source'         => 'npd',
            'source_label'   => 'yp_npd',
            'configured'     => true,
            'npd_configured' => true,
            'sap_configured' => true,
            'comparison'     => $this->comparisonRow(),
        ];

        $html = view('pages/monitoring/show', $vars);

        $this->assertStringContainsString('Perbandingan Sinkronisasi', $html);
        $this->assertStringContainsString('id="check-now"', $html);
        $this->assertStringContainsString('Rows yp_npd', $html);
        $this->assertStringContainsString('Riwayat Pemeriksaan', $html);
        $this->assertStringContainsString('monitoring/check/yp_npd', $html);
    }

    public function testCheckModeShowsCredentialBannerWhenNotConfigured()
    {
        $vars = $this->baseVars();
        $vars['check'] = [
            'source'         => 'npd',
            'source_label'   => 'yp_npd',
            'configured'     => false,
            'npd_configured' => false,
            'sap_configured' => true,
            'comparison'     => $this->comparisonRow(),
        ];

        $html = view('pages/monitoring/show', $vars);

        $this->assertStringContainsString('Kredensial belum diisi', $html);
        $this->assertStringContainsString('database.npd.username', $html);
    }

    public function testCronModeHidesComparisonAndCheckButton()
    {
        $html = view('pages/monitoring/show', $this->baseVars());

        $this->assertStringNotContainsString('Perbandingan Sinkronisasi', $html);
        $this->assertStringNotContainsString('id="check-now"', $html);
        $this->assertStringContainsString('Riwayat Cronjob', $html);
    }
}
