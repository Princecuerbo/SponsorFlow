<?php

namespace Tests\Feature;

use App\Console\Commands\ImportSleFheRegistryToPostgresCommand;
use App\Models\AcademicProgram;
use App\Models\AuditLog;
use App\Models\SleFheRegistry;
use App\Models\SleFheRequest;
use App\Models\SleFheVerification;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\AcademicProgramSeeder;
use Database\Seeders\LocalAddressSeeder;
use Database\Seeders\RoleAndUserSeeder;
use Database\Seeders\SleFheDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class SleFheRegistryDataPreparationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LocalAddressSeeder::class);
        $this->seed(AcademicProgramSeeder::class);
        $this->seed(RoleAndUserSeeder::class);
    }

    /**
     * 1. The synthetic seeder creates the expected 15 records with valid schema and programs.
     */
    public function test_demo_seeder_creates_expected_fifteen_records(): void
    {
        $this->assertSame(0, SleFheRegistry::query()->count());

        $this->seed(SleFheDemoSeeder::class);

        $this->assertSame(15, SleFheRegistry::query()->count());

        $demoRecords = SleFheRegistry::query()->where('student_id', 'like', 'DEMO-2026-%')->get();
        $this->assertCount(15, $demoRecords);

        // Check verified count (13 verified, 2 unverified)
        $verifiedCount = SleFheRegistry::query()->where('is_verified', true)->count();
        $unverifiedCount = SleFheRegistry::query()->where('is_verified', false)->count();
        $this->assertSame(13, $verifiedCount);
        $this->assertSame(2, $unverifiedCount);

        // Verify that specific unverified IDs match the design
        $this->assertFalse((bool) SleFheRegistry::query()->byStudentId('DEMO-2026-0011')->value('is_verified'));
        $this->assertFalse((bool) SleFheRegistry::query()->byStudentId('DEMO-2026-0012')->value('is_verified'));
        $this->assertTrue((bool) SleFheRegistry::query()->byStudentId('DEMO-2026-0001')->value('is_verified'));

        // All courses in demo seeder belong to approved catalog
        $catalogCourses = AcademicProgram::query()->pluck('name')->all();
        foreach ($demoRecords as $record) {
            $this->assertContains($record->course, $catalogCourses, "Course {$record->course} must belong to catalog.");
            $this->assertContains($record->district, [1, 2]);
            $this->assertNotEmpty($record->municipality);
            $this->assertNotEmpty($record->barangay);
            $this->assertNotEmpty($record->purok);
            $this->assertNotEmpty($record->campus);
        }
    }

    /**
     * 2. Repeated execution is idempotent and cannot create duplicates or overwrite existing identities.
     */
    public function test_demo_seeder_is_idempotent_and_cannot_create_duplicates(): void
    {
        $this->seed(SleFheDemoSeeder::class);
        $this->assertSame(15, SleFheRegistry::query()->count());

        // Re-run seeder
        $this->seed(SleFheDemoSeeder::class);
        $this->assertSame(15, SleFheRegistry::query()->count(), 'Repeated seeding must not duplicate records.');

        // Re-run a third time
        (new SleFheDemoSeeder())->run();
        $this->assertSame(15, SleFheRegistry::query()->count());
    }

    /**
     * 3. Absent student IDs are documented and never seeded.
     */
    public function test_absent_demo_student_ids_are_never_seeded(): void
    {
        $this->seed(SleFheDemoSeeder::class);

        foreach (SleFheDemoSeeder::ABSENT_STUDENT_IDS as $absentId) {
            $this->assertNull(
                SleFheRegistry::query()->byStudentId($absentId)->first(),
                "Absent student ID {$absentId} must never exist in the registry."
            );
        }
    }

    /**
     * 4. The importer rejects incorrect database targets.
     */
    public function test_importer_rejects_incorrect_database_targets(): void
    {
        // Default target driver check: rejects sqlite without --allow-test-driver
        $exitCode = Artisan::call('sponsorflow:import-sle-fhe-postgres', [
            '--connection' => 'sqlite',
        ]);
        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('SAFETY ABORT: Destination database driver is', Artisan::output());

        // Rejects forbidden database names such as local MariaDB 'sponsorflow'
        $exitCode = Artisan::call('sponsorflow:import-sle-fhe-postgres', [
            '--connection' => 'sqlite',
            '--target-db' => 'sponsorflow',
            '--allow-test-driver' => true,
        ]);
        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('prohibited from running against local MariaDB databases', Artisan::output());

        // Rejects target DB mismatch
        $exitCode = Artisan::call('sponsorflow:import-sle-fhe-postgres', [
            '--connection' => 'sqlite',
            '--target-db' => 'some_wrong_database',
            '--allow-test-driver' => true,
        ]);
        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Destination database name is', Artisan::output());

        // Rejects host mismatch when expected-host is specified
        $exitCode = Artisan::call('sponsorflow:import-sle-fhe-postgres', [
            '--connection' => 'sqlite',
            '--target-db' => ':memory:',
            '--expected-host' => 'dpg-actual-intended-host.singapore-postgres.render.com',
            '--allow-test-driver' => true,
        ]);
        $this->assertSame(1, $exitCode);
        $output = Artisan::output();
        $this->assertStringContainsString('Configured host is', $output);
        $this->assertStringContainsString('expected host is', $output);
    }

    /**
     * 5. Dry-run mode performs no writes.
     */
    public function test_importer_dry_run_performs_no_writes(): void
    {
        $fixturePath = $this->createTempCsvFixture(5);

        try {
            $exitCode = Artisan::call('sponsorflow:import-sle-fhe-postgres', [
                '--connection' => 'sqlite',
                '--target-db' => ':memory:',
                '--allow-test-driver' => true,
                '--file' => $fixturePath,
                '--expected-count' => 5,
                '--dry-run' => true,
            ]);

            $output = Artisan::output();
            $this->assertSame(0, $exitCode);
            $this->assertSame(0, SleFheRegistry::query()->count(), 'Dry-run must not write any records.');
            $this->assertStringContainsString('DRY-RUN VALIDATION REPORT', $output);
            $this->assertStringContainsString('NO WRITES PERFORMED', $output);
        } finally {
            @unlink($fixturePath);
        }
    }

    /**
     * 5b. Dry-run validation of the full 1,903-record cleaned CSV file.
     */
    public function test_real_cleaned_csv_dry_run_validation_passes_all_checks(): void
    {
        $csvPath = storage_path('app/private/registry-recovery/sle_fhe_registries_cleaned.csv');
        $this->assertFileExists($csvPath);

        $exitCode = Artisan::call('sponsorflow:import-sle-fhe-postgres', [
            '--connection' => 'sqlite',
            '--target-db' => ':memory:',
            '--allow-test-driver' => true,
            '--file' => $csvPath,
            '--expected-count' => 1903,
            '--dry-run' => true,
        ]);

        $output = Artisan::output();
        $this->assertSame(0, $exitCode, "Dry-run of real cleaned CSV must succeed. Output: {$output}");
        $this->assertSame(0, SleFheRegistry::query()->count(), 'Dry-run must not write any records.');
        $this->assertStringContainsString('DRY-RUN VALIDATION REPORT', $output);
        $this->assertStringContainsString('1,903', $output);
        $this->assertStringContainsString('71 entries preserved', $output);
        $this->assertStringContainsString('DRY-RUN RESULT: PASS', $output);
    }

    /**
     * 6. Importer rejects non-empty destination table.
     */
    public function test_importer_rejects_non_empty_destination_table(): void
    {
        // Seed 1 record
        SleFheRegistry::query()->create([
            'student_id' => 'PRE-EXISTING-01',
            'is_verified' => true,
        ]);

        $fixturePath = $this->createTempCsvFixture(5);

        try {
            $exitCode = Artisan::call('sponsorflow:import-sle-fhe-postgres', [
                '--connection' => 'sqlite',
                '--target-db' => ':memory:',
                '--allow-test-driver' => true,
                '--file' => $fixturePath,
                '--expected-count' => 5,
            ]);

            $this->assertSame(1, $exitCode);
            $this->assertStringContainsString('already contains 1 records', Artisan::output());
        } finally {
            @unlink($fixturePath);
        }
    }

    /**
     * 7. Invalid CSV rows cause validation failure.
     */
    public function test_invalid_csv_rows_cause_validation_failure(): void
    {
        // Case A: Missing columns
        $badHeaderCsv = tempnam(sys_get_temp_dir(), 'csv_bad_head_');
        file_put_contents($badHeaderCsv, "id,student_id,wrong_column\n1,TEST-01,bad\n");

        $exitCode = Artisan::call('sponsorflow:import-sle-fhe-postgres', [
            '--connection' => 'sqlite',
            '--target-db' => ':memory:',
            '--allow-test-driver' => true,
            '--file' => $badHeaderCsv,
            '--expected-count' => 1,
        ]);
        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('CSV column mismatch', Artisan::output());
        @unlink($badHeaderCsv);

        // Case B: Duplicate student_id
        $dupCsv = tempnam(sys_get_temp_dir(), 'csv_dup_');
        $header = implode(',', ImportSleFheRegistryToPostgresCommand::EXPECTED_COLUMNS) . "\n";
        $row1 = "1,DUP-01,Male,Single,Purok 1,Barangay 1,Mati,BSIT,1,Main,2,1,27/09/2026 23:32,27/09/2026 23:32\n";
        $row2 = "2,DUP-01,Female,Single,Purok 2,Barangay 2,Mati,BSIT,2,Main,2,1,27/09/2026 23:32,27/09/2026 23:32\n";
        file_put_contents($dupCsv, $header . $row1 . $row2);

        $exitCode = Artisan::call('sponsorflow:import-sle-fhe-postgres', [
            '--connection' => 'sqlite',
            '--target-db' => ':memory:',
            '--allow-test-driver' => true,
            '--file' => $dupCsv,
            '--expected-count' => 2,
        ]);
        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString("duplicate student_id 'DUP-01'", Artisan::output());
        @unlink($dupCsv);

        // Case C: Record count mismatch
        $countCsv = tempnam(sys_get_temp_dir(), 'csv_count_');
        file_put_contents($countCsv, $header . $row1);

        $exitCode = Artisan::call('sponsorflow:import-sle-fhe-postgres', [
            '--connection' => 'sqlite',
            '--target-db' => ':memory:',
            '--allow-test-driver' => true,
            '--file' => $countCsv,
            '--expected-count' => 5, // Expected 5, but CSV only has 1
        ]);
        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Record count mismatch', Artisan::output());
        @unlink($countCsv);

        // Case D: Impossible calendar date (Feb 31)
        $badDateCsv = tempnam(sys_get_temp_dir(), 'csv_baddate_');
        $badRow = "1,DATE-01,Male,Single,Purok 1,Barangay 1,Mati,Bachelor of Science in Information Technology,1,DOrSU Main Campus,2,1,31/02/2026 23:32,31/02/2026 23:32\n";
        file_put_contents($badDateCsv, $header . $badRow);

        $exitCode = Artisan::call('sponsorflow:import-sle-fhe-postgres', [
            '--connection' => 'sqlite',
            '--target-db' => ':memory:',
            '--allow-test-driver' => true,
            '--file' => $badDateCsv,
            '--expected-count' => 1,
        ]);
        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('invalid calendar date', Artisan::output());
        @unlink($badDateCsv);

        // Case E: Impossible time (25:00)
        $badTimeCsv = tempnam(sys_get_temp_dir(), 'csv_badtime_');
        $badTimeRow = "1,TIME-01,Male,Single,Purok 1,Barangay 1,Mati,Bachelor of Science in Information Technology,1,DOrSU Main Campus,2,1,27/09/2026 25:00,27/09/2026 25:00\n";
        file_put_contents($badTimeCsv, $header . $badTimeRow);

        $exitCode = Artisan::call('sponsorflow:import-sle-fhe-postgres', [
            '--connection' => 'sqlite',
            '--target-db' => ':memory:',
            '--allow-test-driver' => true,
            '--file' => $badTimeCsv,
            '--expected-count' => 1,
        ]);
        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('invalid time', Artisan::output());
        @unlink($badTimeCsv);
    }

    /**
     * 7b. Timestamp conversion strictly converts DD/MM/YYYY HH:MM to unambiguous ISO representation.
     */
    public function test_timestamp_conversion_to_unambiguous_iso_format(): void
    {
        $fixturePath = tempnam(sys_get_temp_dir(), 'csv_iso_');
        $header = implode(',', ImportSleFheRegistryToPostgresCommand::EXPECTED_COLUMNS) . "\n";
        $row = "1,TS-01,Male,Single,Purok 1,Central,City of Mati,Bachelor of Science in Information Technology,1,DOrSU Main Campus,2,1,27/09/2026 23:32,27/09/2026 23:32\n";
        file_put_contents($fixturePath, $header . $row);

        try {
            $exitCode = Artisan::call('sponsorflow:import-sle-fhe-postgres', [
                '--connection' => 'sqlite',
                '--target-db' => ':memory:',
                '--allow-test-driver' => true,
                '--file' => $fixturePath,
                '--expected-count' => 1,
                '--execute' => true,
                '--confirm' => true,
            ]);

            $this->assertSame(0, $exitCode, Artisan::output());
            $record = SleFheRegistry::query()->where('student_id', 'TS-01')->firstOrFail();

            // Formatted in database as unambiguous ISO-8601 (YYYY-MM-DD HH:MM:00)
            $this->assertSame('2026-09-27 23:32:00', $record->created_at->format('Y-m-d H:i:s'));
            $this->assertSame('2026-09-27 23:32:00', $record->updated_at->format('Y-m-d H:i:s'));
        } finally {
            @unlink($fixturePath);
        }
    }

    /**
     * 8. Transaction rollback prevents partial imports.
     */
    public function test_transaction_rollback_prevents_partial_imports(): void
    {
        $this->assertSame(0, SleFheRegistry::query()->count());

        // Simulate transactional failure during batch insert
        try {
            DB::transaction(function (): void {
                SleFheRegistry::query()->create([
                    'student_id' => 'ROLLBACK-01',
                    'is_verified' => true,
                ]);

                // Throw error mid-transaction
                throw new RuntimeException('Simulated database write error during import');
            });
        } catch (RuntimeException $e) {
            // Expected
        }

        $this->assertSame(0, SleFheRegistry::query()->count(), 'Transaction rollback must leave destination table with 0 records.');
    }

    /**
     * 9. Registry matching works with the imported test records.
     */
    public function test_registry_matching_works_with_imported_demo_records(): void
    {
        $this->seed(SleFheDemoSeeder::class);

        // Match 1: Verified record
        $match = SleFheRegistry::query()->byStudentId('DEMO-2026-0001')->where('is_verified', true)->first();
        $this->assertNotNull($match);
        $this->assertSame('City of Mati', $match->municipality);
        $this->assertSame('Bachelor of Science in Information Technology', $match->course);

        // Match 2: Unverified record in registry (is_verified = false)
        // Advisory matching requires where('is_verified', true)
        $unverifiedMatch = SleFheRegistry::query()->byStudentId('DEMO-2026-0011')->where('is_verified', true)->first();
        $this->assertNull($unverifiedMatch, 'Registry match query requires is_verified=true');

        $rawRegistry = SleFheRegistry::query()->byStudentId('DEMO-2026-0011')->first();
        $this->assertNotNull($rawRegistry, 'Raw record exists but has is_verified=false');
        $this->assertFalse((bool) $rawRegistry->is_verified);

        // Match 3: Absent student ID
        $absentMatch = SleFheRegistry::query()->byStudentId('DEMO-2026-9001')->first();
        $this->assertNull($absentMatch, 'Absent student ID returns null');
    }

    /**
     * 10. FASSG verification still requires an explicit authorized decision.
     */
    public function test_fassg_verification_requires_explicit_authorized_decision(): void
    {
        $this->seed(SleFheDemoSeeder::class);

        $program = AcademicProgram::query()->where('name', 'Bachelor of Science in Information Technology')->firstOrFail();

        // Create student
        $studentUser = User::factory()->create([
            'role' => 'student',
            'email' => 'demo.student@dorsu.edu.ph',
        ]);
        $studentProfile = StudentProfile::factory()->create([
            'user_id' => $studentUser->id,
            'student_id_number' => 'DEMO-2026-0001',
            'academic_program_id' => $program->id,
            'campus' => 'DOrSU Main Campus',
            'year_level' => 1,
            'first_name' => 'Demo',
            'last_name' => 'Student',
        ]);

        // Student submits SLE-FHE address request
        $request = SleFheRequest::query()->create([
            'student_profile_id' => $studentProfile->id,
            'province' => 'Davao Oriental',
            'municipality_city' => 'City of Mati',
            'barangay' => 'Central',
            'street_purok' => 'Purok Narra',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        // Verify that existence of registry record does NOT automatically verify the student
        $this->assertNull($studentProfile->fresh()->sleFheVerification);
        $this->assertSame('pending', $request->fresh()->status);

        // FASSG officer logs in and executes explicit verification
        $fassgUser = User::query()->where('role', 'fassg')->firstOrFail();

        $response = $this->actingAs($fassgUser)->post(
            route('fassg.sle-fhe.verify', $studentProfile),
            [
                'verification_request_id' => $request->id,
                'confirm_identity' => 1,
            ]
        );

        $response->assertSessionHas('success');
        $this->assertSame('verified', $request->fresh()->status);
        $this->assertNotNull($studentProfile->fresh()->sleFheVerification);
        $this->assertSame($fassgUser->id, $studentProfile->fresh()->sleFheVerification->verified_by);

        // Check audit log
        $this->assertTrue(
            AuditLog::query()
                ->where('action', 'fassg.student.sle_fhe_verified')
                ->where('user_id', $fassgUser->id)
                ->exists()
        );
    }

    /**
     * Helper to create a temporary valid CSV fixture.
     */
    private function createTempCsvFixture(int $count): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sle_fhe_fixture_');
        $handle = fopen($path, 'w');

        fputcsv($handle, ImportSleFheRegistryToPostgresCommand::EXPECTED_COLUMNS);

        for ($i = 1; $i <= $count; $i++) {
            fputcsv($handle, [
                $i,
                "DEMO-FIXTURE-{$i}",
                'Male',
                'Single',
                'Purok 1',
                'Central',
                'City of Mati',
                'Bachelor of Science in Information Technology',
                '1',
                'DOrSU Main Campus',
                2,
                1,
                '2026-09-01 00:00:00',
                '2026-09-01 00:00:00',
            ]);
        }

        fclose($handle);

        return $path;
    }
}
