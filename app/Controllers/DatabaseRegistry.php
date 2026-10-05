<?php

namespace App\Controllers;

use App\Libraries\MarkdownLite;
use App\Libraries\Monitoring\SourceIntrospector;
use App\Libraries\Monitoring\TableFreshnessChecker;
use App\Models\SourceModel;
use App\Models\WatchedTableModel;
use Config\Services;

/**
 * Registry UI: kelola database (source) yang dipantau.
 *
 * Form hanya meminta code + label — credential TIDAK diisi di sini:
 * developer menambahkan connection group di Config/Database.php dan
 * nilainya di .env (database.<code>.*). Selama group belum ada,
 * check akan berhenti di langkah 0 dengan status PENDING_CONFIG
 * (lihat plan bagian 8.4). Setelah simpan, source langsung di-check
 * sekali supaya statusnya langsung terlihat.
 */
class DatabaseRegistry extends BaseController
{
    private SourceModel $sourceModel;
    private WatchedTableModel $watchedModel;
    /** @var \CodeIgniter\Validation\Validation */
    private $formValidator;

    public function __construct()
    {
        $this->sourceModel  = new SourceModel();
        $this->watchedModel = new WatchedTableModel();
    }

    public function index()
    {
        $sources = [];
        foreach ($this->sourceModel->orderBy('id', 'ASC')->findAll() as $source) {
            $sources[] = $source + [
                'config' => $this->configState((string) $source['code']),
            ];
        }

        return view('pages/databases/index', [
            'title'    => 'Databases | MD-Bridge',
            'page'     => 'databases',
            'sources'  => $sources,
        ]);
    }

    public function create()
    {
        return $this->renderForm(null, $this->tutorialHtml());
    }

    public function edit($id)
    {
        $source = $this->sourceModel->find((int) $id);

        if ($source === null) {
            return redirect()->to(base_url('databases'))
                ->with('flash_error', 'Database tidak ditemukan.');
        }

        return $this->renderForm($source);
    }

    public function store()
    {
        $post = $this->normalizedPost();

        if (! $this->runValidation($post)) {
            return redirect()->to(base_url('databases/new'))
                ->withInput()
                ->with('validation_errors', $this->formValidator->getErrors());
        }

        $inserted = $this->sourceModel->insert($post);

        $source = $this->sourceModel->find((int) $inserted);
        $summary = $source !== null ? $this->initialCheck($source) : '';

        SourceIntrospector::flushCache();

        return redirect()->to(base_url('databases'))
            ->with('flash_success', 'Database "' . $post['label'] . '" berhasil didaftarkan.'
                . ($summary !== '' ? ' Check awal: ' . $summary . '.' : ''));
    }

    public function update($id)
    {
        $source = $this->sourceModel->find((int) $id);

        if ($source === null) {
            return redirect()->to(base_url('databases'))
                ->with('flash_error', 'Database tidak ditemukan.');
        }

        $post = $this->normalizedPost();

        if (! $this->runValidation($post, (int) $id)) {
            return redirect()->to(base_url('databases/edit/' . $id))
                ->withInput()
                ->with('validation_errors', $this->formValidator->getErrors());
        }

        // {id} pada rule is_unique hanya ter-replace bila data memuat
        // key 'id' (CI 4.1.9) — tanpa itu update gagal diam-diam.
        // 'id' tidak ada di allowedFields, jadi tidak ikut di-SET.
        $this->sourceModel->update((int) $id, $post + ['id' => (int) $id]);

        $source = $this->sourceModel->find((int) $id);
        $summary = $source !== null ? $this->initialCheck($source) : '';

        SourceIntrospector::flushCache();

        return redirect()->to(base_url('databases'))
            ->with('flash_success', 'Database "' . $post['label'] . '" berhasil diperbarui.'
                . ($summary !== '' ? ' Check awal: ' . $summary . '.' : ''));
    }

    /**
     * Toggle aktif/nonaktif. Source nonaktif tidak ikut checkAll berikutnya.
     */
    public function toggle($id)
    {
        $source = $this->sourceModel->find((int) $id);

        if ($source === null) {
            return redirect()->to(base_url('databases'))
                ->with('flash_error', 'Database tidak ditemukan.');
        }

        $newStatus = (int) $source['is_active'] === 1 ? 0 : 1;
        $this->sourceModel->update((int) $id, ['is_active' => $newStatus]);

        SourceIntrospector::flushCache();

        return redirect()->to(base_url('databases'))
            ->with('flash_success', 'Database "' . $source['label'] . '" '
                . ($newStatus === 1 ? 'diaktifkan' : 'dinonaktifkan') . '.');
    }

