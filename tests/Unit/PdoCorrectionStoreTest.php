<?php

namespace Tests\Unit;

use App\Support\Corrections\CorrectionEvidence as E;
use App\Support\Corrections\PdoCorrectionStore;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\CorrectionFixtures;

/** SQL recording substitutes: these tests never create a database connection. */
class PdoCorrectionStoreTest extends TestCase
{
    private function store(RecordingCorrectionPdo $pdo): PdoCorrectionStore
    {
        return new PdoCorrectionStore($pdo, ['database' => 'sponsorflow_test', 'host' => '127.0.0.1', 'port' => '3306'], false);
    }

    public function test_read_only_snapshot_uses_no_locks_writes_or_commit(): void
    {
        $pdo = new RecordingCorrectionPdo;
        $store = $this->store($pdo);
        $result = $store->transaction(false, fn () => $store->catalog());
        $this->assertSame([CorrectionFixtures::program()], $result);
        $sql = implode("\n", $pdo->sql);
        $this->assertStringContainsString('SET TRANSACTION READ ONLY', $sql);
        $this->assertStringContainsString('ROLLBACK', $sql);
        $this->assertDoesNotMatchRegularExpression('/\b(?:INSERT|UPDATE|DELETE|UPSERT|TRUNCATE|GET_LOCK|COMMIT)\b/i', $sql);
    }

    public function test_store_rejects_write_methods_during_read_only_transaction(): void
    {
        $pdo = new RecordingCorrectionPdo;
        $store = $this->store($pdo);
        try {
            $store->transaction(false, fn () => $store->insertProgram(CorrectionFixtures::program()));
            $this->fail('Read-only store permitted a write.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Dry-run store cannot write.', $exception->getMessage());
        }
        $this->assertDoesNotMatchRegularExpression('/\b(?:INSERT|UPDATE|DELETE)\b/i', implode("\n", $pdo->sql));
        $this->assertContains('ROLLBACK', $pdo->sql);
    }

    public function test_write_transaction_locks_catalog_and_rolls_back_on_failure(): void
    {
        $pdo = new RecordingCorrectionPdo;
        $store = $this->store($pdo);
        $caught = false;
        try {
            $store->transaction(true, function () use ($store): void {
                $store->catalog();
                throw new RuntimeException('synthetic failure');
            });
        } catch (RuntimeException $exception) {
            $caught = true;
            $this->assertSame('synthetic failure', $exception->getMessage());
        }
        $this->assertTrue($caught);
        $sql = implode("\n", $pdo->sql);
        $this->assertStringContainsString('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ', $sql);
        $this->assertStringContainsString('ORDER BY program_id FOR UPDATE', $sql);
        $this->assertStringContainsString('GET_LOCK', $sql);
        $this->assertStringContainsString('RELEASE_LOCK', $sql);
        $this->assertContains('ROLLBACK', $pdo->sql);
        $this->assertNotContains('COMMIT', $pdo->sql);
    }

    public static function unsafeGrants(): array
    {
        return [
            'original database' => ["GRANT ALL PRIVILEGES ON `sponsorflow`.* TO `fixture`@`localhost`"],
            'global' => ['GRANT ALL PRIVILEGES ON *.* TO `fixture`@`localhost`'],
            'underscore wildcard' => ['GRANT ALL PRIVILEGES ON `sponsorflow_test`.* TO `fixture`@`localhost`'],
            'role' => ['GRANT `some_role` TO `fixture`@`localhost`'],
            'grant option' => ['GRANT ALL PRIVILEGES ON `sponsorflow\\_test`.* TO `fixture`@`localhost` WITH GRANT OPTION'],
        ];
    }

    #[DataProvider('unsafeGrants')]
    public function test_test_account_must_have_only_exact_isolated_database_privileges(string $grant): void
    {
        $pdo = new RecordingCorrectionPdo;
        $pdo->grants = [$grant];
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('privileges');
        $this->store($pdo)->assertTestGrants();
    }

    public function test_literal_database_grant_is_accepted_without_writes(): void
    {
        $pdo = new RecordingCorrectionPdo;
        $this->store($pdo)->assertTestGrants();
        $this->assertCount(3, $pdo->sql);
        $this->assertStringStartsWith('SELECT ', $pdo->sql[0]);
        $this->assertSame('SHOW GRANTS FOR CURRENT_USER', $pdo->sql[2]);
    }

