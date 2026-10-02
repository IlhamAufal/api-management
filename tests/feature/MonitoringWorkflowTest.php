<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Tests\Support\MonitoringFixtureTrait;

/**
 * Feature test halaman Alur Workflow (Drawflow) — rebuild canvas
 * modul Monitoring lama di atas data sources/watched_tables/snapshots.
 *
 * Trait sudah men-seed source `npd` (id 1) dan `sap` (id 2).
 *
 * @internal
 */
final class MonitoringWorkflowTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use MonitoringFixtureTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMonitoringFixture();
    }

    public function testWorkflowRequiresAuthentication()
    {
        $result = $this->get('monitoring/workflow');

        $result->assertRedirect();
        $result->assertSessionHas('auth_error');
    }

    public function testWorkflowRendersCanvasGraphFromRegistryData()
    {
        $npd     = $this->sourceId('npd');
        $sap     = $this->sourceId('sap');
        $tableId = $this->insertWatchedTable(['label' => 'Pesanan FX']);
        $this->insertSnapshot([
            'watched_table_id' => $tableId,
            'source_id'        => $npd,
            'status'           => 'OK',
            'row_count'        => 1234,
        ]);
        $this->insertSnapshot([
            'watched_table_id' => $tableId,
            'source_id'        => $sap,
            'status'           => 'OK',
            'row_count'        => 50,
        ]);
        $emptyId = $this->insertWatchedTable([
            'table_name' => 'fx_kosong',
            'label'      => 'Tabel Kosong',
        ]);

        $result = $this->withSession($this->authSession())->get('monitoring/workflow');

        $result->assertOK();
        $this->assertBodySee('Alur Workflow', $result);
        $this->assertBodySee('assets/vendor/drawflow/drawflow.min.js', $result);
        $this->assertBodySee('TableFreshnessChecker', $result);
        $this->assertBodySee('Monitoring Matrix', $result);
        $this->assertBodySee('Pesanan FX', $result);
        $this->assertBodySee('NPD (RDS)', $result);
        $this->assertBodySee('SAP', $result);

        $workflow = $this->workflowJson($result);

        // 2 source + 1 checker + 2 tabel + 1 sink.
        $this->assertCount(6, $workflow['nodes']);
        // source->checker x2, (checker->tabel + tabel->sink) x2.
        $this->assertCount(6, $workflow['edges']);

        $table = $this->node($workflow, 'table_' . $tableId);
        $this->assertSame('Pesanan FX', $table['title']);
        $this->assertSame('OK', $table['status']);
        $this->assertContains(['label' => 'npd', 'value' => 'OK (1,234)'], $table['meta']);
        $this->assertContains(['label' => 'sap', 'value' => 'OK (50)'], $table['meta']);

        // Tabel tanpa snapshot sama sekali: NEVER_SYNCED + "belum dicek".
        $empty = $this->node($workflow, 'table_' . $emptyId);
        $this->assertSame('NEVER_SYNCED', $empty['status']);
        $this->assertContains(['label' => 'npd', 'value' => 'belum dicek'], $empty['meta']);
    }

    public function testWorkflowTableNodeUsesWorstStatusAcrossSources()
    {
        $npd     = $this->sourceId('npd');
        $sap     = $this->sourceId('sap');
        $tableId = $this->insertWatchedTable();
        $this->insertSnapshot([
            'watched_table_id' => $tableId,
            'source_id'        => $npd,
            'status'           => 'OK',
        ]);
        $this->insertSnapshot([
            'watched_table_id' => $tableId,
            'source_id'        => $sap,
            'status'           => 'CONN_ERROR',
            'row_count'        => null,
            'last_synced_at'   => null,
            'error_message'    => 'Gagal koneksi',
        ]);

        $result = $this->withSession($this->authSession())->get('monitoring/workflow');

        $result->assertOK();
        $workflow = $this->workflowJson($result);

        // Terburuk menang: CONN_ERROR (5) > OK (1) — bukan status pertama.
        $this->assertSame('CONN_ERROR', $this->node($workflow, 'table_' . $tableId)['status']);
        $this->assertSame('CONN_ERROR', $this->node($workflow, 'checker')['status']);
        $this->assertSame('CONN_ERROR', $this->node($workflow, 'sink')['status']);

        // Sumber biru tetap netral (tanpa badge status).
        $this->assertSame('SOURCE', $this->node($workflow, 'source_npd')['status']);
    }

    public function testWorkflowShowsEmptyStateWhenNothingConfigured()
    {
        $this->deleteSource('npd');
        $this->deleteSource('sap');

        $result = $this->withSession($this->authSession())->get('monitoring/workflow');

        $result->assertOK();
        $this->assertBodySee('Belum ada data untuk ditampilkan', $result);
        $this->assertBodyNotSee('drawflow.min.js', $result);
    }

    public function testMatrixIndexLinksToWorkflowPage()
    {
        $result = $this->withSession($this->authSession())->get('monitoring');

        $result->assertOK();
        $this->assertBodySee('monitoring/workflow', $result);
        $this->assertBodySee('Alur Workflow', $result);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /** Ekstrak payload `var workflow = {...};` dari halaman. */
    private function workflowJson(TestResponse $result): array
    {
        $body = $result->response()->getBody();

        $this->assertTrue(
            (bool) preg_match('/var workflow = (\{.*?\});/s', $body, $matches),
            'Payload workflow tidak ditemukan di halaman.'
        );

        $data = json_decode($matches[1], true);
        $this->assertIsArray($data, 'Payload workflow bukan JSON valid.');

        return $data;
    }

    private function node(array $workflow, string $key): array
    {
        foreach ($workflow['nodes'] as $node) {
            if (($node['key'] ?? null) === $key) {
                return $node;
            }
        }

        $this->fail("Node '{$key}' tidak ada di graph workflow.");
    }
}
