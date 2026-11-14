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
    /** Pilihan jumlah baris per halaman; yang pertama = default. */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

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
        $watched = $this->watchedModel->orderBy('id', 'ASC')->findAll();

        // Peta code => label source aktif (untuk tampilan kolom Source).
        $sourceLabels = [];
        foreach ((new SourceModel())->getActiveSources() as $source) {
            $sourceLabels[(string) $source['code']] = (string) $source['label'];
        }

        // Ekspansi tiap watched_table menjadi satu baris per source yang
        // secara fisik memuat tabel tersebut. Beda source = beda baris
        // (duplikasi antar source memang diharapkan). Tabel yang tidak
        // ditemukan di source aktif manapun tetap tampil satu baris
        // dengan source kosong supaya tidak hilang diam-diam.
        $rows = [];
        foreach ($watched as $table) {
            $codes = $this->introspector->sourcesContainingTable((string) $table['table_name']);

            if ($codes === []) {
                $rows[] = $table + ['source_code' => null, 'source_label' => null];
                continue;
            }

            foreach ($codes as $code) {
                $rows[] = $table + [
                    'source_code'  => $code,
                    'source_label' => $sourceLabels[$code] ?? $code,
                ];
            }
        }

        // Pagination di memori: baris sudah diekspansi per source, jadi
        // total tidak bisa dihitung lewat query. perPage dibatasi whitelist.
        $perPage = (int) $this->request->getGet('per_page');
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::PER_PAGE_OPTIONS[0];
        }

        $total      = count($rows);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $pageNum    = min(max(1, (int) $this->request->getGet('page')), $totalPages);

        return view('pages/watched_tables/index', [
            'title'      => 'Watched Tables | MD-Bridge',
            'page'       => 'watched_tables',
            'tables'     => array_slice($rows, ($pageNum - 1) * $perPage, $perPage),
            'pagination' => [
                'total'      => $total,
                'page'       => $pageNum,
                'perPage'    => $perPage,
                'totalPages' => $totalPages,
                'options'    => self::PER_PAGE_OPTIONS,
            ],
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
        // Mode bulk: form tambah mengirim rows[n][field] (tombol "Tambah Kolom").
        $rows = $this->request->getPost('rows');
        if (is_array($rows)) {
            return $this->storeBulk($rows);
        }

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

    /**
     * Registrasi banyak tabel sekaligus. All-or-nothing: bila ada satu baris
     * tidak valid, tidak ada yang disimpan dan semua error dilaporkan
     * dengan prefix nomor baris.
     */
    private function storeBulk(array $rawRows)
    {
        $rows = [];
        foreach ($rawRows as $raw) {
            if (is_array($raw)) {
                $rows[] = $this->normalizeRow($raw);
            }
        }

        if ($rows === []) {
            return redirect()->to(base_url('watched-tables/new'))
                ->withInput()
                ->with('validation_errors', ['rows' => 'Minimal satu tabel harus diisi.']);
        }

        $errors = [];
        $seen   = [];

        foreach ($rows as $i => $row) {
            $prefix = 'Baris ' . ($i + 1) . ': ';

            if (! $this->runValidation($row)) {
                foreach ($this->formValidator->getErrors() as $field => $message) {
                    $errors["rows.{$i}.{$field}"] = $prefix . $message;
                }
                continue;
            }

            if (isset($seen[$row['table_name']])) {
                $errors["rows.{$i}.table_name"] = $prefix . 'Tabel "' . $row['table_name'] . '" dipilih lebih dari sekali pada form ini.';
                continue;
            }
            $seen[$row['table_name']] = true;

            $introspectionError = $this->introspector->validateRegistration($row['table_name'], $row['sync_column']);
            if ($introspectionError !== null) {
                $errors["rows.{$i}.table_name"] = $prefix . $introspectionError;
            }
        }

        if ($errors !== []) {
            return redirect()->to(base_url('watched-tables/new'))
                ->withInput()
                ->with('validation_errors', $errors);
        }

        $ids = [];
        $this->watchedModel->db->transStart();
        foreach ($rows as $row) {
            $ids[] = (int) $this->watchedModel->insert($row);
        }
        $this->watchedModel->db->transComplete();

        if (! $this->watchedModel->db->transStatus()) {
            return redirect()->to(base_url('watched-tables/new'))
                ->withInput()
                ->with('validation_errors', ['rows' => 'Gagal menyimpan data; tidak ada tabel yang didaftarkan.']);
        }

        // Check sekali per tabel aktif supaya langsung muncul di dashboard.
        foreach ($ids as $insertedId) {
            $table = $this->watchedModel->find($insertedId);
            if ($table !== null && (int) $table['is_active'] === 1) {
                try {
                    Services::freshnessChecker()->checkTable($table);
                } catch (\Throwable $e) {
                    // lihat store()
                }
            }
        }

        $count = count($ids);

        return redirect()->to(base_url('watched-tables'))
            ->with('flash_success', $count === 1
                ? 'Tabel "' . $rows[0]['label'] . '" berhasil didaftarkan.'
                : $count . ' tabel berhasil didaftarkan.');
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
        return $this->normalizeRow([
            'table_name'          => $this->request->getPost('table_name'),
            'label'               => $this->request->getPost('label'),
            'sync_column'         => $this->request->getPost('sync_column'),
            'stale_after_minutes' => $this->request->getPost('stale_after_minutes'),
            'is_active'           => $this->request->getPost('is_active'),
        ]);
    }

    private function normalizeRow(array $raw): array
    {
        return [
            'table_name'          => strtolower(trim((string) ($raw['table_name'] ?? ''))),
            'label'               => trim((string) ($raw['label'] ?? '')),
            'sync_column'         => trim((string) ($raw['sync_column'] ?? '')),
            'stale_after_minutes' => trim((string) ($raw['stale_after_minutes'] ?? '')),
            'is_active'           => isset($raw['is_active']) ? '1' : '0',
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
