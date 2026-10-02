<?php

use App\Libraries\Monitoring\CheckResult;
use App\Libraries\Monitoring\TableFreshnessChecker;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit test TableFreshnessChecker.
 *
 * Probe dijalankan terhadap koneksi SQLite in-memory (group `tests`)
 * dengan fixture tabel, supaya kasus OK/STALE/NEVER_SYNCED/MISSING_TABLE
 * bisa diuji tanpa menyentuh source remote. Persist di-capture lewat
 * subclass, jadi tidak ada tulis-betulan ke database manapun.
 *
 * @internal
 */
final class TableFreshnessCheckerTest extends CIUnitTestCase
{
    private const NOW = '2026-10-01 13:00:00';

    /** @var BaseConnection */
    private $fixtureDb;

    /** @var string[] nama tabel fixture yang dibuat */
    private $fixtures = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtureDb = db_connect();
        $this->createFixture('chk_ok', "'2026-10-01 12:00:00'");      // 1 jam lalu  -> OK
        $this->createFixture('chk_stale', "'2026-09-01 12:00:00'");    // 30 hari lalu -> STALE
        $this->createFixture('chk_null', 'NULL');                      // belum pernah -> NEVER_SYNCED
        $this->createFixture('chk_nocolumn', "'2026-10-01 12:00:00'", false);

