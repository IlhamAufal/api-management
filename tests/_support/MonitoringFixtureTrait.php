<?php

namespace Tests\Support;

use CodeIgniter\Database\BaseConnection;

/**
 * Fixture SQLite in-memory (group `tests`) untuk feature test monitoring:
 * membuat 4 tabel monitoring + 2 source fixture, mematikan CSRF filter,
 * dan menyuntik fake introspector/checker yang tidak pernah menyentuh
 * koneksi remote (semua kode source diarahkan ke SQLite tests ini).
 *
 * Dpakai via `use MonitoringFixtureTrait;` + `setUpMonitoringFixture()`
 * di dalam setUp() setelah parent::setUp().
 */
trait MonitoringFixtureTrait
{
    /** Urutan drop: child table dulu (FK snapshot), lalu parent. */
    private const MONITORING_TABLES = [
        'table_snapshots',
        'check_history',
        'watched_tables',
        'sources',
    ];

    private const REMOTE_FIXTURE_TABLES = ['fx_orders'];

    /** @var BaseConnection */
    protected $fixtureDb;

    protected function setUpMonitoringFixture(): void
    {
        // PHP 8.4 menaikkan deprecation mb_convert_encoding(HTML-ENTITIES)
        // hanya sekali per proses; serap di sini (error suppressed) supaya
        // pemakaian pertama oleh DOMParser::withString tidak meledak
        // menjadi ErrorException dari handler error CodeIgniter.
        @mb_convert_encoding('a', 'HTML-ENTITIES', 'UTF-8');

        $this->fixtureDb = db_connect();

        // SQLite in-memory dipakai bersama antar test — buang sisa dulu.
        foreach ($this->monitoringTables() as $table) {
            $this->fixtureDb->query('DROP TABLE IF EXISTS ' . $this->ident($table));
        }

        $this->createMonitoringTables();
        $this->createRemoteFixtureTables();
        $this->seedSources();

        // CI men-cache listTables()/getFieldNames() di $dataCache koneksi
        // shared. Test lain (mis. migrasi ExampleDatabaseTest) bisa mengisi
        // cache itu lebih dulu — reset supaya tabel fixture tetap terlihat.
        $this->fixtureDb->dataCache = [];

        // Matikan CSRF filter global supaya POST feature test langsung jalan.
        $filters = config('Filters');
        $filters->globals['before'] = array_values(array_filter(
            $filters->globals['before'],
            static fn ($filter) => $filter !== 'csrf'
        ));

        // Introspeksi & check: semua source diarahkan ke SQLite tests ini.
        $resolver = static fn (string $code): BaseConnection => db_connect();

        \Config\Services::injectMock('sourceIntrospector', new \App\Libraries\Monitoring\SourceIntrospector($resolver));
        \Config\Services::injectMock('freshnessChecker', new FixtureFreshnessChecker($resolver));
    }

    // ------------------------------------------------------------------
    // Helpers untuk test
    // ------------------------------------------------------------------

    protected function authSession(): array
    {
        return ['auth_logged_in' => true];
    }

    protected function monitoringDb(): BaseConnection
    {
        return $this->fixtureDb;
    }

    protected function insertSource(array $overrides = []): int
    {
        $row = $overrides + [
            'code'      => 'npd',
            'label'     => 'NPD',
            'is_active' => 1,
        ];

        $this->fixtureDb->table('sources')->insert($row);

        return (int) $this->fixtureDb->insertID();
    }

    protected function sourceId(string $code): int
    {
        $row = $this->fixtureDb->table('sources')
            ->where('code', $code)
            ->get()
            ->getRow();

        $this->assertNotNull($row, "Source fixture '{$code}' tidak ada.");

        return (int) $row->id;
    }

    protected function deleteSource(string $code): void
    {
        $this->fixtureDb->table('sources')->where('code', $code)->delete();
    }

    /**
     * Badge status dirender sebagai `> \n STATUS \n <` di dalam span —
     * cocokkan dengan whitespace fleksibel supaya tidak ketemu di teks biasa.
     * (assertSee DOMParser CI 4.1.9 rusak di PHP 8.4 — pakai body mentah.)
     */
    protected function assertStatusBadge(\CodeIgniter\Test\TestResponse $result, string $status): void
    {
        $this->assertMatchesRegularExpression(
            '/>\s*' . preg_quote($status, '/') . '\s*</',
            $result->response()->getBody(),
            "Badge status {$status} tidak tampil di halaman."
        );
    }

