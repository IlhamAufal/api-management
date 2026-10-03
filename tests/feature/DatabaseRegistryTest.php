<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\MonitoringFixtureTrait;

/**
 * Feature test registry database (source): form tanpa kredensial,
 * validasi kode, auto-check pasca-simpan (PENDING_CONFIG bila group
 * belum ada), toggle, dan soft delete.
 *
 * @internal
 */
final class DatabaseRegistryTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use MonitoringFixtureTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMonitoringFixture();
    }

    // ------------------------------------------------------------------
    // Render
    // ------------------------------------------------------------------

    public function testIndexListsFixtureSources()
    {
        $result = $this->withSession($this->authSession())->get('databases');

        $result->assertOK();
        $this->assertBodySee('NPD (RDS)', $result);
        $this->assertBodySee('npd', $result);
        $this->assertBodySee('sap', $result);
    }

    public function testIndexShowsMissingConfigBadgeForUnknownGroup()
    {
        $this->insertSource(['code' => 'zona_tanpa_group', 'label' => 'Zona']);

        $result = $this->withSession($this->authSession())->get('databases');

        $result->assertOK();
        $this->assertBodySee('zona_tanpa_group', $result);
        $this->assertBodySee('Belum dikonfigurasi', $result);
        $this->assertBodySee('database.zona_tanpa_group.*', $result);
    }

    public function testCreateFormExplainsEnvOnlyCredentials()
    {
        $result = $this->withSession($this->authSession())->get('databases/new');

        $result->assertOK();
        $this->assertBodySee('.env', $result);
        $this->assertBodySee('Config/Database', $result);
        $this->assertBodySee('PENDING_CONFIG', $result);
    }

    public function testEditFormRendersWithReadonlyCode()
    {
        $id = $this->insertSource(['code' => 'zona_edit', 'label' => 'Zona Edit']);

        $result = $this->withSession($this->authSession())->get('databases/edit/' . $id);

        $result->assertOK();
        $this->assertBodySee('Zona Edit', $result);
        $this->assertBodySee('zona_edit', $result);
        $this->assertBodySee('readonly', $result);
    }

    public function testUnauthenticatedUserIsRedirectedToLogin()
    {
        $result = $this->get('databases');

        $result->assertRedirect();
        $result->assertRedirectTo(base_url('login'));
    }

    // ------------------------------------------------------------------
    // Store
    // ------------------------------------------------------------------

    public function testStoreValidCreatesSourceAndRunsInitialCheckAsPendingConfig()
    {
        $this->insertWatchedTable();

        $result = $this->withSession($this->authSession())->post('databases', [
            'code'      => 'zona_baru',
            'label'     => 'Zona Baru',
            'is_active' => '1',
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('flash_success');
        $this->assertSame(3, $this->countRows('sources'));

        // Group 'zona_baru' tidak ada di Config/Database.php → auto-check
        // berhenti di langkah 0: PENDING_CONFIG, tanpa percobaan koneksi.
        $this->assertSame(1, $this->countRows('check_history'));
        $history = $this->fixtureDb->table('check_history')->get()->getRowArray();
        $this->assertSame('PENDING_CONFIG', $history['status']);
        $this->assertSame('Harap tambahkan credential terlebih dahulu.', $history['error_message']);

        $snapshot = $this->fixtureDb->table('table_snapshots')->get()->getRowArray();
        $this->assertSame('PENDING_CONFIG', $snapshot['status']);

        $flash = \Config\Services::session()->getFlashdata('flash_success');
        $this->assertIsString($flash);
        $this->assertStringContainsString('PENDING_CONFIG', $flash);
    }

    public function testStoreWithoutActiveFlagCreatesInactiveSourceAndSkipsCheck()
    {
        $result = $this->withSession($this->authSession())->post('databases', [
            'code'  => 'zona_pasif',
            'label' => 'Zona Pasif',
        ]);

        $result->assertRedirect();
        $this->assertSame(3, $this->countRows('sources'));

        $row = $this->fixtureDb->table('sources')->where('code', 'zona_pasif')->get()->getRowArray();
        $this->assertSame(0, (int) $row['is_active']);
        $this->assertSame(0, $this->countRows('check_history'));
    }

    public function testStoreRejectsMalformedCode()
    {
        // Dinormalisasi strtolower dulu — 'my-db' tetap tidak valid (strip).
        $result = $this->withSession($this->authSession())->post('databases', [
            'code'  => 'my-db',
            'label' => 'My DB',
        ]);

        $result->assertRedirect();
        $this->assertSame(2, $this->countRows('sources'));

        $errors = $this->flashErrors();
        $this->assertArrayHasKey('code', $errors);
    }

    public function testStoreRejectsDuplicateCode()
    {
        $result = $this->withSession($this->authSession())->post('databases', [
            'code'  => 'npd',
            'label' => 'Duplikat',
        ]);

        $result->assertRedirect();
        $this->assertSame(2, $this->countRows('sources'));

        $errors = $this->flashErrors();
        $this->assertArrayHasKey('code', $errors);
        $this->assertStringContainsString('sudah dipakai', $errors['code']);
    }

    // ------------------------------------------------------------------
    // Update / toggle / soft delete
    // ------------------------------------------------------------------

    public function testUpdateChangesLabelAndKeepsCode()
    {
        $id = $this->insertSource(['code' => 'zona_ubah', 'label' => 'Lama']);

        $result = $this->withSession($this->authSession())->post('databases/update/' . $id, [
            'code'      => 'zona_ubah',
            'label'     => 'Baru',
            'is_active' => '1',
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('flash_success');

        $row = $this->fixtureDb->table('sources')->where('id', $id)->get()->getRowArray();
        $this->assertSame('zona_ubah', $row['code']);
        $this->assertSame('Baru', $row['label']);
    }

    public function testToggleFlipsActiveFlag()
    {
        $id = $this->insertSource(['code' => 'zona_toggle', 'label' => 'Toggle']);

        $result = $this->withSession($this->authSession())
            ->post('databases/toggle/' . $id);

        $result->assertRedirect();
        $result->assertSessionHas('flash_success');

        $row = $this->fixtureDb->table('sources')->where('id', $id)->get()->getRowArray();
        $this->assertSame(0, (int) $row['is_active']);
    }

    public function testSoftDeleteKeepsSourceRowButDeactivates()
    {
        $id = $this->insertSource(['code' => 'zona_hapus', 'label' => 'Hapus']);

        $result = $this->withSession($this->authSession())
            ->post('databases/delete/' . $id);

        $result->assertRedirect();
        $this->assertSame(3, $this->countRows('sources'));

        $row = $this->fixtureDb->table('sources')->where('id', $id)->get()->getRowArray();
        $this->assertSame(0, (int) $row['is_active']);
    }

    // ------------------------------------------------------------------
    // Uji koneksi (tanpa menyimpan)
    // ------------------------------------------------------------------

    public function testTestConnectionButtonVisibleOnFormsAndIndex()
    {
        $id = $this->insertSource(['code' => 'zona_uji', 'label' => 'Zona Uji']);

        $new = $this->withSession($this->authSession())->get('databases/new');
        $new->assertOK();
        $this->assertBodySee('Uji Koneksi', $new);

        $edit = $this->withSession($this->authSession())->get('databases/edit/' . $id);
        $edit->assertOK();
        $this->assertBodySee('Uji Koneksi', $edit);

        $index = $this->withSession($this->authSession())->get('databases');
        $index->assertOK();
        $this->assertBodySee('Uji koneksi', $index);
    }

    public function testTestConnectionFailsFastForUnknownGroup()
    {
        $result = $this->withSession($this->authSession())->post('databases/test', [
            'code'      => 'zona_nggakada',
            'return_to' => 'new',
        ]);

        $result->assertRedirectTo(base_url('databases/new'));

        $flash = \Config\Services::session()->getFlashdata('flash_error');
        $this->assertIsString($flash);
        $this->assertStringContainsString('Config/Database', $flash);
        $this->assertStringContainsString('database.zona_nggakada.*', $flash);
        // Tidak ada source tersimpan oleh uji koneksi.
        $this->assertSame(2, $this->countRows('sources'));
        $this->assertSame(0, $this->countRows('check_history'));
    }

    public function testTestConnectionReportsEmptyCredentials()
    {
        // Group 'tests' ada tapi username kosong (tanpa override .env) —
        // gagal sebelum percobaan koneksi jaringan.
        $result = $this->withSession($this->authSession())->post('databases/test', [
            'code'      => 'tests',
            'return_to' => 'index',
        ]);

        $result->assertRedirectTo(base_url('databases'));

        $flash = \Config\Services::session()->getFlashdata('flash_error');
        $this->assertIsString($flash);
        $this->assertStringContainsString('credential belum diisi', $flash);
        $this->assertStringContainsString('database.tests.', $flash);
    }

    public function testTestConnectionSucceedsForConfiguredGroup()
    {
        // Suntik credential sesaat ke group tests (driver SQLite — hermetik),
        // kembalikan setelah test walau gagal.
        $config   = config('Database');
        $original = $config->tests['username'];
        $config->tests['username'] = 'uji_koneksi';

        try {
            $result = $this->withSession($this->authSession())->post('databases/test', [
                'code'      => 'tests',
                'return_to' => 'index',
            ]);

            $result->assertRedirectTo(base_url('databases'));

            $flash = \Config\Services::session()->getFlashdata('flash_success');
            $this->assertIsString($flash);
            $this->assertStringContainsString('OK', $flash);
            $this->assertStringContainsString('terhubung', $flash);
            $this->assertStringContainsString('tabel terdeteksi', $flash);
        } finally {
            $config->tests['username'] = $original;
        }
    }

    public function testTestConnectionRejectsInvalidCode()
    {
        $result = $this->withSession($this->authSession())->post('databases/test', [
            'code' => 'my-db!',
        ]);

        $result->assertRedirectTo(base_url('databases'));

        $flash = \Config\Services::session()->getFlashdata('flash_error');
        $this->assertIsString($flash);
        $this->assertStringContainsString('tidak valid', $flash);
    }

    public function testTestConnectionReturnsToEditTarget()
    {
        $id = $this->insertSource(['code' => 'zona_return', 'label' => 'Return']);

        $result = $this->withSession($this->authSession())->post('databases/test', [
            'code'      => 'zona_return',
            'return_to' => 'edit/' . $id,
        ]);

        $result->assertRedirectTo(base_url('databases/edit/' . $id));

        // Kode tidak valid untuk connect tapi return tetap ke edit —
        // 'zona_return' tidak punya group → flash_error, bukan sukses.
        $flash = \Config\Services::session()->getFlashdata('flash_error');
        $this->assertIsString($flash);
        $this->assertStringContainsString('zona_return', $flash);
    }
}
