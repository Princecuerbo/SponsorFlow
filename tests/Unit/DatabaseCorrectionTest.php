<?php

namespace Tests\Unit;

use App\Console\Commands\DatabaseCorrections;
use App\Support\Corrections\CorrectionApproval;
use App\Support\Corrections\CorrectionEvidence as E;
use App\Support\Corrections\CorrectionExecutor;
use App\Support\SleFhe\RegistryImportPreflight;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Support\CorrectionFixtures;
use Tests\Support\MemoryCorrectionStore;
use Tests\Support\MiniXlsx;

class DatabaseCorrectionTest extends TestCase
{
    private static CorrectionFixtures $fixtures;
    private MemoryCorrectionStore $store;

    public static function setUpBeforeClass(): void
    {
        self::$fixtures = new CorrectionFixtures;
    }

    public static function tearDownAfterClass(): void
    {
        self::$fixtures->close();
    }

    protected function setUp(): void
    {
        $this->store = new MemoryCorrectionStore;
    }

    private function runPackage(array $package, bool $apply = true, ?array $policy = null): array
    {
        $executor = new CorrectionExecutor(new CorrectionApproval($policy ?? self::$fixtures->policy), fn () => $this->store);

        return $executor->run(...[...$package, 'fixture-operator', E::document($package[0])['target']['database'], $apply]);
    }

    private function rejected(callable $work, string $message = ''): void
    {
        $before = serialize([$this->store->programs, $this->store->rows, $this->store->receipts, $this->store->writes]);
        $exception = null;
        try {
            $work();
        } catch (RuntimeException $e) {
            $exception = $e;
        }
        $this->assertNotNull($exception, 'Expected correction rejection.');
        if ($message !== '') {
            $this->assertStringContainsString($message, $exception->getMessage());
        }
        $this->assertSame($before, serialize([$this->store->programs, $this->store->rows, $this->store->receipts, $this->store->writes]));
    }

    public function test_pending_and_partial_approval_manifest_never_reaches_store(): void
    {
        foreach (['manifest', 'item', 'partial'] as $case) {
            $m = self::$fixtures->manifest();
            if ($case === 'manifest') {
                $m['status'] = 'PENDING';
            } else {
                if ($case === 'partial') {
                    $m['items'][] = array_replace($m['items'][0], ['reference' => 'TEST-2']);
                }
                $m['items'][array_key_last($m['items'])]['approval_status'] = 'PENDING';
            }
            $calls = 0;
            $engine = new CorrectionExecutor(new CorrectionApproval(self::$fixtures->policy), function () use (&$calls) {
                $calls++;
                return $this->store;
            });
            $package = self::$fixtures->package($m);
            $this->rejected(fn () => $engine->run(...[...$package, 'fixture-operator', 'sponsorflow_test', true]));
            $this->assertSame(0, $calls);
        }
    }

    public static function invalidAuthorizations(): array
    {
        return [
            'wrong operator' => [['operator' => 'another-operator']],
            'no exclusive window' => [['exclusive_maintenance' => false]],
            'wrong approval hash' => [['approval_sha256' => str_repeat('f', 64)]],
            'wrong operation' => [['operation' => 'rollback']],
            'no backup proof' => [['backup' => []]],
            'no isolated test proof' => [['isolated_test' => []]],
            'expired authorization' => [['expires_at' => '2000-01-01T00:00:00Z']],
        ];
    }

    #[DataProvider('invalidAuthorizations')]
    public function test_incomplete_execution_authorization_cannot_write(array $overrides): void
    {
        $this->rejected(fn () => $this->runPackage(self::$fixtures->package(self::$fixtures->manifest(), $overrides)));
    }