    /**
     * Soft delete: set is_active = 0. Sengaja tidak hard delete —
     * FK snapshot/history ON DELETE CASCADE akan menghapus riwayat.
     */
    public function delete($id)
    {
        $source = $this->sourceModel->find((int) $id);

        if ($source === null) {
            return redirect()->to(base_url('databases'))
                ->with('flash_error', 'Database tidak ditemukan.');
        }

        $this->sourceModel->update((int) $id, ['is_active' => 0]);

        SourceIntrospector::flushCache();

        return redirect()->to(base_url('databases'))
            ->with('flash_success', 'Database "' . $source['label'] . '" dinonaktifkan (soft delete) — riwayat check tetap tersimpan.');
    }

    /**
     * Uji koneksi tanpa menyimpan — dipanggil tombol "Uji Koneksi"
     * di form (new/edit, via formaction) dan ikon plug di list.
     * Menguji apa adanya: group config → credential → ping nyata.
     */
    public function test()
    {
        $code     = strtolower(trim((string) $this->request->getPost('code')));
        $back     = $this->testReturnUrl((string) $this->request->getPost('return_to'));
        $withOld  = static fn () => redirect()->to($back)->withInput();

        if ($code === '' || ! TableFreshnessChecker::isValidIdentifier($code)) {
            return $withOld()->with('flash_error', 'Uji koneksi dibatalkan: kode database tidak valid.');
        }

        [$ok, $message] = $this->pingGroup($code);

        if (! $ok) {
            return $withOld()->with('flash_error', 'Uji koneksi "' . $code . '" gagal — ' . $message);
        }

        return $withOld()->with('flash_success', 'Uji koneksi "' . $code . '" OK — ' . $message);
    }

    /**
     * Ping satu connection group: cek keberadaan group, kelengkapan
     * credential, lalu koneksi nyata + SELECT 1. Tidak menulis snapshot
     * atau riwayat (murni uji koneksi).
     *
     * @return array{0:bool,1:string} [sukses, pesan]
     */
    private function pingGroup(string $code): array
    {
        if (! TableFreshnessChecker::hasConnectionGroup($code)) {
            return [false, 'connection group belum ada di Config/Database.php — tambahkan group dan isi credential di .env (database.' . $code . '.*).'];
        }

        if (! TableFreshnessChecker::isConfiguredGroup(config('Database')->{$code} ?? null)) {
            return [false, 'credential belum diisi di .env (database.' . $code . '.hostname / .username).'];
        }

        try {
            $start = microtime(true);
            $db    = db_connect($code);
            $db->query('SELECT 1');
            $ms = (int) round((microtime(true) - $start) * 1000);

            $tableCount = count($db->listTables());

            return [true, 'terhubung (ping ' . $ms . ' ms — ' . $tableCount . ' tabel terdeteksi).'];
        } catch (\Throwable $e) {
            return [false, TableFreshnessChecker::shortError($e->getMessage()) . '.'];
        }
    }

    /**
     * Tujuan kembali setelah uji koneksi (whitelist — bukan URL bebas).
     */
    private function testReturnUrl(string $returnTo): string
    {
        if ($returnTo === 'new') {
            return base_url('databases/new');
        }

        if (preg_match('#^edit/([0-9]+)$#', $returnTo, $matches) === 1) {
            if ($this->sourceModel->find((int) $matches[1]) !== null) {
                return base_url('databases/edit/' . (int) $matches[1]);
            }
        }

        return base_url('databases');
    }

    /**
     * Check sekali semua watched table aktif terhadap source ini.
     * Pasca-daftar biasanya menghasilkan PENDING_CONFIG (group belum
     * ada) — itu memang tampilan yang diinginkan (plan bagian 8.4).
     *
     * @return string ringkasan, '' bila tidak ada sel / gagal
     */
    private function initialCheck(array $source): string
    {
        if ((int) $source['is_active'] !== 1) {
            return '';
        }

        try {
            $results = Services::freshnessChecker()->runFor(
                $this->watchedModel->getActiveTables(),
                [$source]
            );
        } catch (\Throwable $e) {
            // Check awal tidak menggagalkan simpan — status CONN_ERROR
            // akan muncul pada check berikutnya.
            return '';
        }

        if ($results === []) {
            return '';
        }

        $counts = [];
        foreach ($results as $result) {
            $counts[$result->status] = ($counts[$result->status] ?? 0) + 1;
        }

        $parts = [];
        foreach ($counts as $status => $count) {
            $parts[] = $count . ' ' . $status;
        }

        return count($results) . ' sel (' . implode(', ', $parts) . ')';
    }

