# MD-Bridge — Data Freshness Monitor

> Aplikasi internal untuk **memantau kesegaran (freshness) tabel master** di beberapa
> database sumber read-only. MD-Bridge memeriksa status tiap tabel yang dipantau
> (`watched_tables`) di tiap sumber (`sources`) lewat **dashboard monitoring per-source**,
> mencatat setiap pemeriksaan ke riwayat (`check_history`), dan menyimpan status terkini
> per sel di `table_snapshots`.

Dibangun di atas **CodeIgniter 4.1.9** (PHP 8.x) dengan UI TailAdmin (Tailwind CSS + Alpine.js,
aset sudah dikompilasi — tanpa build pipeline Node).

---

## Daftar Isi

- [Konsep Singkat](#konsep-singkat)
- [Tech Stack](#tech-stack)
- [Prasyarat](#prasyarat)
- [Quickstart](#quickstart)
- [Struktur Folder](#struktur-folder)
- [Model Data](#model-data)
- [Status Freshness](#status-freshness)
- [Konvensi Route](#konvensi-route)
- [Menambah Source / Watched Table](#menambah-source--watched-table)
- [Menjalankan Test](#menjalankan-test)
- [Catatan Keamanan](#catatan-keamanan)

---

## Konsep Singkat

- **Source** = satu database sumber read-only (mis. `npd`, `sap`). Tiap source punya
  `code` yang **harus sama** dengan nama connection group di `app/Config/Database.php`
  (dan key `database.<code>.*` di `.env`).
- **Watched table** = satu tabel master yang ingin dipantau (mis. `sap_material_master`),
  beserta kolom penanda sync (`sync_column`) dan ambang basi (`stale_after_minutes`).
- **Check** = pemeriksaan satu sel (watched table × source): menghitung baris + membaca
  waktu sync terakhir dari source, lalu menentukan status freshness. Hasil terkini
  disimpan ke `table_snapshots` (satu baris per sel) dan setiap pemeriksaan ditambahkan
  ke `check_history` (append-only).
- **Monitoring** (`/monitoring`, `/monitoring/source/{code}`) = dashboard per-source:
  tab bar berisi semua source aktif (tiap tab URL nyata), baris = watched tables pada
  source terpilih, sel = badge status + umur data terakhir, urut worst-first.
  Tombol **Check All** (navbar, AJAX), **Check Sumber Ini** (per source), dan **Check Sel** (per baris).

---

## Tech Stack

| Layer | Teknologi |
|-------|-----------|
| Framework | CodeIgniter 4.1.9 |
| PHP | 8.x |
| Database | MySQL (lokal: `api_management`) + 2 sumber read-only (`yp_npd`, `yp_sap`) |
| UI | TailAdmin (Tailwind CSS v4, dikompilasi ke `public/assets/css/`) |
| Interaktivitas | Alpine.js v3 (`public/assets/js/bundle.js`) |
| Ikon | Font Awesome (`public/assets/vendor/fontawesome`) |
| Diagram alur | Drawflow (`public/assets/vendor/drawflow`) |
| Test | PHPUnit 9.6 (SQLite in-memory untuk feature test) |

---

## Prasyarat

| Tools | Versi | Catatan |
|-------|-------|---------|
| PHP | 8.0+ | Ekstensi `intl`, `mbstring`, `mysqli`, `sqlite3` aktif |
| Composer | 2.x | Untuk `composer install` |
| MySQL | 5.7 / 8.0 | Database lokal untuk tabel aplikasi |

> Node.js **tidak** diperlukan — CSS/JS sudah dikompilasi di `public/assets/`.

---

## Quickstart

```bash
# 1. Install dependency PHP
composer install

# 2. Siapkan environment
#    Salin .env.example -> .env lalu isi kredensial lokal
#    (database.default.*, auth.seed*, dan database.npd.* / database.sap.*)
#    Windows: Copy-Item .env.example .env
#    Linux/macOS: cp .env.example .env

# 3. Buat skema database aplikasi
php spark migrate

# 4. Isi data awal (user admin + sources + 4 watched table) — idempoten
php spark db:seed DatabaseSeeder

# 5. Jalankan server pengembangan
php spark serve
```

Lalu buka `http://localhost:8080/`, login dengan kredensial dari `auth.seed*` di `.env`.

> **Catatan koneksi sumber:** fitur Check memerlukan akses ke database `npd` dan `sap`.
> Jika sumber tidak terjangkau (mis. VPN mati), pemeriksaan akan menghasilkan status
> `CONN_ERROR` — ini perilaku valid, bukan crash.

---

## Struktur Folder

```
api-management/
├── app/
│   ├── Config/
│   │   ├── Database.php          # group: default, npd, sap, tests
│   │   ├── Routes.php            # definisi route (lihat Konvensi Route)
│   │   └── Filters.php           # filter auth + CSRF
│   ├── Controllers/
│   │   ├── Auth.php              # login / attempt / logout
│   │   ├── Dashboard.php         # ringkasan analytics (dari check_history)
│   │   ├── MonitoringMatrix.php  # index / source / checkAll / checkCell / checkSource / history / workflow
│   │   ├── WatchedTableRegistry.php  # CRUD watched_tables
│   │   └── SyncLogs.php          # Execution logs (baca check_history)
│   ├── Database/
│   │   ├── Migrations/           # skema (lihat Model Data)
│   │   └── Seeds/                # UsersSeeder, SourcesSeeder, WatchedTablesSeeder
│   ├── Libraries/Monitoring/
│   │   ├── TableFreshnessChecker.php  # inti pemeriksaan freshness
│   │   ├── SourceIntrospector.php     # introspeksi tabel/kolom di source
│   │   ├── CheckResult.php            # value object hasil check
│   │   └── RelativeTime.php           # format "x menit lalu"
│   ├── Models/                   # Source / WatchedTable / TableSnapshot / CheckHistory / User
│   └── Views/
│       ├── layouts/main.php
│       ├── partials/             # sidebar, navbar, dsb.
│       ├── components/           # badge_status, breadcrumb, workflow_canvas
│       ├── pages/monitoring_source/   # dashboard per-source (index)
│       └── pages/monitoring_matrix/   # history, workflow
├── public/                       # front controller + aset terkompilasi
├── tests/                        # feature / unit / database (lihat tests/README.md)
├── .env.example                  # template environment (placeholder dummy)
└── composer.json
```

---

## Model Data

Skema final dibuat oleh migrasi `2026-10-02-100000_CreateMonitoringCoreTables`
(migrasi pipeline lama sudah di-drop oleh `2026-10-02-090000_DropLegacyPipelineTables`).

| Tabel | Fungsi |
|-------|--------|
| `sources` | Registry sumber read-only. `code` unik = nama connection group. |
| `watched_tables` | Tabel yang dipantau: `table_name`, `label`, `sync_column`, `stale_after_minutes`, `is_active`. |
| `table_snapshots` | Status **terkini** per sel (unik `watched_table_id` + `source_id`); FK cascade ke dua tabel di atas. |
| `check_history` | Riwayat **append-only** tiap pemeriksaan (tanpa FK agar riwayat tetap ada saat watched table dihapus). |
| `users` | Akun login. |

Seed awal:

- **Sources:** `npd` (YP NPD / `yp_npd`), `sap` (YP SAP / `yp_sap`).
- **Watched tables:** `sap_material_master`, `sap_customer_master` (sync_column `updated_at`);
  `sap_customer_material`, `sap_customer_sales_area` (sync_column `synced_at`). Semua
  `stale_after_minutes = 1440` (24 jam).

Seeder bersifat **idempoten** — menjalankan `db:seed` dua kali tidak membuat duplikat.

---

## Status Freshness

Setiap sel punya salah satu status berikut. Urutan "terburuk" (dipakai untuk mewarnai
ringkasan/agregat) dari paling parah ke paling baik:

```
CONN_ERROR > MISSING_TABLE > NEVER_SYNCED > STALE > OK
```

| Status | Arti |
|--------|------|
| `OK` | Tabel ada & sync terakhir masih di dalam ambang `stale_after_minutes`. |
| `STALE` | Tabel ada tapi sync terakhir melewati ambang basi. |
| `NEVER_SYNCED` | Tabel ada tapi belum pernah tercatat sync (atau belum pernah dicek). |
| `MISSING_TABLE` | Tabel tidak ditemukan di source. |
| `CONN_ERROR` | Gagal konek ke source (mis. VPN mati / kredensial salah). |

---

## Konvensi Route

Route didefinisikan di `app/Config/Routes.php` memakai **handler string**
(`'Controller::method'`) dan dikelompokkan dengan **prefix group** di dalam group filter `auth`:

```php
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->group('monitoring', static function ($routes) {
        $routes->get('', 'MonitoringMatrix::index');
        $routes->get('source/(:segment)', 'MonitoringMatrix::source/$1');
        $routes->post('check-cell/(:num)/(:num)', 'MonitoringMatrix::checkCell/$1/$2');
        $routes->post('check-source/(:segment)', 'MonitoringMatrix::checkSource/$1');
        $routes->get('history/(:num)/(:num)', 'MonitoringMatrix::history/$1/$2');
    });
});
```

Catatan:

- Parameter dari `(:num)` / `(:segment)` diteruskan ke argumen method lewat `/$1`.
- Route `login` / `logout` adalah satu-satunya yang publik; sisanya di balik filter `auth`.
- Route `check-table` lama (per tabel × semua source) sudah **dihapus** — aksi check kini
  per sel (`check-cell`) atau per source (`check-source`).

---

## Menambah Source / Watched Table

**Source baru:**

1. Tambahkan connection group di `app/Config/Database.php` (atau cukup via `.env`
   `database.<code>.*`) dengan nama group = `code` source.
2. Tambahkan barisnya di `app/Database/Seeds/SourcesSeeder.php` lalu
   `php spark db:seed SourcesSeeder`, atau tambahkan langsung lewat data `sources`.

**Watched table baru:**

- Lewat UI: menu **Watched Tables** → **New** (`/watched-tables/new`), atau
- Lewat seed: tambahkan entri di `WatchedTablesSeeder.php` lalu jalankan ulang seeder.

Setiap watched table perlu `table_name`, `label`, `sync_column` (kolom timestamp penanda
sync di source), dan `stale_after_minutes`.

---

## Menjalankan Test

```bash
php vendor/bin/phpunit --no-coverage
```

Feature test memakai database **SQLite in-memory** (group `tests`, `DBPrefix = db_`) dan
trait `tests/_support/MonitoringFixtureTrait.php`. Detail pola & gotcha ada di
[`tests/README.md`](tests/README.md).

---

## Catatan Keamanan

- `.env` **tidak** di-commit (ada di `.gitignore`); `.env.example` hanya berisi placeholder
  dummy — isi kredensial nyata hanya di `.env` lokal.
- CSRF aktif (mode cookie); form POST menyertakan token, request AJAX mengirim header
  `X-CSRF-TOKEN`.
- Koneksi `npd` / `sap` dipakai **read-only** untuk introspeksi; MD-Bridge tidak menulis
  ke database sumber.
- Selalu gunakan `esc()` saat menampilkan data ke view.