    public function test_csv_approved_word_is_not_authorization_and_no_trust_is_configured_by_default(): void
    {
        $policy = require dirname(__DIR__, 2).'/config/corrections.php';
        $this->assertSame([], $policy['approval_keys']);
        $this->assertSame([], $policy['execution_keys']);
        $this->assertFalse($policy['production_apply_enabled']);
        $this->rejected(fn () => $this->runPackage(self::$fixtures->package(self::$fixtures->manifest()), policy: $policy), 'not an enrolled');
        $path = tempnam(sys_get_temp_dir(), 'sf-review-');
        $csv = $path.'.csv';
        try {
            rename($path, $csv);
            file_put_contents($csv, "registry_id,approval_status\n42,APPROVED\n");
            $report = E::review($csv);
            $this->assertSame(0, $report['executable_changes']);
            $this->assertSame(['APPROVED' => 1], $report['statuses']);
            $container = new class extends Container
            {
                public function runningUnitTests(): bool { return true; }
            };
            $config = $this->createMock(Repository::class);
            $config->expects($this->never())->method('get');
            $container->instance('config', $config);
            $command = new DatabaseCorrections;
            $command->setLaravel($container);
            $this->assertFalse($command->getDefinition()->getOption('apply')->getDefault());
            $tester = new CommandTester($command);
            foreach ([false, true] as $apply) {
                $this->assertSame(2, $tester->execute(['manifest' => $csv, '--apply' => $apply], ['interactive' => false]));
                $output = json_decode($tester->getDisplay(), true, flags: JSON_THROW_ON_ERROR);
                $this->assertSame('REVIEW_ONLY', $output['mode']);
                $this->assertSame(0, $output['executable_changes']);
            }
        } finally {
            if (is_file($csv)) {
                unlink($csv);
            }
        }
    }

    public function test_tampered_signature_and_changed_manifest_are_rejected(): void
    {
        $package = self::$fixtures->package(self::$fixtures->manifest());
        $bad = E::document($package[1]);
        $bad['payload']['authority'] = 'Fabricated approval';
        $package[1] = E::json($bad);
        $this->rejected(fn () => $this->runPackage($package), 'signature');
        $package = self::$fixtures->package(self::$fixtures->manifest());
        $m = E::document($package[0]);
        $m['items'][0]['code'] = 'TAMPERED';
        $package[0] = E::json($m);
        $this->rejected(fn () => $this->runPackage($package), 'checksum');
    }

    public function test_missing_authorization_is_a_dry_run_blocker_and_apply_fails(): void
    {
        $p = self::$fixtures->package(self::$fixtures->manifest());
        $p[2] = null;
        $report = $this->runPackage($p, false);
        $this->assertCount(1, $report['changes']);
        $this->assertSame(0, $report['writes']);
        $this->assertNotEmpty($report['execution_blockers']);
        $this->rejected(fn () => $this->runPackage($p), 'authorization is missing');
    }

    public function test_production_apply_is_disabled_even_with_synthetic_signed_approvals(): void
    {
        $m = self::$fixtures->manifest();
        $m['target']['database'] = 'sponsorflow';
        $this->rejected(fn () => $this->runPackage(self::$fixtures->package($m)), 'Production APPLY is disabled');
    }

    public function test_catalog_insert_uses_assigned_id_and_repeat_has_no_timestamp_or_audit_churn(): void
    {
        $package = self::$fixtures->package(self::$fixtures->manifest());
        $original = $this->store->programs[0];
        $this->assertSame('COMMITTED', $this->runPackage($package)['status']);
        $this->assertSame($original, $this->store->programs[0]);
        $this->assertSame(701, $this->store->programs[1]['program_id']);
        $this->assertSame('fixture-operator', $this->store->receipts[hash('sha256', $package[0])]['metadata']['operator']);
        $after = serialize($this->store);
        $this->assertSame('ALREADY_APPLIED', $this->runPackage($package)['status']);
        $this->assertSame($after, serialize($this->store));
    }

