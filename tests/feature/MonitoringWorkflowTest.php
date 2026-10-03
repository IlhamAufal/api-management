<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use Tests\Support\MonitoringFixtureTrait;

/**
 * Feature test halaman Alur Workflow (Drawflow) — per-source (sub-tab):
 * landing = source pertama, tab per source (URL nyata), graph di-scope
 * ke source terpilih, 404 untuk code tak dikenal/nonaktif.
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

    public function testWorkflowRendersCanvasGraphForFirstActiveSourceOnly()
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
        $this->assertBodySee('Dashboard Monitoring', $result);
        // Tab bar: semua source aktif tampil sebagai URL nyata.
        $this->assertBodySee('monitoring/workflow/source/npd', $result);
        $this->assertBodySee('monitoring/workflow/source/sap', $result);
        $this->assertBodySee('Pesanan FX', $result);
        $this->assertBodySee('NPD (RDS)', $result);

        $workflow = $this->workflowJson($result);

        // Landing = source pertama (npd): 1 source + 1 checker + 2 tabel + 1 sink.
        $this->assertCount(5, $workflow['nodes']);
        // source->checker x1, (checker->tabel + tabel->sink) x2.
        $this->assertCount(5, $workflow['edges']);

        $table = $this->node($workflow, 'table_' . $tableId);
        $this->assertSame('Pesanan FX', $table['title']);
        $this->assertSame('OK', $table['status']);
        // Meta hanya memuat source tab terpilih (npd), bukan gabungan.
        $this->assertContains(['label' => 'npd', 'value' => 'OK (1,234)'], $table['meta']);
        $this->assertNotContains(['label' => 'sap', 'value' => 'OK (50)'], $table['meta']);

        // Tabel tanpa snapshot sama sekali: NEVER_SYNCED + "belum dicek".
        $empty = $this->node($workflow, 'table_' . $emptyId);
        $this->assertSame('NEVER_SYNCED', $empty['status']);
        $this->assertContains(['label' => 'npd', 'value' => 'belum dicek'], $empty['meta']);
    }

    public function testWorkflowSourceTabScopesGraphToThatSource()
    {
        $npd     = $this->sourceId('npd');
        $sap     = $this->sourceId('sap');
        $tableId = $this->insertWatchedTable();
        // Berbeda per source: npd OK, sap CONN_ERROR.
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

        // Tab npd: graph hanya npd, status OK.
        $npdPage = $this->withSession($this->authSession())->get('monitoring/workflow/source/npd');
        $npdPage->assertOK();
        $npdWorkflow = $this->workflowJson($npdPage);
        $this->assertNotNull($this->nodeOrNull($npdWorkflow, 'source_npd'));
        $this->assertNull($this->nodeOrNull($npdWorkflow, 'source_sap'), 'Tab npd tidak boleh memuat node source sap.');
        $this->assertSame('OK', $this->node($npdWorkflow, 'table_' . $tableId)['status']);
        $this->assertSame('OK', $this->node($npdWorkflow, 'checker')['status']);
        $this->assertSame('OK', $this->node($npdWorkflow, 'sink')['status']);

        // Tab sap: hanya sap, status CONN_ERROR (terburuk di source ini).
        $sapPage = $this->withSession($this->authSession())->get('monitoring/workflow/source/sap');
        $sapPage->assertOK();
        $sapWorkflow = $this->workflowJson($sapPage);
        $this->assertNotNull($this->nodeOrNull($sapWorkflow, 'source_sap'));
        $this->assertNull($this->nodeOrNull($sapWorkflow, 'source_npd'), 'Tab sap tidak boleh memuat node source npd.');
        $this->assertSame('CONN_ERROR', $this->node($sapWorkflow, 'table_' . $tableId)['status']);
        $this->assertSame('CONN_ERROR', $this->node($sapWorkflow, 'checker')['status']);
        $this->assertSame('CONN_ERROR', $this->node($sapWorkflow, 'sink')['status']);

        // Sumber biru tetap netral (tanpa badge status).
        $this->assertSame('SOURCE', $this->node($sapWorkflow, 'source_sap')['status']);
    }

    public function testWorkflowUnknownSourceCodeReturns404()
    {
        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->withSession($this->authSession())->get('monitoring/workflow/source/ngaco');
    }

    public function testWorkflowInactiveSourceReturns404()
    {
        $this->monitoringDb()->table('sources')->where('code', 'sap')->update(['is_active' => 0]);

        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->withSession($this->authSession())->get('monitoring/workflow/source/sap');
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
        $node = $this->nodeOrNull($workflow, $key);
        if ($node === null) {
            $this->fail("Node '{$key}' tidak ada di graph workflow.");
        }

        return $node;
    }

    private function nodeOrNull(array $workflow, string $key): ?array
    {
        foreach ($workflow['nodes'] as $node) {
            if (($node['key'] ?? null) === $key) {
                return $node;
            }
        }

        return null;
    }
}
