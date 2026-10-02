<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\MonitoringFixtureTrait;

/**
 * Feature test form registry watched tables:
 * validasi unik, introspeksi tabel/kolom terhadap source,
 * toggle nonaktif, dan check sekali pasca-simpan.
 *
 * @internal
 */
final class WatchedTableFormTest extends CIUnitTestCase
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

    public function testIndexListsRegisteredTables()
    {
        $id = $this->insertWatchedTable(['label' => 'Pesanan FX']);

        $result = $this->withSession($this->authSession())->get('watched-tables');

        $result->assertOK();
        $this->assertBodySee('Pesanan FX', $result);
        $this->assertBodySee('fx_orders', $result);
        $this->assertGreaterThan(0, $id);
    }

    public function testCreateFormShowsSourceTablesInDropdown()
    {
        $result = $this->withSession($this->authSession())->get('watched-tables/new');

        $result->assertOK();
        // fx_orders berasal dari introspeksi source fixture (bukan hardcode).
        $this->assertBodySee('fx_orders', $result);
    }

    public function testUnauthenticatedUserIsRedirectedToLogin()
    {
        $result = $this->get('watched-tables');

        $result->assertRedirect();
        $result->assertRedirectTo(base_url('login'));
    }

    // ------------------------------------------------------------------
    // Store: valid
    // ------------------------------------------------------------------

    public function testStoreValidRegistrationCreatesRowAndRunsInitialCheck()
    {
        $result = $this->withSession($this->authSession())->post('watched-tables', [
            'table_name'          => 'fx_orders',
            'label'               => 'FX Orders',
            'sync_column'         => 'synced_at',
            'stale_after_minutes' => '1440',
            'is_active'           => '1',
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('flash_success');

        $this->assertSame(1, $this->countRows('watched_tables'));

        $row = $this->fixtureDb->table('watched_tables')->get()->getRowArray();
        $this->assertSame('fx_orders', $row['table_name']);
        $this->assertSame(1, (int) $row['is_active']);

        // Check sekali pasca-simpan: 1 tabel × 2 source fixture = 2 history.
        $this->assertSame(2, $this->countRows('check_history'));
        $this->assertSame(2, $this->countRows('table_snapshots'));
        $statuses = $this->fixtureDb->table('table_snapshots')
            ->select('DISTINCT status', false)
            ->get()->getResultArray();
        $this->assertSame('OK', $statuses[0]['status']);
    }

    public function testStoreWithoutActiveFlagRegistersInactiveTable()
    {
        $result = $this->withSession($this->authSession())->post('watched-tables', [
            'table_name'          => 'fx_orders',
            'label'               => 'FX Orders (nonaktif)',
            'sync_column'         => 'synced_at',
            'stale_after_minutes' => '1440',
        ]);

        $result->assertRedirect();
        $this->assertSame(1, $this->countRows('watched_tables'));

        $row = $this->fixtureDb->table('watched_tables')->get()->getRowArray();
        $this->assertSame(0, (int) $row['is_active']);
        // Tabel nonaktif tidak di-check saat registrasi.
        $this->assertSame(0, $this->countRows('check_history'));
    }

    // ------------------------------------------------------------------
    // Store: ditolak
    // ------------------------------------------------------------------

    public function testStoreRejectsTableMissingFromAllSources()
    {
        $result = $this->withSession($this->authSession())->post('watched-tables', [
            'table_name'          => 'fx_tidak_ada',
            'label'               => 'Tidak Ada',
            'sync_column'         => 'synced_at',
            'stale_after_minutes' => '1440',
            'is_active'           => '1',
        ]);

        $result->assertRedirect();
        $this->assertSame(0, $this->countRows('watched_tables'));

        $errors = $this->flashErrors();
        $this->assertArrayHasKey('table_name', $errors);
        $this->assertStringContainsString('tidak ditemukan', $errors['table_name']);
    }

    public function testStoreRejectsUnknownSyncColumn()
    {
        $result = $this->withSession($this->authSession())->post('watched-tables', [
            'table_name'          => 'fx_orders',
            'label'               => 'FX Orders',
            'sync_column'         => 'kolom_ngawur',
            'stale_after_minutes' => '1440',
            'is_active'           => '1',
        ]);

        $result->assertRedirect();
        $this->assertSame(0, $this->countRows('watched_tables'));

        $errors = $this->flashErrors();
        $this->assertArrayHasKey('table_name', $errors);
        $this->assertStringContainsString('Kolom', $errors['table_name']);
    }

    public function testStoreRejectsDuplicateTableName()
    {
        $this->insertWatchedTable();

        $result = $this->withSession($this->authSession())->post('watched-tables', [
            'table_name'          => 'fx_orders',
            'label'               => 'Duplikat',
            'sync_column'         => 'synced_at',
            'stale_after_minutes' => '1440',
            'is_active'           => '1',
        ]);

        $result->assertRedirect();
        $this->assertSame(1, $this->countRows('watched_tables'));

        $errors = $this->flashErrors();
        $this->assertArrayHasKey('table_name', $errors);
        $this->assertStringContainsString('sudah terdaftar', $errors['table_name']);
    }

    public function testStoreRejectsMalformedStaleAfter()
    {
        $result = $this->withSession($this->authSession())->post('watched-tables', [
            'table_name'          => 'fx_orders',
            'label'               => 'FX Orders',
            'sync_column'         => 'synced_at',
            'stale_after_minutes' => '0',
            'is_active'           => '1',
        ]);

        $result->assertRedirect();
        $this->assertSame(0, $this->countRows('watched_tables'));

        $errors = $this->flashErrors();
        $this->assertArrayHasKey('stale_after_minutes', $errors);
    }

    // ------------------------------------------------------------------
    // Toggle / delete / AJAX columns
    // ------------------------------------------------------------------

    public function testToggleFlipsActiveFlag()
    {
        $id = $this->insertWatchedTable();

        $result = $this->withSession($this->authSession())
            ->post('watched-tables/toggle/' . $id);

        $result->assertRedirect();
        $result->assertSessionHas('flash_success');

        $row = $this->fixtureDb->table('watched_tables')
            ->where('id', $id)->get()->getRowArray();
        $this->assertSame(0, (int) $row['is_active']);
    }

    public function testUpdatePersistsChangesWhenTableNameUnchanged()
    {
        // Regresi: rule is_unique memakai {id} — tanpa key 'id' di data,
        // update gagal diam-diam (CI 4.1.9).
        $id = $this->insertWatchedTable(['label' => 'Lama']);

        $result = $this->withSession($this->authSession())->post('watched-tables/update/' . $id, [
            'table_name'          => 'fx_orders',
            'label'               => 'Baru',
            'sync_column'         => 'synced_at',
            'stale_after_minutes' => '720',
            'is_active'           => '1',
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('flash_success');

        $row = $this->fixtureDb->table('watched_tables')
            ->where('id', $id)->get()->getRowArray();
        $this->assertSame('Baru', $row['label']);
        $this->assertSame(720, (int) $row['stale_after_minutes']);
    }

    public function testSoftDeleteKeepsHistoryButDeactivates()
    {
        $id        = $this->insertWatchedTable();
        $sourceId  = $this->sourceId('npd');
        $this->insertHistory([
            'watched_table_id' => $id,
            'source_id'        => $sourceId,
            'status'           => 'OK',
        ]);

        $result = $this->withSession($this->authSession())
            ->post('watched-tables/delete/' . $id);

        $result->assertRedirect();
        $row = $this->fixtureDb->table('watched_tables')
            ->where('id', $id)->get()->getRowArray();
        $this->assertSame(0, (int) $row['is_active']);
        // Riwayat tetap ada (soft delete, bukan hard delete).
        $this->assertSame(1, $this->countRows('check_history'));
    }

    public function testColumnsEndpointReturnsFixtureColumns()
    {
        $result = $this->withSession($this->authSession())
            ->withHeaders(['Accept' => 'application/json'])
            ->get('watched-tables/columns?table=fx_orders');

        $result->assertOK();
        $payload = json_decode($result->response()->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertContains('synced_at', $payload['columns']);
        $this->assertContains('updated_at', $payload['columns']);
    }

    // ------------------------------------------------------------------
    // Filter database (introspeksi per source) — UX saja, bukan skema
    // ------------------------------------------------------------------

    public function testCreateFormShowsDatabaseFilterOptions()
    {
        $result = $this->withSession($this->authSession())->get('watched-tables/new');

        $result->assertOK();
        $this->assertBodySee('Semua source', $result);
        $this->assertBodySee('NPD (RDS)', $result);
        $this->assertBodySee('SAP', $result);
    }

    public function testCreateFormScopedToUnknownSourceShowsEmptyTableList()
    {
        $result = $this->withSession($this->authSession())
            ->get('watched-tables/new?source=zona_nggakada');

        $result->assertOK();
        // Source tidak dikenal → lingkup introspeksi kosong.
        $this->assertBodyNotSee('fx_orders', $result);
    }

    public function testTablesEndpointReturnsFixtureTablesForUnionAndScope()
    {
        // Nama fisik ber-DBPrefix (mis. db_fx_orders) — cocokkan suffix.
        $hasFxOrders = static function (array $tables): bool {
            foreach ($tables as $name) {
                if (str_ends_with((string) $name, 'fx_orders')) {
                    return true;
                }
            }

            return false;
        };

        $union = $this->withSession($this->authSession())
            ->withHeaders(['Accept' => 'application/json'])
            ->get('watched-tables/tables');

        $union->assertOK();
        $payload = json_decode($union->response()->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertTrue($hasFxOrders($payload['tables']), 'Union seharusnya memuat fx_orders.');

        $scoped = $this->withSession($this->authSession())
            ->withHeaders(['Accept' => 'application/json'])
            ->get('watched-tables/tables?source=npd');

        $scoped->assertOK();
        $payload = json_decode($scoped->response()->getBody(), true);
        $this->assertTrue($hasFxOrders($payload['tables']), 'Scope npd seharusnya memuat fx_orders.');

        $unknown = $this->withSession($this->authSession())
            ->withHeaders(['Accept' => 'application/json'])
            ->get('watched-tables/tables?source=zona_nggakada');

        $unknown->assertOK();
        $payload = json_decode($unknown->response()->getBody(), true);
        $this->assertSame([], $payload['tables']);
    }

    public function testColumnsEndpointScopesToSource()
    {
        $result = $this->withSession($this->authSession())
            ->withHeaders(['Accept' => 'application/json'])
            ->get('watched-tables/columns?table=fx_orders&source=zona_nggakada');

        $result->assertOK();
        $payload = json_decode($result->response()->getBody(), true);
        $this->assertSame([], $payload['columns']);
    }
}