    public function test_exact_existing_catalog_definition_is_a_noop(): void
    {
        $m = self::$fixtures->manifest();
        foreach (['code', 'name', 'short_name'] as $field) {
            $m['items'][0][$field] = $this->store->programs[0][$field];
        }
        $this->assertSame('NO_CHANGES', $this->runPackage(self::$fixtures->package($m))['status']);
        $this->assertSame(0, $this->store->writes);
    }

    public function test_inactive_catalog_collision_and_selected_collation_collision_abort(): void
    {
        $this->store->programs[0]['is_active'] = 0;
        $m = self::$fixtures->manifest(catalog: $this->store->programs);
        $m['items'][0]['code'] = $this->store->programs[0]['code'];
        $this->rejected(fn () => $this->runPackage(self::$fixtures->package($m)), 'collision');
        $m = self::$fixtures->manifest(catalog: $this->store->programs);
        $m['items'][] = array_replace($m['items'][0], ['reference' => 'TEST-2', 'name' => 'Another synthetic degree', 'code' => 'TEST-NÉW']);
        $this->rejected(fn () => $this->runPackage(self::$fixtures->package($m)), 'collide');
    }

    public function test_registry_exact_row_correction_preserves_other_fields_records_and_repeat(): void
    {
        $before = $this->store->rows;
        $protected = $this->store->protectedRecords;
        $package = self::$fixtures->package(self::$fixtures->manifest('registry'));
        $this->assertSame('COMMITTED', $this->runPackage($package)['status']);
        $this->assertSame(CorrectionFixtures::program()['name'], $this->store->rows[42]['course']);
        foreach (E::REGISTRY_FIELDS as $field) {
            if (! in_array($field, ['course', 'updated_at'], true)) {
                $this->assertSame($before[42][$field], $this->store->rows[42][$field]);
            }
        }
        $this->assertSame($before[43], $this->store->rows[43]);
        $this->assertSame($protected, $this->store->protectedRecords);
        $after = serialize($this->store);
        $this->assertSame('ALREADY_APPLIED', $this->runPackage($package)['status']);
        $this->assertSame($after, serialize($this->store));
    }

    public function test_stale_row_catalog_or_missing_target_program_aborts(): void
    {
        $m = self::$fixtures->manifest('registry');
        $this->store->rows[42]['barangay'] = 'Changed after review';
        $this->rejected(fn () => $this->runPackage(self::$fixtures->package($m)), 'stale');
        $this->store = new MemoryCorrectionStore;
        $this->store->programs[0]['is_active'] = 0;
        $this->rejected(fn () => $this->runPackage(self::$fixtures->package($m)), 'catalog snapshot is stale');
        $this->store = new MemoryCorrectionStore;
        $m['items'][0]['catalog_program_id'] = 999;
        $this->rejected(fn () => $this->runPackage(self::$fixtures->package($m)), 'canonical');
    }

    public function test_blocked_registry_ids_and_corresponding_student_ids_never_execute(): void
    {
        foreach (RegistryImportPreflight::BLOCKED as $id => $studentId) {
            foreach (['id', 'student'] as $case) {
                $m = self::$fixtures->manifest('registry');
                $m['items'][0][$case === 'id' ? 'registry_id' : 'student_id'] = $case === 'id' ? $id : $studentId;
                $this->rejected(fn () => $this->runPackage(self::$fixtures->package($m)), 'Blocked');
            }
        }
    }

    public function test_failure_during_second_write_rolls_back_data_and_audit(): void
    {
        $m = self::$fixtures->manifest('registry');
        $m['items'][] = array_replace($m['items'][0], ['reference' => 'TEST-2', 'registry_id' => 43,
            'student_id' => '2099-9002', 'source_row' => 4, 'original_row_sha256' => E::rowHash(CorrectionFixtures::row(43))]);
        $this->store->failAfter = 1;
        $this->rejected(fn () => $this->runPackage(self::$fixtures->package($m)), 'Synthetic transaction failure');
        $this->assertSame([], $this->store->receipts);
    }