    /**
     * Status konfigurasi credential source di Config/Database.php + .env
     * (tanpa mencoba koneksi).
     *
     * @return array{key:string,label:string,hint:string}
     */
    private function configState(string $code): array
    {
        if (! TableFreshnessChecker::hasConnectionGroup($code)) {
            return [
                'key'   => 'pending',
                'label' => 'Belum dikonfigurasi',
                'hint'  => 'Tambahkan connection group di Config/Database.php dan credential di .env (database.' . $code . '.*).',
            ];
        }

        $configured = TableFreshnessChecker::isConfiguredGroup(config('Database')->{$code} ?? null);

        if (! $configured) {
            return [
                'key'   => 'empty',
                'label' => 'Credential kosong',
                'hint'  => 'Group ada, tetapi hostname/username belum diisi di .env.',
            ];
        }

        return [
            'key'   => 'ok',
            'label' => 'Terkonfigurasi',
            'hint'  => 'Credential terisi — siap di-check.',
        ];
    }

    private function renderForm(?array $source, ?string $tutorial = null): string
    {
        $oldFlash = session()->getFlashdata('_ci_old_input');
        $oldInput = [];
        if (is_array($oldFlash)) {
            $oldInput = (array) ($oldFlash['post'] ?? ($oldFlash['old'] ?? []));
        }

        return view('pages/databases/form', [
            'title'      => ($source !== null ? 'Edit' : 'Tambah') . ' Database | MD-Bridge',
            'page'       => 'databases',
            'source'     => $source,
            'errors'     => (array) (session()->getFlashdata('validation_errors') ?: []),
            'oldInput'   => $oldInput,
            'formAction' => $source !== null
                ? base_url('databases/update/' . (int) $source['id'])
                : base_url('databases'),
            'isEdit'     => $source !== null,
            'cancelUrl'  => base_url('databases'),
            'tutorial'   => $tutorial,
        ]);
    }

    /**
     * Tutorial tambah database — di-parse dari markdown (single source
     * of truth di docs/, dokumen yang sama bisa diupload terpisah).
     * Bila file tidak ada, form tetap tampil tanpa panel tutorial.
     */
    private function tutorialHtml(): ?string
    {
        $path = ROOTPATH . 'docs' . DIRECTORY_SEPARATOR . 'TUTORIAL-TAMBAH-DATABASE.md';

        if (! is_file($path)) {
            return null;
        }

        $markdown = file_get_contents($path);

        if ($markdown === false || trim($markdown) === '') {
            return null;
        }

        return MarkdownLite::toHtml($markdown);
    }

    private function normalizedPost(): array
    {
        return [
            'code'      => strtolower(trim((string) $this->request->getPost('code'))),
            'label'     => trim((string) $this->request->getPost('label')),
            'is_active' => $this->request->getPost('is_active') !== null ? '1' : '0',
        ];
    }

    private function runValidation(array $post, ?int $id = null): bool
    {
        $id = (string) ($id ?? '');

        // Services::validation() adalah instance shared: error dari
        // validasi sebelumnya masih menempel dan membuat run() berikutnya
        // false walau aturan lolos — reset dulu.
        $this->formValidator = Services::validation();
        $this->formValidator->reset();
        $this->formValidator->setRules(
            [
                'code'      => "required|regex_match[/^[a-z][a-z0-9_]*$/]|max_length[50]|is_unique[sources.code,id,{$id}]",
                'label'     => 'required|string|max_length[100]',
                'is_active' => 'required|in_list[0,1]',
            ],
            [
                'code' => [
                    'is_unique'   => 'Kode database sudah dipakai.',
                    'regex_match' => 'Kode hanya boleh huruf kecil, angka, dan underscore (contoh: npd, sap, zona_erp).',
                ],
            ]
        );

        return $this->formValidator->run($post);
    }
}
