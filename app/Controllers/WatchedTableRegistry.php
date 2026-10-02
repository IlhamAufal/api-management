<?php

namespace App\Controllers;

use App\Libraries\Monitoring\SourceIntrospector;
use App\Libraries\Monitoring\TableFreshnessChecker;
use App\Models\SourceModel;
use App\Models\WatchedTableModel;
use Config\Services;

/**
 * Registry UI: kelola tabel yang dipantau (watched_tables).
 * Introspeksi tabel/kolom dilakukan terhadap source aktif — admin
 * tidak perlu sentuh kode/migration untuk menambah tabel baru.
 */
class WatchedTableRegistry extends BaseController
{
    private WatchedTableModel $watchedModel;
    private SourceIntrospector $introspector;
    /** @var \CodeIgniter\Validation\Validation */
    private $formValidator;

    public function __construct()
    {
        $this->watchedModel  = new WatchedTableModel();
        $this->introspector  = Services::sourceIntrospector();
    }

    public function index()
    {
        return view('pages/watched_tables/index', [
            'title'      => 'Watched Tables | MD-Bridge',
            'page'       => 'watched_tables',
            'tables'     => $this->watchedModel->orderBy('id', 'ASC')->findAll(),
            'flashSuccess' => session()->getFlashdata('flash_success'),
            'flashError'   => session()->getFlashdata('flash_error'),
        ]);
    }

    public function create()
    {
        return $this->renderForm(null);
    }

    public function edit($id)
    {
        $table = $this->watchedModel->find((int) $id);

        if ($table === null) {
            return redirect()->to(base_url('watched-tables'))
                ->with('flash_error', 'Watched table tidak ditemukan.');
        }

        return $this->renderForm($table);
    }

    public function store()
    {
        $post = $this->normalizedPost();

        if (! $this->runValidation($post)) {
            return redirect()->to(base_url('watched-tables/new'))
                ->withInput()
                ->with('validation_errors', $this->formValidator->getErrors());
        }

        $introspectionError = $this->introspector->validateRegistration(
            $post['table_name'],
            $post['sync_column']
        );

        if ($introspectionError !== null) {
            return redirect()->to(base_url('watched-tables/new'))
                ->withInput()
                ->with('validation_errors', ['table_name' => $introspectionError]);
        }

        $inserted = $this->watchedModel->insert($post);

        // Check sekali untuk tabel ini supaya langsung muncul di dashboard.
        $table = $this->watchedModel->find((int) $inserted);
        if ($table !== null && (int) $table['is_active'] === 1) {
            try {
                Services::freshnessChecker()->checkTable($table);
            } catch (\Throwable $e) {
                // Check gagal tidak menggagalkan registrasi —
                // status CONN_ERROR akan tampil di dashboard.
            }
        }

        return redirect()->to(base_url('watched-tables'))
            ->with('flash_success', 'Tabel "' . $post['label'] . '" berhasil didaftarkan.');
    }

    public function update($id)
    {
        $table = $this->watchedModel->find((int) $id);

        if ($table === null) {
            return redirect()->to(base_url('watched-tables'))
                ->with('flash_error', 'Watched table tidak ditemukan.');
        }

        $post = $this->normalizedPost();

        if (! $this->runValidation($post, (int) $id)) {
            return redirect()->to(base_url('watched-tables/edit/' . $id))
                ->withInput()
                ->with('validation_errors', $this->formValidator->getErrors());
        }

        $introspectionError = $this->introspector->validateRegistration(
            $post['table_name'],
            $post['sync_column']
        );

        if ($introspectionError !== null) {
            return redirect()->to(base_url('watched-tables/edit/' . $id))
                ->withInput()
                ->with('validation_errors', ['table_name' => $introspectionError]);
        }

        // {id} pada rule is_unique hanya ter-replace bila data memuat
        // key 'id' (CI 4.1.9) — tanpa itu edit dengan nama tidak berubah
        // gagal diam-diam. 'id' tidak di allowedFields, jadi tidak ikut SET.
        $this->watchedModel->update((int) $id, $post + ['id' => (int) $id]);

        $table = $this->watchedModel->find((int) $id);
        if ($table !== null && (int) $table['is_active'] === 1) {
            try {
                Services::freshnessChecker()->checkTable($table);
            } catch (\Throwable $e) {
                // lihat store()
            }
        }

        return redirect()->to(base_url('watched-tables'))
            ->with('flash_success', 'Tabel "' . $post['label'] . '" berhasil diperbarui.');
    }

    /**
     * Toggle aktif/nonaktif. Tabel nonaktif tidak ikut checkAll berikutnya.
     */
    public function toggle($id)
    {
        $table = $this->watchedModel->find((int) $id);

        if ($table === null) {
            return redirect()->to(base_url('watched-tables'))
                ->with('flash_error', 'Watched table tidak ditemukan.');
        }

        $newStatus = (int) $table['is_active'] === 1 ? 0 : 1;
        $this->watchedModel->update((int) $id, ['is_active' => $newStatus]);

        return redirect()->to(base_url('watched-tables'))
            ->with('flash_success', 'Tabel "' . $table['label'] . '" ' . ($newStatus === 1 ? 'diaktifkan' : 'dinonaktifkan') . '.');
    }