    public function test_audit_failure_rolls_back_catalog_insertion(): void
    {
        $this->store->failAfter = 1;
        $this->rejected(fn () => $this->runPackage(self::$fixtures->package(self::$fixtures->manifest())), 'Synthetic transaction failure');
        $this->assertCount(1, $this->store->programs);
    }

    public function test_compensating_registry_rollback_requires_separate_authorization_and_preserves_audit(): void
    {
        $before = $this->store->rows[42];
        $p = self::$fixtures->package(self::$fixtures->manifest('registry'));
        $this->runPackage($p);
        $m = self::$fixtures->manifest('rollback_registry');
        $m['original_manifest_sha256'] = hash('sha256', $p[0]);
        $rollback = self::$fixtures->package($m);
        $bad = $rollback;
        $bad[2] = $p[2]; // Old APPLY authorization cannot authorize rollback.
        $this->rejected(fn () => $this->runPackage($bad), 'checksum');
        $this->assertSame('COMMITTED', $this->runPackage($rollback)['status']);
        $this->assertSame($before, $this->store->rows[42]);
        $this->assertCount(2, $this->store->receipts);
        $this->assertSame('ALREADY_APPLIED', $this->runPackage($rollback)['status']);
        $this->rejected(fn () => $this->runPackage($p), 'post-image');
    }

    public function test_rollback_refuses_intervening_changes(): void
    {
        $p = self::$fixtures->package(self::$fixtures->manifest('registry'));
        $this->runPackage($p);
        $m = self::$fixtures->manifest('rollback_registry');
        $m['original_manifest_sha256'] = hash('sha256', $p[0]);
        $this->store->rows[42]['is_verified'] = 1;
        $this->rejected(fn () => $this->runPackage(self::$fixtures->package($m)), 'Intervening changes');
    }

    public function test_incorrect_effective_database_identity_aborts(): void
    {
        $this->store->target['database'] = 'sponsorflow';
        $this->rejected(fn () => $this->runPackage(self::$fixtures->package(self::$fixtures->manifest())), 'identity mismatch');
    }

    public function test_fingerprint_matches_phase_a_order_and_null_rules(): void
    {
        $row = CorrectionFixtures::row();
        $ordered = [];
        foreach (E::REGISTRY_FIELDS as $field) {
            $ordered[$field] = $row[$field] === null ? null : (string) $row[$field];
        }
        $this->assertSame(hash('sha256', json_encode($ordered, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)), E::rowHash($row));
    }

    public function test_missing_approval_and_incomplete_catalog_attributes_never_open_a_store(): void
    {
        $calls = 0;
        $engine = new CorrectionExecutor(new CorrectionApproval(self::$fixtures->policy), function () use (&$calls) {
            $calls++;
            return $this->store;
        });
        $p = self::$fixtures->package(self::$fixtures->manifest());
        $this->rejected(fn () => $engine->run($p[0], '{}', $p[2], 'fixture-operator', 'sponsorflow_test', true));
        foreach (['code' => 'PENDING', 'is_board_program' => null, 'short_name_null_approved' => false, 'program_id' => 27] as $key => $value) {
            $m = self::$fixtures->manifest();
            $m['items'][0][$key] = $value;
            $package = self::$fixtures->package($m);
            $this->rejected(fn () => $engine->run(...[...$package, 'fixture-operator', 'sponsorflow_test', true]));
        }
        $this->assertSame(0, $calls);
    }

    public function test_fully_authorized_dry_run_remains_read_only(): void
    {
        $before = serialize($this->store);
        $report = $this->runPackage(self::$fixtures->package(self::$fixtures->manifest('registry')), false);
        $this->assertSame('DRY_RUN', $report['mode']);
        $this->assertSame(0, $report['writes']);
        $this->assertSame([], $report['execution_blockers']);
        $this->assertCount(1, $report['changes']);
        $this->assertSame($before, serialize($this->store));
    }

