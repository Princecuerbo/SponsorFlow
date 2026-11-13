<?php

namespace Tests\Unit;

use App\Console\Commands\RegistryPreflight;
use App\Support\SleFhe\RegistryImportPreflight;
use App\Support\SleFhe\RegistryPreflightSnapshot;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Support\MiniXlsx;

/** No Laravel bootstrap, real PDO connection, model persistence, or seeder execution. */
class RegistryImportPreflightTest extends TestCase
{
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        parent::tearDown();
    }

    private function catalog(): array
    {
        return [['program_id' => 12, 'code' => 'BSIT', 'short_name' => 'BSIT',
            'name' => 'Bachelor of Science in Information Technology',
            'is_active' => true, 'is_undergraduate' => true, 'is_board_program' => false]];
    }

    private function row(array $overrides = [], string $sheet = 'DISTRICT 1', int $number = 3): array
    {
        return ['sheet' => $sheet, 'row' => $number, 'values' => array_replace([
            'student_id' => '2025-1001', 'sex' => 'Female', 'civil_status' => 'Single',
            'purok' => 'Purok 1', 'barangay' => 'Dahican', 'municipality' => 'Mati City',
            'course' => $this->catalog()[0]['name'], 'year_level' => '1',
            'campus' => 'DOrSU Main Campus', 'district' => RegistryImportPreflight::SHEETS[$sheet],
        ], $overrides)];
    }

    private function workbook(?array $rows = null): array
    {
        return ['source' => ['path' => 'synthetic-fixture.xlsx', 'sha256' => str_repeat('a', 64)],
            'rows' => $rows ?? [$this->row()]];
    }

    private function registry(array $overrides = []): array
    {
        return [array_replace($this->row()['values'], ['id' => 42, 'is_verified' => false,
            'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-02-01 00:00:00'], $overrides)];
    }

    private function inspect(?array $rows = null, array $registry = [], ?array $catalog = null): array
    {
        return (new RegistryImportPreflight)->inspect($this->workbook($rows), $catalog ?? $this->catalog(), $registry);
    }

    public function test_valid_catalog_names_codes_and_case_spacing_equivalence_never_authorize_import(): void
    {
        foreach ([$this->catalog()[0]['name'], 'BSIT', '  bsit  ', ' Bachelor  of Science in Information Technology '] as $course) {
            $report = $this->inspect([$this->row(['course' => $course])]);
            $this->assertFalse($report['has_issues']);
            $this->assertSame('VALID', $report['rows'][0]['status']);
            $this->assertSame(12, $report['rows'][0]['catalog_match']['program_id']);
            $this->assertSame($course, $report['rows'][0]['original']['course']);
            $this->assertFalse($report['writes_enabled']);
            $this->assertFalse($report['import_authorized']);
            $this->assertArrayNotHasKey('is_verified', $report['rows'][0]['original']);
        }
    }

    public function test_missing_and_invalid_ids_are_reported_without_discarding_source_rows(): void
    {
        foreach ([null, '', 'LAGBAWAN', '2025-1', '2025-1001A'] as $id) {
            $report = $this->inspect([$this->row(['student_id' => $id])]);
            $this->assertContains('INVALID_OR_MISSING_STUDENT_ID', $report['rows'][0]['issues']);
            $this->assertSame($id, $report['rows'][0]['original']['student_id']);
            $this->assertSame(1, $report['summary']['total']);
        }
    }

    public function test_identical_duplicates_are_all_reported_and_none_is_selected(): void
    {
        $report = $this->inspect([$this->row(), $this->row([], number: 4)]);
        $this->assertSame(2, $report['summary']['duplicate_rows']);
        $this->assertSame(0, $report['summary']['valid']);
        foreach ($report['rows'] as $row) {
            $this->assertContains('DUPLICATE_STUDENT_ID', $row['issues']);
        }
        $this->assertCount(2, $report['duplicate_groups'][0]['locations']);
    }

    public function test_duplicates_across_district_sheets_report_each_conflicting_field(): void
    {
        $report = $this->inspect([$this->row(), $this->row(['student_id' => ' 2025-1001 ',
            'course' => 'BSBA', 'barangay' => 'Other'], 'DISTRICT 2', 27)]);
        $this->assertSame(2, $report['summary']['conflicting_rows']);
        $this->assertSame(0, $report['summary']['valid']);
        foreach (['course', 'barangay', 'district'] as $field) {
            $this->assertContains($field, $report['duplicate_groups'][0]['conflicting_fields']);
        }
        $this->assertContains('student_id', $report['duplicate_groups'][0]['raw_differing_fields']);
        $this->assertContains('student_id', $report['duplicate_groups'][0]['normalization_equivalent_fields']);
        $this->assertNotContains('student_id', $report['duplicate_groups'][0]['conflicting_fields']);
        $this->assertSame('Other', $report['rows'][1]['original']['barangay']);
    }

    public function test_both_known_bs_it_typos_remain_pending_even_when_catalog_contains_bs_it(): void
    {
        foreach (RegistryImportPreflight::MALFORMED_BSIT as $course) {
            $report = $this->inspect([$this->row(['course' => $course])]);
            $this->assertContains('KNOWN_MALFORMED_BSIT_PENDING_REVIEW', $report['rows'][0]['issues']);
            $this->assertNull($report['rows'][0]['catalog_match']);
            $this->assertSame($course, $report['rows'][0]['original']['course']);
        }
    }

    public function test_unknown_missing_and_generic_programs_are_not_guessed(): void
    {
        foreach (['BS Unknown' => 'UNKNOWN_PROGRAM', '' => 'MISSING_PROGRAM',
            'BSBA' => 'AMBIGUOUS_PROGRAM_REQUIRES_SPECIALIZATION',
            'BSED' => 'AMBIGUOUS_PROGRAM_REQUIRES_SPECIALIZATION',
            'Bachelor of Arts in Information Technology' => 'UNKNOWN_PROGRAM'] as $course => $issue) {
            $row = $this->inspect([$this->row(['course' => $course])])['rows'][0];
            $this->assertContains($issue, $row['issues']);
            $this->assertNull($row['catalog_match']);
        }
    }

    public function test_duplicate_catalog_aliases_block_selection_instead_of_using_the_first(): void
    {
        $catalog = $this->catalog();
        $catalog[] = array_replace($catalog[0], ['program_id' => 99, 'name' => 'Conflicting program']);
        $report = $this->inspect([$this->row(['course' => 'BSIT'])], catalog: $catalog);
        $this->assertSame([12, 99], $report['catalog_collisions']['bsit']);
        $this->assertContains('AMBIGUOUS_CATALOG_MATCH', $report['rows'][0]['issues']);
        $this->assertNull($report['rows'][0]['catalog_match']);
    }

    public function test_inactive_program_requires_review(): void
    {
        $catalog = $this->catalog();
        $catalog[0]['is_active'] = false;
        $this->assertContains('INACTIVE_CATALOG_PROGRAM', $this->inspect(catalog: $catalog)['rows'][0]['issues']);
    }

    public function test_unknown_campus_never_falls_back_to_main_or_matches_a_substring(): void
    {
        foreach ([null, '', 'Random College', 'Fake CATEEL Campus'] as $campus) {
            $row = $this->inspect([$this->row(['campus' => $campus])])['rows'][0];
            $this->assertContains('INVALID_OR_UNKNOWN_CAMPUS', $row['issues']);
            $this->assertNull($row['recognized_campus']);
        }
        $row = $this->inspect([$this->row(['campus' => 'DOrSU-CATEEL EXTENTION CAMPUS'])])['rows'][0];
        $this->assertSame('DOrSU Cateel Extension Campus', $row['recognized_campus']);
        $this->assertSame('DOrSU-CATEEL EXTENTION CAMPUS', $row['original']['campus']);
    }

    public function test_every_incoming_field_difference_is_reported_without_overwriting_evidence(): void
    {
        $existing = $this->registry();
        $incoming = $this->row(['sex' => 'Male', 'purok' => 'Other', 'year_level' => '2']);
        $before = [$existing, $incoming];
        $report = $this->inspect([$incoming], $existing);
        $record = $report['rows'][0]['existing_records'][0];
        $this->assertSame(['sex', 'purok', 'year_level'], array_keys($record['differences']));
        $this->assertSame(['existing' => 'Purok 1', 'incoming' => 'Other'], $record['differences']['purok']);
        $this->assertSame($existing[0], $record['original']);
        $this->assertSame($before, [$existing, $incoming]);
        $this->assertSame('2026-02-01 00:00:00', $record['original']['updated_at']);
        $this->assertFalse($record['original']['is_verified']);
        $this->assertContains('EXISTING_RECORD_OVERWRITE_REQUIRES_REVIEW', $report['rows'][0]['issues']);
    }

    public function test_reimport_of_corrected_course_is_reported_as_regression_and_never_restores_typo(): void
    {
        $report = $this->inspect([$this->row(['course' => RegistryImportPreflight::MALFORMED_BSIT[0]])], $this->registry());
        $this->assertContains('CATALOG_VALUE_REGRESSION_OR_CONFLICT', $report['rows'][0]['issues']);
        $this->assertSame($this->catalog()[0]['name'], $report['rows'][0]['existing_records'][0]['original']['course']);
    }

    public function test_equivalent_catalog_code_does_not_authorize_overwriting_a_reviewed_full_name(): void
    {
        $catalog = $this->catalog();
        $catalog[0]['program_id'] = '12'; // PDO may return numeric strings.
        $report = $this->inspect([$this->row(['course' => 'bsit'])], $this->registry(), $catalog);
        $this->assertContains('CATALOG_EQUIVALENT_REPRESENTATION_CHANGE', $report['rows'][0]['observations']);
        $this->assertNotContains('EXISTING_RECORD_OVERWRITE_REQUIRES_REVIEW', $report['rows'][0]['issues']);
        $this->assertSame('NORMALIZATION_EQUIVALENT', $report['rows'][0]['existing_records'][0]['field_comparisons']['course']['classification']);
        $this->assertFalse($report['ready_for_import']);
        $this->assertFalse($report['import_authorized']);
    }

    public function test_both_phase_a_holds_apply_by_student_identity_even_without_registry_match(): void
    {
        foreach (RegistryImportPreflight::BLOCKED as $id => $studentId) {
            $report = $this->inspect([$this->row(['student_id' => $studentId])]);
            $this->assertSame('BLOCKED', $report['rows'][0]['status']);
            $this->assertSame($studentId, $report['blocked_identities'][$id]);
            $report = $this->inspect(registry: $this->registry(['id' => $id]));
            $this->assertSame('BLOCKED', $report['rows'][0]['status']);
        }
    }

    public function test_duplicate_registry_identity_is_not_resolved_by_first_occurrence(): void
    {
        $registry = array_merge($this->registry(), $this->registry(['id' => 43, 'purok' => 'Other']));
        $row = $this->inspect(registry: $registry)['rows'][0];
        $this->assertCount(2, $row['existing_records']);
        $this->assertContains('DUPLICATE_REGISTRY_IDENTITY', $row['issues']);
    }

    public function test_repeat_preflight_is_identical_and_preserves_false_and_true_verification_flags(): void
    {
        foreach ([true, false] as $flag) {
            $existing = $this->registry(['is_verified' => $flag]);
            $first = $this->inspect(registry: $existing);
            $this->assertSame($first, $this->inspect(registry: $existing));
            $this->assertSame($flag, $first['rows'][0]['existing_records'][0]['original']['is_verified']);
            $this->assertFalse($first['has_issues']);
        }
    }

    public function test_fingerprints_change_with_source_or_existing_evidence(): void
    {
        $first = $this->inspect(registry: $this->registry());
        $changed = $this->inspect([$this->row(['purok' => 'Changed'])], $this->registry(['updated_at' => '2026-03-01 00:00:00']));
        $this->assertNotSame($first['rows'][0]['source_row_sha256'], $changed['rows'][0]['source_row_sha256']);
        $this->assertNotSame($first['registry_sha256'], $changed['registry_sha256']);
        $this->assertSame(RegistryImportPreflight::fingerprint(['b' => 2, 'a' => 1]), RegistryImportPreflight::fingerprint(['a' => 1, 'b' => 2]));
    }

    public function test_empty_catalog_stops_without_a_partial_report(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('nonempty authoritative catalog');
        $this->inspect(catalog: []);
    }

    public function test_duplicate_catalog_primary_identity_stops(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('duplicate catalog identity');
        $this->inspect(catalog: array_merge($this->catalog(), $this->catalog()));
    }

    public function test_duplicate_source_coordinates_stop_instead_of_fabricating_row_attribution(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Duplicate source coordinates');
        $this->inspect([$this->row(), $this->row()]);
    }

    private function fixture(array $values = []): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'registry-b1-');
        $this->temporaryFiles[] = $path;
        $header = ['C' => 'SEX', 'D' => 'CIVIL STATUS', 'E' => 'PUROK', 'F' => 'BARANGAY',
            'G' => 'MUNICIPALITY', 'H' => 'COURSE', 'I' => 'YEAR LEVEL', 'J' => 'NAME OF SCHOOL'];
        MiniXlsx::write($path, [
            'DISTRICT 1' => [['A' => 'Synthetic source'], $header, array_replace([
                'B' => '2025-1001', 'C' => 'Female', 'D' => 'Single', 'E' => 'Purok 1',
                'F' => 'Dahican', 'G' => 'Mati City', 'H' => $this->catalog()[0]['name'],
                'I' => '1', 'J' => 'DOrSU Main Campus',
            ], $values)],
            'DISTRICT 2' => [['A' => 'Synthetic source'], $header],
        ]);

        return $path;
    }

    public function test_workbook_reader_keeps_exact_original_values_coordinates_and_source_hash(): void
    {
        $path = $this->fixture(['H' => ' BSIT  ']);
        $hash = hash_file('sha256', $path);
        $source = (new RegistryImportPreflight)->readWorkbook($path);
        $this->assertSame($hash, $source['source']['sha256']);
        $this->assertSame(realpath($path), $source['source']['path']);
        $this->assertSame(' BSIT  ', $source['rows'][0]['values']['course']);
        $this->assertSame(3, $source['rows'][0]['row']);
        $this->assertSame(['DISTRICT 1' => 1, 'DISTRICT 2' => 0], $source['source']['sheet_rows']);
        $this->assertSame($hash, hash_file('sha256', $path));
    }

    public function test_invalid_source_layout_stops(): void
    {
        $path = $this->fixture();
        MiniXlsx::write($path, ['DISTRICT 1' => [['A' => 'title'], ['H' => 'WRONG']], 'DISTRICT 2' => []]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid source layout');
        (new RegistryImportPreflight)->readWorkbook($path);
    }

    public function test_missing_district_sheet_stops(): void
    {
        $path = $this->fixture();
        MiniXlsx::write($path, []);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Required worksheet DISTRICT 1');
        (new RegistryImportPreflight)->readWorkbook($path);
    }

    /** Every accepted SQL statement is asserted; any additional write/query fails this fixture. */
    private function readonlyPdo(bool $failSelect = false): PDO
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('getAttribute')->with(PDO::ATTR_DRIVER_NAME)->willReturn('mysql');
        $pdo->method('inTransaction')->willReturn(false);
        $statements = ['SET TRANSACTION ISOLATION LEVEL REPEATABLE READ', 'SET TRANSACTION READ ONLY'];
        $pdo->expects($this->exactly(2))->method('exec')->willReturnCallback(function ($sql) use (&$statements) {
            $this->assertSame(array_shift($statements), $sql);
            return 0;
        });
        $pdo->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdo->expects($this->never())->method('commit');
        $pdo->expects($this->once())->method('rollBack')->willReturn(true);
        $queries = [
            'SELECT DATABASE() AS database_name' => [['database_name' => 'synthetic_no_connection']],
            'SELECT program_id, code, name, short_name, is_board_program, is_undergraduate, is_active, created_at, updated_at FROM academic_programs ORDER BY program_id' => $this->catalog(),
            'SELECT id, student_id, sex, civil_status, purok, barangay, municipality, course, year_level, campus, district, is_verified, created_at, updated_at FROM sle_fhe_registries ORDER BY id' => $this->registry(),
        ];
        $pdo->expects($this->exactly($failSelect ? 1 : 3))->method('query')->willReturnCallback(function ($sql) use ($queries, $failSelect) {
            $this->assertArrayHasKey($sql, $queries);
            if ($failSelect) {
                return false;
            }
            $statement = $this->createMock(PDOStatement::class);
            $statement->expects($this->once())->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($queries[$sql]);
            return $statement;
        });

        return $pdo;
    }

    public function test_snapshot_reads_only_catalog_and_registry_under_read_only_transaction(): void
    {
        $data = (new RegistryPreflightSnapshot)->read($this->readonlyPdo());
        $this->assertSame($this->registry(), $data['registry']);
        $this->assertSame($this->catalog(), $data['catalog']);
    }

    public function test_select_failure_rolls_back_and_never_returns_partial_snapshot(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SELECT failed');
        (new RegistryPreflightSnapshot)->read($this->readonlyPdo(true));
    }

    public function test_failed_read_only_protection_prevents_any_select_or_transaction(): void
    {
        foreach ([1, 2] as $failedStatement) {
            $pdo = $this->createMock(PDO::class);
            $pdo->method('getAttribute')->willReturn('mysql');
            $pdo->method('inTransaction')->willReturn(false);
            $calls = 0;
            $pdo->expects($this->exactly($failedStatement))->method('exec')->willReturnCallback(function () use (&$calls, $failedStatement) {
                return ++$calls === $failedStatement ? false : 0;
            });
            $pdo->expects($this->never())->method('beginTransaction');
            $pdo->expects($this->never())->method('query');
            try {
                (new RegistryPreflightSnapshot)->read($pdo);
                $this->fail('Either failed protection must stop inspection.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('read-only snapshot protections', $exception->getMessage());
            }
        }
    }

    public function test_unsupported_database_or_existing_transaction_fails_closed(): void
    {
        foreach ([['sqlite', false], ['mysql', true]] as [$driver, $active]) {
            $pdo = $this->createMock(PDO::class);
            $pdo->method('getAttribute')->willReturn($driver);
            $pdo->method('inTransaction')->willReturn($active);
            $pdo->expects($this->never())->method('exec');
            $pdo->expects($this->never())->method('query');
            try {
                (new RegistryPreflightSnapshot)->read($pdo);
                $this->fail('Unsafe snapshot must fail.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('fresh MySQL/MariaDB connection', $exception->getMessage());
            }
        }
    }

    private function command(?PDO $pdo): CommandTester
    {
        $container = new class extends Container
        {
            public function runningUnitTests(): bool
            {
                return true;
            }
        };
        $container->instance('config', new Repository);
        $manager = $this->createMock(DatabaseManager::class);
        if ($pdo === null) {
            $manager->expects($this->never())->method('connection');
        } else {
            $connection = $this->createMock(Connection::class);
            $connection->expects($this->once())->method('getPdo')->willReturn($pdo);
            $manager->expects($this->once())->method('connection')->willReturn($connection);
        }
        $container->instance('db', $manager);
        $command = new RegistryPreflight;
        $command->setLaravel($container);

        return new CommandTester($command);
    }

    public function test_cli_preflight_with_fixture_emits_complete_read_only_json(): void
    {
        $path = $this->fixture();
        $tester = $this->command($this->readonlyPdo());
        $this->assertSame(0, $tester->execute(['--source' => $path], ['interactive' => false]));
        $report = json_decode($tester->getDisplay(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('READ_ONLY', $report['mode']);
        $this->assertFalse($report['writes_enabled']);
        $this->assertSame(1, $report['summary']['valid']);
        $this->assertSame(hash_file('sha256', $path), $report['source']['sha256']);
        $this->assertSame('synthetic_no_connection', $report['database']);
    }

    public function test_cli_issues_have_failure_exit_status_without_any_persistence(): void
    {
        $tester = $this->command($this->readonlyPdo());
        $this->assertSame(1, $tester->execute(['--source' => $this->fixture(['H' => RegistryImportPreflight::MALFORMED_BSIT[0]])], ['interactive' => false]));
        $report = json_decode($tester->getDisplay(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertContains('KNOWN_MALFORMED_BSIT_PENDING_REVIEW', $report['rows'][0]['issues']);
        $this->assertFalse($report['import_authorized']);
    }

    public function test_missing_source_does_not_connect_to_database_or_fall_back(): void
    {
        $tester = $this->command(null);
        $this->assertSame(2, $tester->execute(['--source' => $this->fixture().'.missing'], ['interactive' => false]));
        $report = json_decode($tester->getDisplay(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('STOPPED', $report['status']);
        $this->assertSame('source', $report['stage']);
    }

    public function test_cli_has_no_force_apply_or_import_options(): void
    {
        $definition = (new RegistryPreflight)->getDefinition();
        $this->assertSame(['source'], array_keys($definition->getOptions()));
        $this->assertSame([], $definition->getArguments());
    }

    public function test_snapshot_failure_reports_source_fingerprint_and_stops_without_partial_success(): void
    {
        $path = $this->fixture();
        $tester = $this->command($this->readonlyPdo(true));
        $this->assertSame(2, $tester->execute(['--source' => $path], ['interactive' => false]));
        $report = json_decode($tester->getDisplay(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('STOPPED', $report['status']);
        $this->assertSame('snapshot', $report['stage']);
        $this->assertSame(hash_file('sha256', $path), $report['source']['sha256']);
        $this->assertArrayNotHasKey('rows', $report);
    }

    public function test_explicit_empty_source_stops_without_falling_back_or_connecting(): void
    {
        $tester = $this->command(null);
        $this->assertSame(2, $tester->execute(['--source' => ' '], ['interactive' => false]));
        $this->assertStringContainsString('no fallback performed', $tester->getDisplay());
    }

    public function test_documented_import_normalization_is_equivalent_without_changing_raw_evidence(): void
    {
        $source = $this->row(['student_id' => ' 2025-1001 ', 'sex' => 'FELALE', 'civil_status' => ' SINGLE ',
            'municipality' => 'MATI CTY', 'purok' => '  Purok   1 ', 'barangay' => ' Dahican ',
            'year_level' => ' 1 ', 'campus' => 'DAVAO ORIENTAL STATE UNIVERSITY-MAIN CAMPUS']);
        $registry = $this->registry(['district' => '1']);
        $before = [$source, $registry];
        $report = $this->inspect([$source], $registry);
        $comparison = $report['rows'][0]['existing_records'][0];
        foreach (['student_id', 'sex', 'civil_status', 'municipality', 'purok', 'barangay', 'year_level', 'campus', 'district'] as $field) {
            $this->assertSame('NORMALIZATION_EQUIVALENT', $comparison['field_comparisons'][$field]['classification'], $field);
            $this->assertNotEmpty($comparison['field_comparisons'][$field]['rule']);
        }
        $this->assertSame('EXACT_MATCH', $comparison['field_comparisons']['course']['classification']);
        $this->assertSame($source['values'], $report['rows'][0]['original']);
        $this->assertSame($registry[0], $comparison['original']);
        $this->assertSame($before, [$source, $registry]);
        $this->assertSame('NORMALIZATION_EQUIVALENT', $report['rows'][0]['attribute_comparison']);
        $this->assertFalse($report['has_issues']);
        $this->assertFalse($report['ready_for_import']);
        $this->assertFalse($report['rows'][0]['ready_for_import']);
        $this->assertSame('NOT_EVALUATED', $report['approval_evidence_status']);
    }

    public function test_attribute_normalization_does_not_hide_genuine_address_or_year_changes(): void
    {
        $report = $this->inspect([$this->row(['barangay' => 'Elsewhere', 'purok' => 'Purok 2', 'year_level' => '2'])], $this->registry());
        $fields = $report['rows'][0]['existing_records'][0]['field_comparisons'];
        foreach (['barangay', 'purok', 'year_level'] as $field) {
            $this->assertSame('DIFFERENT_VALUE', $fields[$field]['classification']);
        }
        $this->assertSame('DIFFERENT_VALUE', $report['rows'][0]['attribute_comparison']);
        $this->assertContains('EXISTING_RECORD_OVERWRITE_REQUIRES_REVIEW', $report['rows'][0]['issues']);
    }

    public function test_no_new_case_address_abbreviation_or_year_alias_rules_are_invented(): void
    {
        $report = $this->inspect([$this->row(['barangay' => 'DAHICAN', 'purok' => 'Prk. 1', 'year_level' => 'First Year'])], $this->registry());
        foreach (['barangay', 'purok', 'year_level'] as $field) {
            $comparison = $report['rows'][0]['existing_records'][0]['field_comparisons'][$field];
            $this->assertSame('DIFFERENT_VALUE', $comparison['classification']);
        }
    }

    public function test_unknown_closed_vocabulary_values_remain_unknown_even_when_identical(): void
    {
        foreach (['sex' => 'UNRECOGNIZED', 'civil_status' => 'UNRECOGNIZED', 'campus' => 'Fake CATEEL Campus'] as $field => $value) {
            $report = $this->inspect([$this->row([$field => $value])], $this->registry([$field => $value]));
            $comparison = $report['rows'][0]['existing_records'][0]['field_comparisons'][$field];
            $this->assertTrue($comparison['raw_exact']);
            $this->assertSame('UNKNOWN_VALUE', $comparison['classification']);
            $this->assertContains('UNRECOGNIZED_EXISTING_OR_SOURCE_ATTRIBUTE', $report['rows'][0]['issues']);
            $this->assertTrue($report['has_issues']);
        }
    }

    public function test_nullable_free_text_is_not_fabricated_and_missing_values_cannot_erase_existing_evidence(): void
    {
        $report = $this->inspect([$this->row(['purok' => '   '])], $this->registry(['purok' => null]));
        $comparison = $report['rows'][0]['existing_records'][0]['field_comparisons']['purok'];
        $this->assertSame('NORMALIZATION_EQUIVALENT', $comparison['classification']);
        $this->assertNull($comparison['compared_existing']);
        $this->assertNull($comparison['compared_incoming']);
        $report = $this->inspect([$this->row(['purok' => null])], $this->registry());
        $this->assertSame('DIFFERENT_VALUE', $report['rows'][0]['existing_records'][0]['field_comparisons']['purok']['classification']);
        $this->assertContains('EXISTING_RECORD_OVERWRITE_REQUIRES_REVIEW', $report['rows'][0]['issues']);
    }

    public function test_formatting_equivalent_attributes_do_not_clear_any_program_issue(): void
    {
        foreach ([...RegistryImportPreflight::MALFORMED_BSIT, 'BSBA', 'Unlisted Course', 'Bachelor of Arts in Information Technology'] as $course) {
            $report = $this->inspect([$this->row(['sex' => 'F', 'course' => $course])], $this->registry());
            $this->assertSame('NORMALIZATION_EQUIVALENT', $report['rows'][0]['attribute_comparison']);
            $this->assertSame('UNKNOWN_VALUE', $report['rows'][0]['existing_records'][0]['field_comparisons']['course']['classification']);
            $this->assertSame('REQUIRES_REVIEW', $report['rows'][0]['status']);
            $this->assertFalse($report['rows'][0]['ready_for_import']);
            $this->assertTrue($report['has_issues']);
        }
    }

    public function test_existing_unknown_course_cannot_be_silently_replaced_by_a_catalog_value(): void
    {
        $report = $this->inspect(registry: $this->registry(['course' => 'Unresolved prior course']));
        $this->assertContains('UNRESOLVED_EXISTING_OR_SOURCE_PROGRAM', $report['rows'][0]['issues']);
        $this->assertContains('EXISTING_RECORD_OVERWRITE_REQUIRES_REVIEW', $report['rows'][0]['issues']);
        $this->assertSame('Unresolved prior course', $report['rows'][0]['existing_records'][0]['original']['course']);
    }

    public function test_distinct_catalog_identities_remain_program_conflicts(): void
    {
        $catalog = $this->catalog();
        $catalog[] = array_replace($catalog[0], ['program_id' => 11, 'name' => 'Bachelor of Science in Computer Science', 'code' => 'BSCS', 'short_name' => 'BSCS']);
        $report = $this->inspect([$this->row(['course' => 'BSCS'])], $this->registry(), $catalog);
        $this->assertSame('DIFFERENT_VALUE', $report['rows'][0]['existing_records'][0]['field_comparisons']['course']['classification']);
        $this->assertSame('EXACT_MATCH', $report['rows'][0]['attribute_comparison']);
        $this->assertContains('CATALOG_VALUE_REGRESSION_OR_CONFLICT', $report['rows'][0]['issues']);
    }

    public function test_formatting_only_duplicates_still_block_without_false_attribute_conflicts(): void
    {
        $report = $this->inspect([$this->row(), $this->row(['sex' => 'F', 'course' => 'BSIT'], number: 4)], $this->registry());
        $group = $report['duplicate_groups'][0];
        $this->assertSame([], $group['conflicting_fields']);
        $this->assertSame(['sex', 'course'], $group['normalization_equivalent_fields']);
        foreach ($report['rows'] as $row) {
            $this->assertContains('DUPLICATE_STUDENT_ID', $row['issues']);
            $this->assertSame('REQUIRES_REVIEW', $row['status']);
        }
        $metrics = $report['reconciliation_summary']['metrics'];
        $this->assertSame(['source_rows' => 2, 'distinct_registry_identities' => 1], $metrics['duplicate_identity']);
        $this->assertSame(['source_rows' => 0, 'distinct_registry_identities' => 0], $metrics['duplicate_source_conflict']);
    }

    public function test_identity_rollup_uses_worst_occurrence_and_never_double_counts_registry_identity(): void
    {
        $report = $this->inspect([$this->row(), $this->row(['purok' => 'Other'], number: 4)], $this->registry());
        $summary = $report['reconciliation_summary']['attributes_excluding_academic_program'];
        $this->assertSame(1, $summary['source_rows']['EXACT_MATCH']);
        $this->assertSame(1, $summary['source_rows']['DIFFERENT_VALUE']);
        $this->assertSame(0, $summary['distinct_registry_identities']['EXACT_MATCH']);
        $this->assertSame(1, $summary['distinct_registry_identities']['DIFFERENT_VALUE']);
        foreach ($report['rows'] as $row) {
            $this->assertSame('DUPLICATE_SOURCE_CONFLICT', $row['duplicate_source_fields']['purok']);
        }
        $this->assertSame(['source_rows' => 2, 'distinct_registry_identities' => 1], $report['reconciliation_summary']['metrics']['requires_institutional_review']);
    }

    public function test_unresolved_duplicate_courses_are_not_reconciled_by_whitespace_or_spelling_guesses(): void
    {
        $report = $this->inspect([$this->row(['course' => 'Unlisted']), $this->row(['course' => ' Unlisted '], number: 4)], $this->registry());
        $group = $report['duplicate_groups'][0];
        $this->assertSame(['course'], $group['unresolved_fields']);
        $this->assertSame([], $group['genuine_conflicting_fields']);
        $this->assertSame(['course'], $group['conflicting_fields']);
        $this->assertSame(2, $report['summary']['conflicting_rows']);
    }

    public function test_duplicate_comparisons_include_every_pair_and_the_last_conflicting_occurrence(): void
    {
        $report = $this->inspect([$this->row(), $this->row(['sex' => 'F'], number: 4),
            $this->row(['sex' => 'Male'], number: 5)], $this->registry());
        $this->assertCount(3, $report['duplicate_groups'][0]['pair_comparisons']);
        $this->assertSame(['sex'], $report['duplicate_groups'][0]['genuine_conflicting_fields']);
        $this->assertSame(['source_rows' => 3, 'distinct_registry_identities' => 1], $report['reconciliation_summary']['metrics']['duplicate_source_conflict']);
    }

    public function test_malformed_counts_distinguish_source_rows_registry_ids_and_unmatched_evidence(): void
    {
        $course = RegistryImportPreflight::MALFORMED_BSIT[0];
        $rows = [$this->row(['course' => $course]), $this->row(['course' => $course], number: 4),
            $this->row(['student_id' => 'INVALID', 'course' => $course], number: 5)];
        $report = $this->inspect($rows, $this->registry(['course' => $course]));
        $this->assertSame(['source_rows' => 3, 'distinct_registry_identities' => 1], $report['reconciliation_summary']['metrics']['known_malformed_programs']);
        $group = $report['reconciliation_summary']['program_review_groups'][0];
        $this->assertSame($course, $group['source_value']);
        $this->assertSame(3, $group['source_rows']);
        $this->assertSame(1, $group['distinct_registry_identities']);
    }

    public function test_phase_a_blocks_survive_complete_attribute_equivalence(): void
    {
        foreach (RegistryImportPreflight::BLOCKED as $id => $studentId) {
            $report = $this->inspect([$this->row(['student_id' => $studentId, 'sex' => 'F'])],
                $this->registry(['id' => $id, 'student_id' => $studentId]));
            $this->assertSame('NORMALIZATION_EQUIVALENT', $report['rows'][0]['attribute_comparison']);
            $this->assertSame('BLOCKED', $report['rows'][0]['status']);
            $this->assertFalse($report['rows'][0]['ready_for_import']);
            $this->assertSame(['source_rows' => 1, 'distinct_registry_identities' => 1], $report['reconciliation_summary']['metrics']['blocked_phase_a']);
        }
    }

    public function test_equivalent_values_do_not_attest_freshness_or_approval_of_changed_evidence(): void
    {
        $first = $this->inspect(registry: $this->registry());
        $changed = $this->inspect([$this->row(['sex' => 'F'])], $this->registry(['updated_at' => '2026-10-20 00:00:00']));
        $this->assertNotSame($first['registry_sha256'], $changed['registry_sha256']);
        $this->assertFalse($changed['ready_for_import']);
        $this->assertFalse($changed['import_authorized']);
        $this->assertSame('NOT_EVALUATED', $changed['approval_evidence_status']);
        $this->assertStringContainsString('freshness against prior approvals is not attested', $changed['review_policy']);
    }
}