    public function test_table_trigger_blocks_before_begin_or_write(): void
    {
        $pdo = new RecordingCorrectionPdo;
        $pdo->triggers = [['TRIGGER_NAME' => 'unreviewed']];
        try {
            $this->store($pdo)->transaction(true, fn () => null);
            $this->fail('Unreviewed trigger was permitted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('triggers', $exception->getMessage());
        }
        $this->assertNotContains('BEGIN', $pdo->sql);
        $this->assertDoesNotMatchRegularExpression('/\b(?:INSERT|UPDATE|DELETE)\b/i', implode("\n", $pdo->sql));
    }
}

final class RecordingCorrectionPdo extends PDO
{
    public array $sql = [];
    public array $grants = ['GRANT USAGE ON *.* TO `fixture`@`localhost`',
        'GRANT ALL PRIVILEGES ON `sponsorflow\\_test`.* TO `fixture`@`localhost`'];
    public array $triggers = [];
    private bool $transaction = false;

    public function __construct() {}
    public function getAttribute(int $attribute): mixed { return $attribute === PDO::ATTR_DRIVER_NAME ? 'mysql' : null; }
    public function setAttribute(int $attribute, mixed $value): bool { return true; }
    public function inTransaction(): bool { return $this->transaction; }
    public function beginTransaction(): bool { $this->sql[] = 'BEGIN'; return $this->transaction = true; }
    public function commit(): bool { $this->sql[] = 'COMMIT'; $this->transaction = false; return true; }
    public function rollBack(): bool { $this->sql[] = 'ROLLBACK'; $this->transaction = false; return true; }
    public function exec(string $statement): int|false { $this->sql[] = $statement; return 0; }
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $this->sql[] = $query;
        return new RecordingCorrectionStatement($this, $query);
    }
    public function rows(string $query, array $bindings): array
    {
        if (str_starts_with($query, 'SELECT DATABASE()')) {
            return [['db' => 'sponsorflow_test', 'hostname' => 'synthetic-server', 'server_id' => 1,
                'port' => 3306, 'account' => 'fixture@localhost', 'version' => '10.4.32-MariaDB', 'init_connect' => '']];
        }
        if ($query === 'SHOW GRANTS FOR CURRENT_USER') {
            return array_map(fn ($g) => ['Grants' => $g], $this->grants);
        }
        if ($query === 'SELECT VERSION() AS version') { return [['version' => '10.4.32-MariaDB']]; }
        if (str_contains($query, 'information_schema.TABLES')) {
            return array_map(fn ($t) => ['TABLE_NAME' => $t, 'ENGINE' => 'InnoDB', 'TABLE_TYPE' => 'BASE TABLE'],
                ['academic_programs', 'sle_fhe_registries', 'audit_logs']);
        }
        if (str_starts_with($query, 'SELECT COLUMN_NAME')) {
            $columns = match ($bindings[0]) {
                'academic_programs' => array_keys(CorrectionFixtures::program()),
                'sle_fhe_registries' => E::REGISTRY_FIELDS,
                default => ['id', 'user_id', 'action', 'target_module', 'details', 'ip_address', 'user_agent', 'role', 'created_at', 'updated_at'],
            };
            return array_map(fn ($c) => ['COLUMN_NAME' => $c], $columns);
        }
        if (str_contains($query, 'information_schema.STATISTICS')) {
            return [['TABLE_NAME' => 'academic_programs', 'COLUMN_NAME' => 'program_id'],
                ['TABLE_NAME' => 'sle_fhe_registries', 'COLUMN_NAME' => 'id'], ['TABLE_NAME' => 'audit_logs', 'COLUMN_NAME' => 'id']];
        }
        if (str_starts_with($query, 'SELECT COLLATION_NAME')) {
            return array_fill(0, 3, ['COLLATION_NAME' => 'utf8mb4_unicode_ci']);
        }
        if (str_starts_with($query, 'SHOW VARIABLES')) { return [['Value' => 'OFF']]; }
        if (str_contains($query, 'information_schema.TRIGGERS')) { return $this->triggers; }
        if (str_contains($query, 'information_schema.KEY_COLUMN_USAGE')) { return []; }
        if (str_contains($query, 'GET_LOCK')) { return [['acquired' => 1]]; }
        if (str_contains($query, 'RELEASE_LOCK')) { return [['released' => 1]]; }
        if (str_contains($query, 'FROM academic_programs')) { return [CorrectionFixtures::program()]; }
        throw new RuntimeException('Unexpected SQL in database-free test.');
    }
}

final class RecordingCorrectionStatement extends PDOStatement
{
    private array $bindings = [];
    public function __construct(private RecordingCorrectionPdo $pdo, private string $query) {}
    public function execute(?array $params = null): bool { $this->bindings = $params ?? []; return true; }
    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array { return $this->pdo->rows($this->query, $this->bindings); }
}
