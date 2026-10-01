<?php

namespace App\Controllers;

use App\Models\ApiSyncTaskModel;
use App\Models\MonitoringAppModel;
use Config\Services;

class TaskRegistry extends BaseController
{
    private const CATEGORIES = ['SAP_MASTER', 'GENERAL'];
    private const SOURCE_TYPES = ['DIRECT_DB', 'HTTP_GET', 'HTTP_POST'];

    /**
     * Shared form metadata consumed by the task modal component.
     */
    private function formMeta(): array
    {
        return [
            'targetTables' => $this->taskModel->getTargetTables(),
            'categories'   => self::CATEGORIES,
            'sourceTypes'  => self::SOURCE_TYPES,
        ];
    }

    private ApiSyncTaskModel $taskModel;
    private MonitoringAppModel $appModel;

    public function __construct()
    {
        $this->taskModel = new ApiSyncTaskModel();
        $this->appModel = new MonitoringAppModel();
    }

    public function index()
    {
        return $this->renderIndex();
    }

    /**
     * GET /tasks/new — renders the page with the create modal open.
     */
    public function create()
    {
        return $this->renderIndex([
            'openModal' => true,
        ]);
    }

    /**
     * GET /tasks/edit/(:num) — renders the page with the edit modal open.
     */
    public function edit($id)
    {
        $task = $this->taskModel->find((int) $id);

        if ($task === null) {
            return redirect()->to(base_url('tasks'))
                ->with('flash_error', 'Task tidak ditemukan.');
        }

        return $this->renderIndex([
            'modalTask' => $task,
            'openModal' => true,
        ]);
    }

    public function store()
    {
        $post = $this->normalizedPost();

        if (!$this->runValidation($post)) {
            return redirect()->to(base_url('tasks/new'))
                ->withInput()
                ->with('validation_errors', $this->validator->getErrors());
        }

        $this->taskModel->insert($post);
        $this->appModel->linkTask($post['task_code'], $post['target_table']);

        return redirect()->to(base_url('tasks'))
            ->with('flash_success', 'Task "' . $post['task_name'] . '" berhasil ditambahkan dan terhubung ke monitoring.');
    }

    public function update($id)
    {
        $task = $this->taskModel->find((int) $id);

        if ($task === null) {
            return redirect()->to(base_url('tasks'))
                ->with('flash_error', 'Task tidak ditemukan.');
        }

        $post = $this->normalizedPost();

        if (!$this->runValidation($post, (int) $id)) {
            return redirect()->to(base_url('tasks/edit/' . $id))
                ->withInput()
                ->with('validation_errors', $this->validator->getErrors());
        }

        $this->taskModel->update((int) $id, $post);

        // Keep monitoring links in sync with the task's current target table.
        $this->appModel->detachTask($task['task_code']);
        $this->appModel->linkTask($task['task_code'], $post['target_table']);

        return redirect()->to(base_url('tasks'))
            ->with('flash_success', 'Task "' . $post['task_name'] . '" berhasil diperbarui.');
    }

    public function delete($id)
    {
        $task = $this->taskModel->find((int) $id);

        if ($task === null) {
            return redirect()->to(base_url('tasks'))
                ->with('flash_error', 'Task tidak ditemukan.');
        }

        $this->taskModel->delete((int) $id);
        $this->appModel->detachTask($task['task_code']);

        return redirect()->to(base_url('tasks'))
            ->with('flash_success', 'Task "' . $task['task_name'] . '" berhasil dihapus dan dilepas dari monitoring.');
    }

    public function toggle($id)
    {
        $task = $this->taskModel->find((int) $id);

        if ($task === null) {
            return redirect()->to(base_url('tasks'))
                ->with('flash_error', 'Task tidak ditemukan.');
        }

        $newStatus = (int) $task['is_active'] === 1 ? 0 : 1;
        $this->taskModel->update((int) $id, ['is_active' => $newStatus]);

        return redirect()->to(base_url('tasks'))
            ->with('flash_success', 'Task "' . $task['task_name'] . '" ' . ($newStatus === 1 ? 'diaktifkan' : 'dinonaktifkan') . '.');
    }

    /**
     * Render the registry page, optionally with the task modal pre-opened.
     */
    private function renderIndex(array $extra = []): string
    {
        $tasks = $this->taskModel->getAllTasks();
        $linked = $this->appModel->getLinkedTaskApps(array_column($tasks, 'task_code'));

        foreach ($tasks as &$task) {
            $task['monitoring_linked'] = isset($linked[$task['task_code']]);
            $task['monitoring_app_code'] = $linked[$task['task_code']] ?? null;
        }
        unset($task);

        return view('pages/task_registry/index', array_merge([
            'title'     => 'API Task Registry | MD-Bridge',
            'page'      => 'tasks',
            'tasks'     => $tasks,
            'modalTask' => null,
        ], $this->formMeta(), $extra));
    }

    /**
     * Normalized POST payload. is_active is checkbox-driven, so it is
     * derived from presence instead of value.
     */
    private function normalizedPost(): array
    {
        $category = trim((string) $this->request->getPost('category'));
        $sourceType = (string) $this->request->getPost('source_type');

        return [
            'task_code'       => strtolower(trim((string) $this->request->getPost('task_code'))),
            'task_name'       => trim((string) $this->request->getPost('task_name')),
            'category'        => in_array($category, self::CATEGORIES, true) ? $category : self::CATEGORIES[0],
            'source_type'     => in_array($sourceType, self::SOURCE_TYPES, true) ? $sourceType : self::SOURCE_TYPES[1],
            'source_endpoint' => trim((string) $this->request->getPost('source_endpoint')),
            'target_table'    => (string) $this->request->getPost('target_table'),
            'batch_size'      => (string) $this->request->getPost('batch_size'),
            'cron_expression' => trim((string) $this->request->getPost('cron_expression')),
            'is_active'       => $this->request->getPost('is_active') !== null ? '1' : '0',
        ];
    }

    /**
     * Validate the payload against an explicit ruleset, including an
     * in_list guard generated from the allowed target tables.
     */
    private function runValidation(array $post, ?int $id = null): bool
    {
        $id = (string) ($id ?? '');
        $rules = [
            'task_code'       => "required|alpha_dash|min_length[3]|max_length[50]|is_unique[api_sync_tasks.task_code,id,{$id}]",
            'task_name'       => 'required|string|min_length[3]|max_length[100]',
            'category'        => 'required|in_list[' . implode(',', self::CATEGORIES) . ']',
            'source_type'     => 'required|in_list[' . implode(',', self::SOURCE_TYPES) . ']',
            'source_endpoint' => 'required|string|max_length[500]',
            'target_table'    => 'required|in_list[' . implode(',', $this->taskModel->getTargetTables()) . ']',
            'batch_size'      => 'required|is_natural_no_zero|less_than_equal_to[10000]',
            'cron_expression' => 'permit_empty|string|max_length[50]',
            'is_active'       => 'required|in_list[0,1]',
        ];

        $this->validator = Services::validation();
        $this->validator->setRules($rules, [
            'task_code' => [
                'is_unique' => 'Task Code sudah digunakan oleh task lain.',
                'alpha_dash' => 'Task Code hanya boleh berisi huruf kecil, angka, dash, dan underscore.',
            ],
            'target_table' => [
                'in_list' => 'Target Table tidak termasuk daftar tabel yang diizinkan.',
            ],
            'batch_size' => [
                'is_natural_no_zero' => 'Batch Size harus berupa angka lebih dari 0.',
            ],
        ]);

        return $this->validator->run($post);
    }
}