    /**
     * Soft delete: set is_active = 0 supaya riwayat di check_history
     * tidak kehilangan makna (tetap ada sebagai konteks).
     */
    public function delete($id)
    {
        $table = $this->watchedModel->find((int) $id);

        if ($table === null) {
            return redirect()->to(base_url('watched-tables'))
                ->with('flash_error', 'Watched table tidak ditemukan.');
        }

        $this->watchedModel->update((int) $id, ['is_active' => 0]);

        return redirect()->to(base_url('watched-tables'))
            ->with('flash_success', 'Tabel "' . $table['label'] . '" dinonaktifkan (soft delete) — riwayat check tetap tersimpan.');
    }

    /**
     * AJAX: nama tabel dari lingkup source tertentu (untuk dropdown
     * Nama Tabel ketika filter database diubah).
     */
    public function tables()
    {
        $source = (string) $this->request->getGet('source');

        return $this->response->setJSON([
            'source' => $source,
            'tables' => $this->introspector->listTables($source !== '' ? $source : null),
        ]);
    }

    /**
     * AJAX: kolom sebuah tabel (untuk dropdown sync_column).
     * Parameter `source` opsional — membatasi introspeksi ke satu database.
     */
    public function columns()
    {
        $tableName = (string) $this->request->getGet('table');
        $source    = (string) $this->request->getGet('source');
        $columns   = $this->introspector->listColumns($tableName, $source !== '' ? $source : null);

        return $this->response->setJSON([
            'table'   => $tableName,
            'source'  => $source,
            'columns' => $columns ?? [],
        ]);
    }

    private function renderForm(?array $table): string
    {
        $oldFlash = session()->getFlashdata('_ci_old_input');
        $oldInput = [];
        if (is_array($oldFlash)) {
            $oldInput = (array) ($oldFlash['post'] ?? ($oldFlash['old'] ?? []));
        }

        // Filter database bersifat UX saja (tidak disimpan ke watched_tables):
        // membatasi daftar tabel/kolom saat mengisi form.
        $selectedSource = (string) ($oldInput['source']
            ?? $this->request->getGet('source')
            ?? '');

        return view('pages/watched_tables/form', [
            'title'      => ($table !== null ? 'Edit' : 'Tambah') . ' Watched Table | MD-Bridge',
            'page'       => 'watched_tables',
            'table'      => $table,
            'errors'     => (array) (session()->getFlashdata('validation_errors') ?: []),
            'oldInput'   => $oldInput,
            'sources'    => (new SourceModel())->getActiveSources(),
            'selectedSource' => $selectedSource,
            'allTables'  => $this->introspector->listTables($selectedSource !== '' ? $selectedSource : null),
            'formAction' => $table !== null
                ? base_url('watched-tables/update/' . (int) $table['id'])
                : base_url('watched-tables'),
            'isEdit'     => $table !== null,
            'cancelUrl'  => base_url('watched-tables'),
        ]);
    }

    private function normalizedPost(): array
    {
        return [
            'table_name'          => strtolower(trim((string) $this->request->getPost('table_name'))),
            'label'               => trim((string) $this->request->getPost('label')),
            'sync_column'         => trim((string) $this->request->getPost('sync_column')),
            'stale_after_minutes' => trim((string) $this->request->getPost('stale_after_minutes')),
            'is_active'           => $this->request->getPost('is_active') !== null ? '1' : '0',
        ];
    }

    private function runValidation(array $post, ?int $id = null): bool
    {
        $id = (string) ($id ?? '');

        // Instance validation shared — reset dulu supaya error validasi
        // sebelumnya tidak membuat run() selanjutnya false (lihat DatabaseRegistry).
        $this->formValidator = Services::validation();
        $this->formValidator->reset();
        $this->formValidator->setRules(
            [
                'table_name'          => "required|regex_match[/^[a-z][a-z0-9_]*$/]|max_length[100]|is_unique[watched_tables.table_name,id,{$id}]",
                'label'               => 'required|string|max_length[150]',
                'sync_column'         => 'required|regex_match[/^[a-z][a-z0-9_]*$/]|max_length[100]',
                'stale_after_minutes' => 'required|is_natural_no_zero|less_than_equal_to[525600]',
                'is_active'           => 'required|in_list[0,1]',
            ],
            [
                'table_name' => [
                    'is_unique'   => 'Nama tabel sudah terdaftar sebagai watched table.',
                    'regex_match' => 'Nama tabel hanya boleh huruf kecil, angka, dan underscore.',
                ],
                'sync_column' => [
                    'regex_match' => 'Nama kolom sync hanya boleh huruf, angka, dan underscore.',
                ],
                'stale_after_minutes' => [
                    'is_natural_no_zero' => 'Stale after harus bilangan bulat lebih dari 0.',
                    'less_than_equal_to' => 'Stale after maksimal 525600 menit (1 tahun).',
                ],
            ]
        );

        return $this->formValidator->run($post);
    }
}