        // CI men-cache listTables() di $dataCache koneksi shared — reset
        // supaya tabel fixture terlihat walau test lain mengisi cache dulu.
        $this->fixtureDb->dataCache = [];
    }

    protected function tearDown(): void
    {
        foreach ($this->fixtures as $name) {
            $this->fixtureDb->query('DROP TABLE IF EXISTS ' . $this->ident($name));
        }
        $this->fixtures = [];
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Fixture helpers
    // ------------------------------------------------------------------

    private function ident(string $name): string
    {
        return $this->fixtureDb->protectIdentifiers($name, true, null, false);
    }

    private function createFixture(string $name, string $syncedAtValue, bool $withSyncColumn = true): void
    {
        $columns = 'id INTEGER PRIMARY KEY';
        if ($withSyncColumn) {
            $columns .= ', synced_at DATETIME NULL';
        }

        $this->fixtureDb->query('CREATE TABLE ' . $this->ident($name) . ' (' . $columns . ')');
        $this->fixtures[] = $name;

        if ($withSyncColumn) {
            $this->fixtureDb->query(
                'INSERT INTO ' . $this->ident($name) . ' (synced_at) VALUES (' . $syncedAtValue . ')'
            );
        }
    }

    /**
     * Checker yang menyimpan hasil persist di memori (tanpa database).
     */
    private function makeChecker(?callable $resolver = null): TableFreshnessChecker
    {
        $checker = new class($resolver, $this->fixtureDb) extends TableFreshnessChecker {
            public array $snapshots = [];
            public array $history   = [];

            public function __construct($resolver, $local)
            {
                parent::__construct(
                    $resolver,
                    $local,
                    static fn () => TableFreshnessCheckerTest::NOW()
                );
            }

            protected function persist(CheckResult $result, string $triggerType): void
            {
                $row = $result->toArray() + ['trigger_type' => $triggerType];
                $this->snapshots[] = $row;
                $this->history[]   = $row;
            }
        };

        return $checker;
    }

    public static function NOW(): string
    {
        return self::NOW;
    }

    private function table(array $overrides = []): array
    {
        return $overrides + [
            'id'                  => 11,
            'table_name'          => 'chk_ok',
            'label'               => 'Fixture OK',
            'sync_column'         => 'synced_at',
            'stale_after_minutes' => 1440,
            'is_active'           => 1,
        ];
    }

    private function source(array $overrides = []): array
    {
        return $overrides + [
            'id'        => 3,
            'code'      => 'tests',
            'label'     => 'Fixture Source',
            'is_active' => 1,
        ];
    }

    private function resolverReturning(BaseConnection $db): callable
    {
        return static fn (string $code) => $db;
    }

    // ------------------------------------------------------------------
    // Status: OK / STALE / NEVER_SYNCED
    // ------------------------------------------------------------------

    public function testOkWhenSyncWithinThreshold()
    {
        $checker = $this->makeChecker($this->resolverReturning($this->fixtureDb));

        $result = $checker->check($this->table(), $this->source());

        $this->assertSame(CheckResult::OK, $result->status);
        $this->assertSame(1, $result->rowCount);
        $this->assertSame('2026-10-01 12:00:00', $result->lastSyncedAt);
        $this->assertNull($result->errorMessage);
    }

    public function testStaleWhenSyncOlderThanThreshold()
    {
        $checker = $this->makeChecker($this->resolverReturning($this->fixtureDb));

        $result = $checker->check($this->table(['table_name' => 'chk_stale']), $this->source());

        $this->assertSame(CheckResult::STALE, $result->status);
        $this->assertSame('2026-09-01 12:00:00', $result->lastSyncedAt);
        $this->assertNull($result->errorMessage);
    }

    public function testNeverSyncedWhenSyncColumnIsNull()
    {
        $checker = $this->makeChecker($this->resolverReturning($this->fixtureDb));

        $result = $checker->check($this->table(['table_name' => 'chk_null']), $this->source());

        $this->assertSame(CheckResult::NEVER_SYNCED, $result->status);
        $this->assertSame(1, $result->rowCount);
        $this->assertNull($result->lastSyncedAt);
    }

    public function testOkBecomesStaleExactlyWhenAgeExceedsThreshold()
    {
        // synced 2026-10-01 12:00, dicek 13:00 -> age 60 menit.
        $this->assertSame(
            CheckResult::OK,
            TableFreshnessChecker::resolveStatus('2026-10-01 12:00:00', self::NOW, 60)
        );
        $this->assertSame(
            CheckResult::STALE,
            TableFreshnessChecker::resolveStatus('2026-10-01 12:00:00', self::NOW, 59)
        );
    }

    // ------------------------------------------------------------------
    // Status: MISSING_TABLE
    // ------------------------------------------------------------------

    public function testMissingTableWhenTableAbsentInSource()
    {
        $checker = $this->makeChecker($this->resolverReturning($this->fixtureDb));

        $result = $checker->check($this->table(['table_name' => 'chk_tidak_ada']), $this->source());

        $this->assertSame(CheckResult::MISSING_TABLE, $result->status);
        $this->assertNull($result->rowCount);
        $this->assertStringContainsString('chk_tidak_ada', $result->errorMessage);
        $this->assertStringContainsString('tests', $result->errorMessage);
    }

    public function testMissingTableWhenSyncColumnAbsent()
    {
        $checker = $this->makeChecker($this->resolverReturning($this->fixtureDb));

        $result = $checker->check(
            $this->table(['table_name' => 'chk_nocolumn', 'sync_column' => 'synced_at']),
            $this->source()
        );

        $this->assertSame(CheckResult::MISSING_TABLE, $result->status);
        $this->assertStringContainsString('synced_at', $result->errorMessage);
    }

    // ------------------------------------------------------------------
    // Status: CONN_ERROR
    // ------------------------------------------------------------------

    public function testConnErrorWhenSourceConnectionFails()
    {
        $resolver = static function (string $code): BaseConnection {
            throw new RuntimeException('Gagal koneksi ke host ' . $code . ' (access denied).');
        };

        $checker = $this->makeChecker($resolver);
        $result    = $checker->check($this->table(), $this->source());

        $this->assertSame(CheckResult::CONN_ERROR, $result->status);
        $this->assertNull($result->rowCount);
        $this->assertStringContainsString('Gagal koneksi', $result->errorMessage);
    }

    public function testConnErrorNeverLeaksCredential()
    {
        $resolver = static function (): BaseConnection {
            throw new RuntimeException(
                'Access denied for user \'reader\'@\'10.0.0.1\' (using password: S3cr3t-Passw0rd) to database \'yp_npd\''
            );
        };

        $checker = $this->makeChecker($resolver);
        $result    = $checker->check($this->table(), $this->source(['code' => 'npd']));

        $this->assertSame(CheckResult::CONN_ERROR, $result->status);
        $this->assertStringNotContainsString('S3cr3t-Passw0rd', $result->errorMessage);
        $this->assertStringContainsString('[redacted]', $result->errorMessage);
    }

    public function testShortErrorTruncatesLongMessage()
    {
        $long = str_repeat('x', 300);
        $out  = TableFreshnessChecker::shortError($long);

        $this->assertLessThanOrEqual(181, mb_strlen($out));
        $this->assertStringEndsWith('…', $out);
    }

    // ------------------------------------------------------------------
    // Konfigurasi source & validasi identifier
    // ------------------------------------------------------------------

    public function testIsConfiguredGroupRequiresHostnameAndUsername()
    {
        $this->assertFalse(TableFreshnessChecker::isConfiguredGroup(null));
        $this->assertFalse(TableFreshnessChecker::isConfiguredGroup([]));
        $this->assertFalse(TableFreshnessChecker::isConfiguredGroup([
            'hostname' => 'db.example.com',
            'username' => '',
        ]));
        $this->assertTrue(TableFreshnessChecker::isConfiguredGroup([
            'hostname' => 'db.example.com',
            'username' => 'reader',
        ]));
    }

    public function testIsValidIdentifier()
    {
        $this->assertTrue(TableFreshnessChecker::isValidIdentifier('sap_material_master'));
        $this->assertTrue(TableFreshnessChecker::isValidIdentifier('Table2'));
        $this->assertFalse(TableFreshnessChecker::isValidIdentifier('tbl; DROP TABLE users'));
        $this->assertFalse(TableFreshnessChecker::isValidIdentifier('2fast'));
        $this->assertFalse(TableFreshnessChecker::isValidIdentifier(''));
    }

    // ------------------------------------------------------------------
    // Persist (captured) & logika multi-source
    // ------------------------------------------------------------------

    public function testPersistsSnapshotAndHistoryPerCheck()
    {
        $checker = $this->makeChecker($this->resolverReturning($this->fixtureDb));

        $checker->check($this->table(), $this->source());

        $snapshots = $checker->snapshots;
        $history   = $checker->history;

        $this->assertCount(1, $snapshots);
        $this->assertCount(1, $history);
        $this->assertSame(11, $snapshots[0]['watched_table_id']);
        $this->assertSame(3, $snapshots[0]['source_id']);
        $this->assertSame(CheckResult::OK, $snapshots[0]['status']);
        $this->assertSame(self::NOW, $snapshots[0]['checked_at']);
        $this->assertSame(TableFreshnessChecker::TRIGGER_MANUAL, $history[0]['trigger_type']);
    }

    public function testRunForSingleSourceDoesNotAssumeMinimumTwoSources()
    {
        $checker = $this->makeChecker($this->resolverReturning($this->fixtureDb));

        $results = $checker->runFor([$this->table()], [$this->source()]);

        $this->assertCount(1, $results);
        $this->assertCount(1, $checker->snapshots);
    }

    public function testRunForTwoSourcesWritesOneResultEach()
    {
        $checker = $this->makeChecker($this->resolverReturning($this->fixtureDb));

        $results = $checker->runFor(
            [$this->table()],
            [$this->source(['id' => 3, 'code' => 'npd']), $this->source(['id' => 4, 'code' => 'sap'])]
        );

        $this->assertCount(2, $results);
        $this->assertCount(2, $checker->snapshots);
        $this->assertSame(3, $results[0]->sourceId);
        $this->assertSame(4, $results[1]->sourceId);
    }

    public function testSnapshotUpsertUsesOnDuplicateKeyUpdate()
    {
        $sql = TableFreshnessChecker::buildSnapshotUpsert();

        $this->assertStringContainsString('INSERT INTO `table_snapshots`', $sql);
        $this->assertStringContainsString('ON DUPLICATE KEY UPDATE', $sql);
        $this->assertSame(7, substr_count($sql, '?'));
    }
}
