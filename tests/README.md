# Test MD-Bridge

Panduan menjalankan dan menulis test untuk MD-Bridge. Suite memakai **PHPUnit 9.6**
dan database **SQLite in-memory** untuk feature test (tanpa menyentuh MySQL / sumber remote).

## Menjalankan

```bash
# Seluruh suite
php vendor/bin/phpunit --no-coverage

# Ringkas (nama test per baris)
php vendor/bin/phpunit --no-coverage --testdox

# Satu file / satu folder
php vendor/bin/phpunit tests/feature/HistoryPaginationTest.php --no-coverage
php vendor/bin/phpunit tests/feature --no-coverage
```

Konfigurasi ada di `phpunit.xml.dist` (root). Saat `CI_ENVIRONMENT = testing`,
`app/Config/Database.php` otomatis memakai group `tests` (SQLite `:memory:`,
`DBPrefix = db_`) sehingga data live tidak pernah tersentuh.

## Struktur

```
tests/
├── _support/
│   ├── MonitoringFixtureTrait.php   # fixture utama feature test monitoring
│   ├── FixtureFreshnessChecker.php  # checker palsu (tidak konek remote)
│   ├── Database/ Libraries/ Models/ # helper tambahan
├── feature/                         # test HTTP end-to-end (request → response)
│   ├── MonitoringMatrixTest.php     # render matrix, Check All/Now, riwayat, logs
│   ├── HistoryPaginationTest.php    # pagination + filter status riwayat
│   ├── WatchedTableFormTest.php     # CRUD watched_tables
│   ├── MonitoringWorkflowTest.php   # halaman workflow (Drawflow)
│   ├── DashboardAnalyticsTest.php   # analytics dashboard
│   └── LegacyRoutesRemovedTest.php  # memastikan route pipeline lama nonaktif
├── unit/                            # unit murni
└── database/                        # test terkait migrasi/DB
```

## Pola Feature Test (`MonitoringFixtureTrait`)

Hampir semua feature test monitoring memakai trait ini. Pola dasarnya:

```php
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\MonitoringFixtureTrait;

final class ContohTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use MonitoringFixtureTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMonitoringFixture(); // buat tabel + seed source npd & sap
    }

    public function testContoh(): void
    {
        $tableId = $this->insertWatchedTable(['label' => 'Pesanan FX']);
        $this->insertSnapshot([
            'watched_table_id' => $tableId,
            'source_id'        => $this->sourceId('npd'),
            'status'           => 'OK',
        ]);

        $result = $this->withSession($this->authSession())
            ->get('monitoring/history/' . $tableId . '/' . $this->sourceId('npd'));

        $result->assertOK();
        $this->assertBodySee('Pesanan FX', $result);
        $this->assertStatusBadge($result, 'OK');
    }
}
```

Helper yang disediakan trait:

| Helper | Fungsi |
|--------|--------|
| `setUpMonitoringFixture()` | Drop + buat 4 tabel monitoring, seed source `npd` (id 1) & `sap` (id 2), matikan CSRF filter, suntik introspector/checker palsu. |
| `authSession()` | Array sesi login (`['auth_logged_in' => true]`). |
| `sourceId('npd'\|'sap')` | Ambil id source hasil seed. |
| `insertWatchedTable([...])` | Insert watched table (default `fx_orders`). Mengembalikan id. |
| `insertSnapshot([...])` / `insertHistory([...])` | Insert baris snapshot / riwayat. |
| `assertBodySee()` / `assertBodyNotSee()` | Cek substring di body response. |
| `assertStatusBadge($result, 'OK')` | Cek badge status (regex, tahan whitespace). |
| `countRows('check_history')` | Hitung baris sebuah tabel. |

## Gotcha (WAJIB diperhatikan)

- **Reset cache metadata setelah DDL.** Koneksi SQLite shared men-cache `listTables()` /
  `getFieldNames()` di `$db->dataCache`. Trait sudah memanggil `$this->fixtureDb->dataCache = []`
  setelah membuat tabel. Jika Anda membuat tabel sendiri di luar trait, lakukan hal yang sama,
  jika tidak tabel baru bisa "tak terlihat".
- **`TestResponse` tidak punya `getBody()`.** Gunakan `$result->response()->getBody()`
  (atau helper `assertBodySee()` / `assertStatusBadge()` di trait). Jangan panggil
  `$result->getBody()`.
- **Jangan andalkan `assertSee()` DOMParser.** Pada CI 4.1.9 + PHP 8.4, DOMParser bermasalah;
  pakai assertion berbasis body mentah dari trait.
- **`mb_convert_encoding(HTML-ENTITIES)` deprecation** sudah "diserap" sekali di
  `setUpMonitoringFixture()` — jangan menambahkannya lagi secara manual.
- **DBPrefix `db_`.** Nama tabel fisik di SQLite tests berawalan `db_` (mis. `db_check_history`),
  tapi query lewat Model/Builder tetap memakai nama logis (`check_history`).

## Live Database (opsional)

Untuk test yang butuh MySQL nyata, salin `phpunit.xml.dist` → `phpunit.xml`, isi detail group
`tests`, dan pastikan `phpunit.xml` masuk `.gitignore`. Secara default suite tidak memerlukan
koneksi live.
