<?php

namespace Tests\Integration;

use App\Support\Corrections\CorrectionApproval;
use App\Support\Corrections\CorrectionEvidence as E;
use App\Support\Corrections\CorrectionExecutor;
use App\Support\Corrections\PdoCorrectionStore;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\CorrectionFixtures;

/**
 * OPT-IN ONLY. No Laravel bootstrap, .env, migrations, seeders or database cleanup.
 * Requires a separately provisioned EMPTY schema and a restricted MariaDB 10.4 account.
 * Test data is retained for inspection; never point this at a copied institutional dataset.
 */
class DatabaseCorrectionsMariaDbTest extends TestCase
{
    public function test_isolated_executor_transactions_idempotency_and_compensation(): void
    {
        if (getenv('SPONSORFLOW_D1_ISOLATED_TEST') !== 'AUTHORIZED_EMPTY_TEST_DATABASE') {
            $this->markTestSkipped('Separate isolation approval and explicit opt-in are required.');
        }
        $path = getenv('SPONSORFLOW_D1_TEST_CONFIG');
        E::require(is_string($path) && $path !== '', 'External isolated test configuration required.');
        $config = json_decode(E::file($path), true, 16, JSON_THROW_ON_ERROR);
        E::require(preg_match('/^sponsorflow_correction_test_[a-z0-9]+$/D', $config['database'] ?? '') === 1
            && in_array($config['host'] ?? null, ['127.0.0.1', 'localhost'], true)
            && ctype_digit((string) ($config['port'] ?? '')) && (int) $config['port'] > 0 && (int) $config['port'] <= 65535
            && preg_match('/^[a-zA-Z0-9_]+$/D', $config['username'] ?? '') === 1
            && strtolower($config['username']) !== 'root', 'Explicit local isolated database/account required.');
        E::require(E::text($config['isolation_review']['reference'] ?? null)
            && E::text($config['isolation_review']['reviewed_by'] ?? null)
            && ($config['isolation_review']['empty_schema_only'] ?? null) === true,
            'Independent isolation review is missing.');
        E::file($config['isolation_review']['path'] ?? '', $config['isolation_review']['sha256'] ?? '');

        // This entry point never reads Laravel configuration/cache or dispatches app events.
        // Consequently there are no Laravel storage/cache/queue/mail connections to isolate.
        $connect = fn () => new PDO(
            'mysql:host='.$config['host'].';port='.$config['port'].';dbname='.$config['database'].';charset=utf8mb4',
            $config['username'], $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_MULTI_STATEMENTS => false],
        );
        $pdo = $connect();
        $store = new PdoCorrectionStore($pdo, $config, false);
        $store->assertTestGrants(); // SELECT/SHOW only; no fixture writes until this passes.
        $identity = $store->identity();
        $this->assertSame($config['database'], $identity['database']);
        $this->assertNotSame('sponsorflow', strtolower($identity['database']));

        $protectedTables = [];
        $store->transaction(true, function () use ($pdo, &$protectedTables): void {
            $tables = $pdo->query('SELECT TABLE_NAME,TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($tables as $table) {
                E::require($table['TABLE_TYPE'] === 'BASE TABLE'
                    && preg_match('/^[a-zA-Z0-9_]+$/D', $table['TABLE_NAME']) === 1, 'Unexpected table or view.');
                if ($table['TABLE_NAME'] !== 'migrations') {
                    E::require((int) $pdo->query('SELECT COUNT(*) FROM `'.$table['TABLE_NAME'].'`')->fetchColumn() === 0,
                        'Integration test requires an empty schema, not existing or institutional data.');
                }
                if (! in_array($table['TABLE_NAME'], ['academic_programs', 'sle_fhe_registries', 'audit_logs', 'migrations'], true)) {
                    $protectedTables[] = $table['TABLE_NAME'];
                }
            }
            $program = CorrectionFixtures::program();
            $columns = array_keys($program);
            $pdo->prepare('INSERT INTO academic_programs ('.implode(',', $columns).') VALUES ('.implode(',', array_fill(0, count($columns), '?')).')')
                ->execute(array_values($program));
            foreach ([42, 43] as $id) {
                $row = CorrectionFixtures::row($id);
                $pdo->prepare('INSERT INTO sle_fhe_registries ('.implode(',', E::REGISTRY_FIELDS).') VALUES ('.implode(',', array_fill(0, count(E::REGISTRY_FIELDS), '?')).')')
                    ->execute(array_values($row));
            }
        });

