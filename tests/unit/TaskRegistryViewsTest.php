<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Smoke test render view Task Registry (index, create, edit).
 *
 * @internal
 */
final class TaskRegistryViewsTest extends CIUnitTestCase
{
    private function sampleTask(): array
    {
        return [
            'id'                 => 1,
            'task_code'          => 'sap_material_master',
            'task_name'          => 'Material Master Sync',
            'category'           => 'SAP_MASTER',
            'source_type'        => 'DIRECT_DB',
            'source_endpoint'    => 'https://example.com/odata/API_PRODUCT_SRV',
            'target_table'       => 'sap_material_master',
            'batch_size'         => 1500,
            'cron_expression'    => '10 8 * * 1-6',
            'is_active'          => 1,
            'monitoring_linked'  => true,
            'monitoring_app_code'=> 'SAP',
        ];
    }

    private function formVars(array $overrides = []): array
    {
        return array_merge([
            'title'        => 'API Task Registry | MD-Bridge',
            'page'         => 'tasks',
            'task'         => null,
            'errors'       => [],
            'oldInput'     => [],
            'formAction'   => 'http://example.com/tasks',
            'isEdit'       => false,
            'cancelUrl'    => 'http://example.com/tasks',
            'targetTables' => ['sap_material_master'],
            'categories'   => ['SAP_MASTER', 'GENERAL'],
            'sourceTypes'  => ['HTTP_GET', 'HTTP_POST'],
        ], $overrides);
    }

    public function testIndexRendersTaskRowsWithIconActions()
    {
        $html = view('pages/task_registry/index', [
            'title' => 'API Task Registry | MD-Bridge',
            'page'  => 'tasks',
            'tasks' => [$this->sampleTask()],
        ]);

        $this->assertStringContainsString('Material Master Sync', $html);
        $this->assertStringContainsString('sap_material_master', $html);

        // Kolom Actions memakai Font Awesome, bukan teks polos.
        $this->assertStringContainsString('fa-pen-to-square', $html);
        $this->assertStringContainsString('fa-toggle-on', $html);
        $this->assertStringContainsString('fa-trash-can', $html);

        // Halaman list tidak lagi menampilkan modal form.
        $this->assertStringNotContainsString('x-data="{ open:', $html);
        $this->assertStringNotContainsString('modal_task_form', $html);

        // Font Awesome di-load oleh layout.
        $this->assertStringContainsString('fontawesome/css/all.min.css', $html);

        // Kolom cron disembunyikan (sync manual, tanpa cron untuk saat ini).
        $this->assertStringNotContainsString('>Schedule</th>', $html);
        $this->assertStringNotContainsString('cron_expression', $html);
    }

    public function testCreatePageRendersStandaloneForm()
    {
        $html = view('pages/task_registry/create', $this->formVars());

        $this->assertStringContainsString('Tambah Task', $html);
        $this->assertStringContainsString('<form method="post" action="http://example.com/tasks"', $html);
        $this->assertStringContainsString('fa-plus', $html);
        // Task code masih bisa diedit saat create.
        $this->assertDoesNotMatchRegularExpression('/name="task_code"[^>]*readonly/', $html);
    }

    public function testEditPagePrefillsTaskAndLocksTaskCode()
    {
        $html = view('pages/task_registry/edit', $this->formVars([
            'task'       => $this->sampleTask(),
            'formAction' => 'http://example.com/tasks/update/1',
            'isEdit'     => true,
        ]));

        $this->assertStringContainsString('http://example.com/tasks/update/1', $html);
        $this->assertStringContainsString('value="Material Master Sync"', $html);
        $this->assertStringContainsString('readonly', $html);
        // Field cron disembunyikan dari form.
        $this->assertStringNotContainsString('name="cron_expression"', $html);
        $this->assertStringContainsString('fa-floppy-disk', $html);
        $this->assertStringContainsString('Simpan Perubahan', $html);
    }

    public function testValidationErrorsAreRenderedOnFormPages()
    {
        $html = view('pages/task_registry/create', $this->formVars([
            'errors'   => ['task_code' => 'Task Code sudah digunakan oleh task lain.'],
            'oldInput' => ['task_code' => 'dup_task'],
        ]));

        $this->assertStringContainsString('Task Code sudah digunakan oleh task lain.', $html);
        $this->assertStringContainsString('value="dup_task"', $html);
        $this->assertStringContainsString('fa-triangle-exclamation', $html);
    }
}