    public function test_source_checksum_coordinates_and_duplicate_source_identity_are_not_guessed(): void
    {
        $m = self::$fixtures->manifest('registry');
        $m['source']['sha256'] = str_repeat('a', 64);
        $this->rejected(fn () => $this->runPackage(self::$fixtures->package($m)), 'checksum');
        $m = self::$fixtures->manifest('registry');
        $m['items'][0]['source_row'] = 4;
        $this->rejected(fn () => $this->runPackage(self::$fixtures->package($m)), 'coordinates');
        $fixture = new CorrectionFixtures;
        try {
            $headers = ['B' => 'STUDENT ID', 'C' => 'SEX', 'D' => 'CIVIL STATUS', 'E' => 'PUROK',
                'F' => 'BARANGAY', 'G' => 'MUNICIPALITY', 'H' => 'COURSE', 'I' => 'YEAR LEVEL', 'J' => 'NAME OF SCHOOL'];
            MiniXlsx::write($fixture->workbook, [
                'DISTRICT 1' => [[], $headers, ['B' => '2099-9001', 'H' => 'Malformed synthetic course']],
                'DISTRICT 2' => [[], $headers, ['B' => '2099-9001', 'H' => 'Conflicting synthetic course']],
            ]);
            $this->rejected(fn () => $this->runPackage($fixture->package($fixture->manifest('registry')), policy: $fixture->policy), 'duplicate source');
        } finally {
            $fixture->close();
        }
    }

    public function test_independent_signers_and_actual_written_evidence_are_required(): void
    {
        $p = self::$fixtures->package(self::$fixtures->manifest());
        $payload = E::document($p[2])['payload'];
        $payload['authority'] = 'Synthetic approval';
        $p[2] = self::$fixtures->sign($payload, 'approval');
        $policy = self::$fixtures->policy;
        $policy['execution_keys']['approval'] = $policy['approval_keys']['approval'];
        $this->rejected(fn () => $this->runPackage($p, policy: $policy), 'independent signers');
        $p = self::$fixtures->package(self::$fixtures->manifest());
        $payload = E::document($p[1])['payload'];
        $payload['evidence'][0]['sha256'] = str_repeat('a', 64);
        $p[1] = self::$fixtures->sign($payload, 'approval');
        $this->rejected(fn () => $this->runPackage($p), 'checksum');
        $payload['evidence'][0]['path'] = self::$fixtures->evidence.'.missing';
        $p[1] = self::$fixtures->sign($payload, 'approval');
        $this->rejected(fn () => $this->runPackage($p), 'missing or unreadable');
    }

    public function test_approval_scope_cannot_be_expanded_and_only_selected_row_changes(): void
    {
        $m = self::$fixtures->manifest('registry');
        $p = self::$fixtures->package($m);
        $payload = E::document($p[1])['payload'];
        $payload['items'][] = 'UNAPPROVED-EXTRA';
        $p[1] = self::$fixtures->sign($payload, 'approval');
        $this->rejected(fn () => $this->runPackage($p), 'exactly');
        $untouched = $this->store->rows[43];
        $this->assertSame(1, $this->runPackage(self::$fixtures->package($m))['writes']);
        $this->assertSame($untouched, $this->store->rows[43]);
    }

    public function test_revalidation_rejects_evidence_changed_after_initial_gate(): void
    {
        $fixture = new CorrectionFixtures;
        try {
            $p = $fixture->package($fixture->manifest());
            $engine = new CorrectionExecutor(new CorrectionApproval($fixture->policy), function () use ($fixture) {
                file_put_contents($fixture->evidence, 'CHANGED synthetic evidence');
                return $this->store;
            });
            $this->rejected(fn () => $engine->run(...[...$p, 'fixture-operator', 'sponsorflow_test', true]), 'checksum');
        } finally {
            $fixture->close();
        }
    }
}