        $fixture = new CorrectionFixtures;
        try {
            $engine = new CorrectionExecutor(new CorrectionApproval($fixture->policy), fn () => $store);
            $run = fn (array $package, bool $apply = true) => $engine->run(...[...$package, 'fixture-operator', $config['database'], $apply]);
            $catalog = fn () => $store->transaction(false, fn () => $store->catalog());
            $registry = fn () => $store->transaction(false, fn () => [$store->registry(42), $store->registry(43)]);
            $manifest = function (string $kind) use ($fixture, $catalog, $identity): array {
                $m = $fixture->manifest($kind, $catalog());
                $m['target'] = $identity;
                return $m;
            };
            $before = $registry();
            $m = $manifest('registry');
            $m['items'][0]['original_row_sha256'] = E::rowHash($before[0]);
            $m['items'][0]['original_updated_at'] = $before[0]['updated_at'];
            $package = $fixture->package($m);
            $this->assertSame(0, $run($package, false)['writes']);
            $this->assertSame($before, $registry());
            $this->assertSame('COMMITTED', $run($package)['status']);
            $after = $registry();
            $this->assertSame(CorrectionFixtures::program()['name'], $after[0]['course']);
            $this->assertSame($before[1], $after[1]);
            foreach (E::REGISTRY_FIELDS as $field) {
                if (! in_array($field, ['course', 'updated_at'], true)) {
                    $this->assertSame($before[0][$field], $after[0][$field]);
                }
            }
            $auditCount = (int) $pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
            $this->assertSame('ALREADY_APPLIED', $run($package)['status']);
            $this->assertSame($after, $registry());
            $this->assertSame($auditCount, (int) $pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn());
            $rollback = $manifest('rollback_registry');
            $rollback['original_manifest_sha256'] = hash('sha256', $package[0]);
            $this->assertSame('COMMITTED', $run($fixture->package($rollback))['status']);
            $this->assertSame($before, $registry());
            $this->assertGreaterThan($auditCount, (int) $pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn());

            // Real SQL failure handling: a successful UPDATE is undone by a later exception.
            $failed = false;
            try {
                $store->transaction(true, function () use ($store): void {
                    $store->catalog();
                    $store->changeCourse($store->registry(42), 'Temporary synthetic transaction value');
                    throw new RuntimeException('INJECTED_TEST_FAILURE');
                });
            } catch (RuntimeException $e) {
                $this->assertSame('INJECTED_TEST_FAILURE', $e->getMessage());
                $failed = true;
            }
            $this->assertTrue($failed);
            $this->assertSame($before, $registry());

            // A second connection must not insert through the locked primary-index range.
            $other = $connect();
            (new PdoCorrectionStore($other, $config, false))->assertTestGrants();
            $other->exec('SET SESSION innodb_lock_wait_timeout=1');
            $store->transaction(true, function () use ($store, $other): void {
                $store->catalog();
                $blocked = false;
                $other->beginTransaction();
                try {
                    $other->prepare('INSERT INTO academic_programs (code,name,short_name,is_board_program,is_undergraduate,is_active) VALUES (?,?,NULL,0,1,1)')
                        ->execute(['TEST-CONCURRENT', 'Synthetic concurrent degree']);
                } catch (PDOException $e) {
                    $this->assertSame(1205, (int) ($e->errorInfo[1] ?? 0));
                    $blocked = true;
                } finally {
                    $other->rollBack();
                }
                $this->assertTrue($blocked, 'Catalog range lock did not prevent concurrent insertion.');
            });
            $package = $fixture->package($manifest('catalog'));
            $this->assertSame('COMMITTED', $run($package)['status']);
            $postCatalog = $catalog();
            $this->assertCount(2, $postCatalog);
            $this->assertSame('ALREADY_APPLIED', $run($package)['status']);
            $this->assertSame($postCatalog, $catalog());
            foreach ($protectedTables as $table) {
                $this->assertSame(0, (int) $pdo->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn(), 'Unrelated table changed: '.$table);
            }
        } finally {
            $fixture->close(); // Only synthetic temporary files, never database cleanup.
        }
    }
}
