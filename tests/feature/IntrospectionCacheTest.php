<?php

use App\Libraries\Monitoring\SourceIntrospector;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\MonitoringFixtureTrait;

/**
 * Cache introspeksi source (listTables/listColumns) — skrip remote
 * npd/sap tidak boleh dipanggil bila hasil cache masih hidup, dan
 * cache wajib dibuang saat source berubah (daftar union).
 *
 * @internal
 */
final class IntrospectionCacheTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use MonitoringFixtureTrait;

    /** @var string[] kunci cache yang harus dibersihkan antar test */
    private array $seededKeys = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMonitoringFixture();
    }

    protected function tearDown(): void
    {
        $cache = \Config\Services::cache();

        foreach ($this->seededKeys as $key) {
            $cache->delete($key);
        }

        $cache->delete('intro_index');
        $this->seededKeys = [];

        parent::tearDown();
    }

    private function seed(string $key, array $value): void
    {
        \Config\Services::cache()->save($key, $value, SourceIntrospector::CACHE_TTL);
        $this->seededKeys[] = $key;
    }

    // ------------------------------------------------------------------
    // Cache dibaca (tanpa introspeksi remote)
    // ------------------------------------------------------------------

    public function testTablesEndpointServesSeededCache()
    {
        $this->seed('intro_tables.npd', ['tabel_hasil_cache_saja']);

        $result = $this->withSession($this->authSession())
            ->get('watched-tables/tables?source=npd');

        $result->assertOK();
        $body = (string) $result->response()->getBody();
        $this->assertStringContainsString('tabel_hasil_cache_saja', $body);
    }

    public function testColumnsEndpointServesSeededCache()
    {
        $this->seed('intro_cols.tabel_dummy.npd', ['kolom_dari_cache']);

        $result = $this->withSession($this->authSession())
            ->get('watched-tables/columns?table=tabel_dummy&source=npd');

        $result->assertOK();
        $body = (string) $result->response()->getBody();
        $this->assertStringContainsString('kolom_dari_cache', $body);
    }

    // ------------------------------------------------------------------
    // Flush saat source berubah
    // ------------------------------------------------------------------

    public function testSourceMutationFlushesIntrospectionCache()
    {
        $cache = \Config\Services::cache();

        $this->seed('intro_tables.all', ['union_cache']);
        $this->seed('intro_tables.npd', ['npd_cache']);
        $this->seed('intro_cols.tabel_dummy.npd', ['kolom_cache']);

        $cache->save('intro_index', [
            'intro_tables.all',
            'intro_tables.npd',
            'intro_cols.tabel_dummy.npd',
        ], 60);

        $result = $this->withSession($this->authSession())->post('databases', [
            'code'  => 'zona_cache_flush',
            'label' => 'Zona Cache Flush',
        ]);

        $result->assertRedirect();

        // MockCache miss = null, FileHandler miss = false — keduanya kosong.
        $this->assertEmpty($cache->get('intro_tables.all'));
        $this->assertEmpty($cache->get('intro_tables.npd'));
        $this->assertEmpty($cache->get('intro_cols.tabel_dummy.npd'));
        $this->assertEmpty($cache->get('intro_index'));
    }

    public function testFixtureCacheMissFallsBackToRemoteIntrospection()
    {
        // Tidak ada seed: endpoint tetap jalan (remote/union apa adanya)
        // dan mengisi cache untuk pemakaian berikutnya.
        $result = $this->withSession($this->authSession())
            ->get('watched-tables/tables?source=npd');

        $result->assertOK();

        $cached = \Config\Services::cache()->get('intro_tables.npd');
        $this->assertIsArray($cached);
        $this->assertNotEmpty($cached);

        $this->seededKeys[] = 'intro_tables.npd';
    }
}