    protected function assertBodySee(string $needle, \CodeIgniter\Test\TestResponse $result): void
    {
        $this->assertStringContainsString($needle, $result->response()->getBody());
    }

    protected function assertBodyNotSee(string $needle, \CodeIgniter\Test\TestResponse $result): void
    {
        $this->assertStringNotContainsString($needle, $result->response()->getBody());
    }

    protected function insertWatchedTable(array $overrides = []): int
    {
        $row = $overrides + [
            'table_name'          => 'fx_orders',
            'label'               => 'FX Orders',
            'sync_column'         => 'synced_at',
            'stale_after_minutes' => 1440,
            'is_active'           => 1,
        ];

        $this->fixtureDb->table('watched_tables')->insert($row);

        return (int) $this->fixtureDb->insertID();
    }

    protected function insertSnapshot(array $overrides): void
    {
        $this->fixtureDb->table('table_snapshots')->insert($overrides + [
            'row_count'      => 10,
            'last_synced_at' => date('Y-m-d H:i:s', time() - 3600),
            'error_message'  => null,
            'checked_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    protected function insertHistory(array $overrides): void
    {
        $this->fixtureDb->table('check_history')->insert($overrides + [
            'trigger_type'   => 'MANUAL_UI',
            'row_count'      => 10,
            'last_synced_at' => date('Y-m-d H:i:s', time() - 3600),
            'error_message'  => null,
            'executed_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    protected function countRows(string $table): int
    {
        return (int) $this->fixtureDb->table($table)->countAllResults();
    }

    protected function flashErrors(): array
    {
        $errors = \Config\Services::session()->getFlashdata('validation_errors');

        return is_array($errors) ? $errors : [];
    }

    // ------------------------------------------------------------------
    // DDL + seed
    // ------------------------------------------------------------------

    /** @return string[] termasuk remote fixture table */
    private function monitoringTables(): array
    {
        return array_merge(self::MONITORING_TABLES, self::REMOTE_FIXTURE_TABLES);
    }

    private function ident(string $name): string
    {
        return $this->fixtureDb->protectIdentifiers($name, true, null, false);
    }

    private function createMonitoringTables(): void
    {
        $this->fixtureDb->query(<<<'SQL'
CREATE TABLE db_sources (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    label TEXT NOT NULL,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NULL
);
SQL
        );

        $this->fixtureDb->query(<<<'SQL'
CREATE TABLE db_watched_tables (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_name TEXT NOT NULL UNIQUE,
    label TEXT NOT NULL,
    sync_column TEXT NOT NULL DEFAULT 'synced_at',
    stale_after_minutes INTEGER NOT NULL DEFAULT 1440,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NULL
);
SQL
        );

        $this->fixtureDb->query(<<<'SQL'
CREATE TABLE db_check_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    watched_table_id INTEGER NOT NULL,
    source_id INTEGER NOT NULL,
    trigger_type TEXT NOT NULL DEFAULT 'MANUAL_UI',
    row_count INTEGER NULL,
    last_synced_at TEXT NULL,
    status TEXT NOT NULL,
    error_message TEXT NULL,
    executed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
SQL
        );

        $this->fixtureDb->query(<<<'SQL'
CREATE TABLE db_table_snapshots (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    watched_table_id INTEGER NOT NULL,
    source_id INTEGER NOT NULL,
    row_count INTEGER NULL,
    last_synced_at TEXT NULL,
    status TEXT NOT NULL,
    error_message TEXT NULL,
    checked_at TEXT NOT NULL,
    UNIQUE (watched_table_id, source_id),
    FOREIGN KEY (watched_table_id) REFERENCES db_watched_tables (id) ON DELETE CASCADE,
    FOREIGN KEY (source_id) REFERENCES db_sources (id) ON DELETE CASCADE
);
SQL
        );
    }

    private function createRemoteFixtureTables(): void
    {
        // Tabel yang "ada di source" — synced 1 jam lalu -> OK (threshold 1440m).
        $this->fixtureDb->query('CREATE TABLE ' . $this->ident('fx_orders') . ' (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            synced_at TEXT NULL,
            updated_at TEXT NULL
        )');

        $recent = date('Y-m-d H:i:s', time() - 3600);
        $this->fixtureDb->query(
            'INSERT INTO ' . $this->ident('fx_orders') . ' (synced_at, updated_at) VALUES (?, ?)',
            [$recent, $recent]
        );
    }

    private function seedSources(): void
    {
        $this->insertSource(['code' => 'npd', 'label' => 'NPD (RDS)']);
        $this->insertSource(['code' => 'sap', 'label' => 'SAP']);
    }
}
