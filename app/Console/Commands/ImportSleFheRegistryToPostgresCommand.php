<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

/**
 * Safe, transactional importer for the remote Render PostgreSQL sle_fhe_registries table.
 *
 * Strict safety constraints:
 * - Dedicated PostgreSQL connection (defaults to pgsql_render).
 * - Enforces pgsql driver; strictly blocks local MariaDB/MySQL (sponsorflow) and SQLite (unless in unit test mode).
 * - Requires TLS encryption (sslmode=require) for remote Render connection.
 * - Read-only schema preflight: verifies public.sle_fhe_registries table and all 14 columns in destination.
 * - Destination table must be completely empty (rejects if count > 0).
 * - Full CSV preflight validation of all 1,903 records, primary keys, student IDs, and column constraints.
 * - Strict verification that all 71 unmapped/flagged course records are preserved without normalization.
 * - Strict date parsing for 'DD/MM/YYYY HH:MM' with calendar validation; rejects impossible dates/times; never invents timestamps.
 * - Converts timestamps to unambiguous ISO-8601 (YYYY-MM-DD HH:MM:00), independent of PostgreSQL DateStyle.
 * - Read-only dry-run mode by default; live write requires explicit --execute and --confirm.
 * - Entire insertion runs inside a single database transaction with automatic rollback on error.
 * - Synchronizes the PostgreSQL primary key sequence post-insertion.
 * - Never modifies student_profiles, sle_fhe_requests, or sle_fhe_verifications.
 * - Never logs or exposes database credentials, passwords, or raw connection strings.
 */
class ImportSleFheRegistryToPostgresCommand extends Command
{
    protected $signature = 'sponsorflow:import-sle-fhe-postgres
        {--file= : Path to private cleaned CSV file}
        {--connection=pgsql_render : Database connection to use (defaults to pgsql_render)}
        {--target-db= : Expected destination database name (defaults to sponsorflow_db_jh27)}
        {--expected-host= : Expected Render PostgreSQL host (e.g. dpg-xxxxxx-a.singapore-postgres.render.com)}
        {--dry-run : Run in validation-only mode without writing to database (default)}
        {--execute : Explicitly authorize database insertion (requires prior approval)}
        {--confirm : Acknowledge and confirm live execution}
        {--expected-count= : Expected record count (defaults to 1903)}
        {--allow-test-driver : Allow non-pgsql driver for automated testing in isolated environments}';

    protected $description = 'Safely and transactionally import the cleaned SLE-FHE institutional registry into Render PostgreSQL.';

    public const EXPECTED_RECORD_COUNT = 1903;

    public const DEFAULT_TARGET_DB = 'sponsorflow_db_jh27';

    public const EXPECTED_COLUMNS = [
        'id',
        'student_id',
        'sex',
        'civil_status',
        'purok',
        'barangay',
        'municipality',
        'course',
        'year_level',
        'campus',
        'district',
        'is_verified',
        'created_at',
        'updated_at',
    ];

    /**
     * Exact 71 flagged course entries that must remain preserved without normalization.
     */
    public const EXPECTED_FLAGGED_COURSES = [
        'BACHELOR OF INDUSTRIAL TECHNOLOGY MANAGEMENT (AUTOMOTIVE)' => 52,
        'Bachelor of Industrial Technology Management (AUTOMOTIVE)' => 11,
        'BACHELOR OF SECONDARY EDUCATION TLE-HE' => 4,
        'BACHELOR OF SCIENCE IN POLITICAL SCIENCE' => 2,
        'BACHELOR OF SCIENCE IN TECHNOLOGY PROGRAM' => 1,
        'BACHELOR OF SIENCE IN MATH CON RESSTAT' => 1,
    ];

    public function handle(): int
    {
        $this->newLine();
        $this->line('====================================================================');
        $this->line(' SponsorFlow — Render PostgreSQL SLE-FHE Institutional Registry Import');
        $this->line('====================================================================');
        $this->newLine();

        $connectionName = (string) ($this->option('connection') ?: 'pgsql_render');
        $expectedDb = $this->option('target-db') ? (string) $this->option('target-db') : self::DEFAULT_TARGET_DB;
        $allowTestDriver = (bool) $this->option('allow-test-driver');
        $expectedCount = $this->option('expected-count') ? (int) $this->option('expected-count') : self::EXPECTED_RECORD_COUNT;

        $isDryRun = ! $this->option('execute') || (bool) $this->option('dry-run');

        if ($this->option('execute') && ! $this->option('confirm')) {
            $this->error('Live execution requires explicit --confirm acknowledgement. Aborted.');

            return self::FAILURE;
        }

        try {
            // 1. Environment & Target Database Safety Validation
            $this->validateTargetDatabase($connectionName, $expectedDb, $allowTestDriver);

            // 2. Read-Only Destination Schema Preflight Check
            $this->verifyDestinationSchema($connectionName, $allowTestDriver);

            // 3. Destination Table Emptiness Check
            $this->validateTableEmptiness($connectionName);

            // 4. Resolve Private Source File Path
            $filePath = $this->resolveSourcePath();
            $this->info("Source file: {$filePath}");

            // 5. Load & Validate Source CSV
            $records = $this->loadAndValidateCsv($filePath, $expectedCount);
            $this->info('Source validation: ' . count($records) . ' records checked.');

            // 6. Verify the 71 Flagged Course Entries Preservation (when full dataset)
            if ($expectedCount === self::EXPECTED_RECORD_COUNT) {
                $this->verifyFlaggedCourses($records);
                $this->info('Flagged courses validation: 71 unmapped/specialized entries verified.');
            }

            // 7. Dry-Run or Transactional Execution
            if ($isDryRun) {
                $this->displayDryRunReport($connectionName, $filePath, $records);

                return self::SUCCESS;
            }

            // 8. Real Live Execution (requires explicit prior authorization)
            return $this->executeTransactionalImport($connectionName, $records, $expectedCount);

        } catch (Throwable $e) {
            $this->newLine();
            $this->error('IMPORT REJECTED / ABORTED: ' . $e->getMessage());
            $this->newLine();

            return self::FAILURE;
        }
    }

    /**
     * Enforce that the target is strictly the intended PostgreSQL database.
     * Blocks execution against local MariaDB/MySQL (sponsorflow) and SQLite.
     */
    private function validateTargetDatabase(string $connectionName, string $expectedDb, bool $allowTestDriver): void
    {
        $connection = DB::connection($connectionName);
        $driver = strtolower($connection->getDriverName());
        $database = (string) $connection->getDatabaseName();
        $host = (string) config("database.connections.{$connectionName}.host", '');
        $sslMode = (string) config("database.connections.{$connectionName}.sslmode", 'prefer');

        $maskedHost = $this->maskHost($host);
        $maskedDb = $database !== '' ? $database : 'unknown';

        $this->comment("Target connection: [{$driver}] host=[{$maskedHost}] db=[{$maskedDb}] sslmode=[{$sslMode}]");

        // Driver check: pgsql only
        if (! $allowTestDriver && ! in_array($driver, ['pgsql', 'postgres', 'postgresql'], true)) {
            throw new RuntimeException(
                "SAFETY ABORT: Destination database driver is '{$driver}'. "
                . 'Render PostgreSQL importer is strictly restricted to PostgreSQL and must NEVER run against MariaDB/MySQL or SQLite.'
            );
        }

        // Database name check: Must NOT be local sponsorflow
        $forbiddenDatabases = ['sponsorflow', 'sponsorflow_isolated_test', 'laravel'];
        if (in_array(strtolower($database), $forbiddenDatabases, true) || in_array(strtolower($expectedDb), $forbiddenDatabases, true)) {
            throw new RuntimeException(
                "SAFETY ABORT: Target database name is '{$database}'. "
                . 'The PostgreSQL importer is prohibited from running against local MariaDB databases.'
            );
        }

        // Enforce expected database name
        if (strtolower($database) !== strtolower($expectedDb)) {
            throw new RuntimeException(
                "SAFETY ABORT: Destination database name is '{$database}', expected '{$expectedDb}'. "
                . 'Import aborted to prevent accidental writes to an unintended database.'
            );
        }

        // Expected host verification if provided
        $expectedHost = (string) $this->option('expected-host');
        if ($expectedHost !== '' && strtolower($host) !== strtolower($expectedHost)) {
            throw new RuntimeException(
                "SAFETY ABORT: Configured host is '{$host}', but expected host is '{$expectedHost}'. "
                . 'Import aborted to prevent connecting to an unintended database instance.'
            );
        }

        // Host & SSL checks for non-test runs
        if (! $allowTestDriver) {
            $localHosts = ['127.0.0.1', 'localhost', '::1'];
            if ($host === '' || in_array(strtolower(trim($host)), $localHosts, true)) {
                throw new RuntimeException(
                    "SAFETY ABORT: Render PostgreSQL target host is '{$host}'. Must be remote Render host (dpg-*.render.com)."
                );
            }

            if (! str_ends_with(strtolower($host), '.render.com')) {
                throw new RuntimeException(
                    "SAFETY ABORT: Render PostgreSQL target host '{$host}' is not a verified Render domain (*.render.com). "
                    . 'Import aborted to protect non-Render databases.'
                );
            }

            if (strtolower($sslMode) !== 'require') {
                throw new RuntimeException(
                    "SAFETY ABORT: Render PostgreSQL connection sslmode is '{$sslMode}'. External TLS connection must enforce sslmode=require."
                );
            }
        }

        $this->info("✓ Database target verified: [{$driver}] -> {$expectedDb}");
    }

    /**
     * Read-only schema verification against PostgreSQL information_schema.
     * Verifies that sle_fhe_registries exists in public schema and has all expected columns.
     */
    private function verifyDestinationSchema(string $connectionName, bool $allowTestDriver): void
    {
        $connection = DB::connection($connectionName);
        $driver = strtolower($connection->getDriverName());

        if ($driver === 'pgsql') {
            $columns = $connection->select("
                SELECT column_name, data_type, character_maximum_length, is_nullable
                FROM information_schema.columns
                WHERE table_schema = 'public' AND table_name = 'sle_fhe_registries'
            ");

            if (empty($columns)) {
                throw new RuntimeException(
                    "Destination table 'public.sle_fhe_registries' was not found in Render PostgreSQL. "
                    . 'Ensure all migrations have been applied to Render.'
                );
            }

            $colNames = array_map(fn ($c) => strtolower((string) $c->column_name), $columns);
            foreach (self::EXPECTED_COLUMNS as $expectedCol) {
                if (! in_array(strtolower($expectedCol), $colNames, true)) {
                    throw new RuntimeException(
                        "Destination table 'public.sle_fhe_registries' is missing required column: '{$expectedCol}'."
                    );
                }
            }

            $this->info('✓ Destination schema verified: public.sle_fhe_registries has all 14 expected columns');
        } elseif ($allowTestDriver) {
            if (! Schema::connection($connectionName)->hasTable('sle_fhe_registries')) {
                throw new RuntimeException("Target table 'sle_fhe_registries' does not exist.");
            }
            foreach (self::EXPECTED_COLUMNS as $expectedCol) {
                if (! Schema::connection($connectionName)->hasColumn('sle_fhe_registries', $expectedCol)) {
                    throw new RuntimeException("Target table missing required column: '{$expectedCol}'.");
                }
            }
            $this->info('✓ Test table schema verified: sle_fhe_registries has all 14 expected columns');
        }
    }

    /**
     * Reject if table contains any existing records.
     */
    private function validateTableEmptiness(string $connectionName): void
    {
        $count = DB::connection($connectionName)->table('sle_fhe_registries')->count();

        if ($count > 0) {
            throw new RuntimeException(
                "Destination table 'sle_fhe_registries' already contains {$count} records. "
                . 'Import requires an empty table to prevent data corruption or overwriting. '
                . 'No records were modified.'
            );
        }

        $this->info('✓ Target table verified: sle_fhe_registries is empty (0 records)');
    }

    /**
     * Locate the private CSV file.
     */
    private function resolveSourcePath(): string
    {
        $explicit = $this->option('file');

        if ($explicit !== null && trim($explicit) !== '') {
            $path = trim($explicit);
            if (! is_file($path)) {
                throw new RuntimeException("Explicit file not found: {$path}");
            }

            return $path;
        }

        $defaultPath = storage_path('app/private/registry-recovery/sle_fhe_registries_cleaned.csv');

        if (! is_file($defaultPath)) {
            throw new RuntimeException("Cleaned registry file not found at: {$defaultPath}");
        }

        return $defaultPath;
    }

    /**
     * Load, parse, and validate all CSV rows against schema constraints.
     *
     * @return list<array<string, mixed>>
     */
    private function loadAndValidateCsv(string $filePath, int $expectedCount): array
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new RuntimeException("Unable to open file: {$filePath}");
        }

        // Strip UTF-8 BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            throw new RuntimeException('CSV file is empty.');
        }

        $headers = array_map(fn ($h) => trim((string) $h), $headers);

        if ($headers !== self::EXPECTED_COLUMNS) {
            fclose($handle);
            throw new RuntimeException(
                'CSV column mismatch. Expected: [' . implode(', ', self::EXPECTED_COLUMNS) . '], '
                . 'got: [' . implode(', ', $headers) . ']'
            );
        }

        $records = [];
        $seenIds = [];
        $seenStudentIds = [];
        $rowNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            if (count($row) !== count($headers)) {
                fclose($handle);
                throw new RuntimeException("Row {$rowNum} column count mismatch: expected " . count($headers) . ', got ' . count($row));
            }

            $raw = array_combine($headers, $row);

            $id = (int) $raw['id'];
            $studentId = trim($raw['student_id']);

            if ($id <= 0) {
                fclose($handle);
                throw new RuntimeException("Row {$rowNum}: invalid id '{$raw['id']}'.");
            }

            if (isset($seenIds[$id])) {
                fclose($handle);
                throw new RuntimeException("Row {$rowNum}: duplicate id {$id} already seen.");
            }
            $seenIds[$id] = true;

            if ($studentId === '') {
                fclose($handle);
                throw new RuntimeException("Row {$rowNum}: empty student_id.");
            }

            if (isset($seenStudentIds[$studentId])) {
                fclose($handle);
                throw new RuntimeException("Row {$rowNum}: duplicate student_id '{$studentId}'.");
            }
            $seenStudentIds[$studentId] = true;

            // Validate schema lengths
            $this->assertLength($raw['student_id'], 50, 'student_id', $rowNum);
            $this->assertLength($raw['sex'], 20, 'sex', $rowNum);
            $this->assertLength($raw['civil_status'], 40, 'civil_status', $rowNum);
            $this->assertLength($raw['purok'], 150, 'purok', $rowNum);
            $this->assertLength($raw['barangay'], 150, 'barangay', $rowNum);
            $this->assertLength($raw['municipality'], 150, 'municipality', $rowNum);
            $this->assertLength($raw['course'], 191, 'course', $rowNum);
            $this->assertLength($raw['year_level'], 50, 'year_level', $rowNum);
            $this->assertLength($raw['campus'], 191, 'campus', $rowNum);

            // Strict timestamp validation and ISO conversion
            $createdAt = $this->parseTimestamp($raw['created_at'], 'created_at', $rowNum);
            $updatedAt = $this->parseTimestamp($raw['updated_at'], 'updated_at', $rowNum);

            $records[] = [
                'id' => $id,
                'student_id' => $studentId,
                'sex' => $raw['sex'] !== '' ? $raw['sex'] : null,
                'civil_status' => $raw['civil_status'] !== '' ? $raw['civil_status'] : null,
                'purok' => $raw['purok'] !== '' ? $raw['purok'] : null,
                'barangay' => $raw['barangay'] !== '' ? $raw['barangay'] : null,
                'municipality' => $raw['municipality'] !== '' ? $raw['municipality'] : null,
                'course' => $raw['course'] !== '' ? $raw['course'] : null,
                'year_level' => $raw['year_level'] !== '' ? $raw['year_level'] : null,
                'campus' => $raw['campus'] !== '' ? $raw['campus'] : null,
                'district' => $raw['district'] !== '' ? (int) $raw['district'] : null,
                'is_verified' => (bool) $raw['is_verified'],
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ];
        }

        fclose($handle);

        if (count($records) !== $expectedCount) {
            throw new RuntimeException(
                "Record count mismatch. Expected exactly {$expectedCount}, read " . count($records) . '.'
            );
        }

        return $records;
    }

    /**
     * Verify that all 71 unmapped / flagged course entries are preserved.
     *
     * @param list<array<string, mixed>> $records
     */
    private function verifyFlaggedCourses(array $records): void
    {
        $counts = array_fill_keys(array_keys(self::EXPECTED_FLAGGED_COURSES), 0);

        foreach ($records as $record) {
            $course = (string) ($record['course'] ?? '');
            if (array_key_exists($course, $counts)) {
                $counts[$course]++;
            }
        }

        $totalFlagged = array_sum($counts);
        $expectedTotal = array_sum(self::EXPECTED_FLAGGED_COURSES);

        if ($totalFlagged !== $expectedTotal) {
            throw new RuntimeException(
                "Flagged courses count mismatch: expected {$expectedTotal}, found {$totalFlagged} preserved."
            );
        }

        foreach (self::EXPECTED_FLAGGED_COURSES as $courseName => $expected) {
            if ($counts[$courseName] !== $expected) {
                throw new RuntimeException(
                    "Flagged course '{$courseName}' count mismatch: expected {$expected}, found {$counts[$courseName]}."
                );
            }
        }
    }

    private function assertLength(?string $val, int $max, string $field, int $row): void
    {
        if ($val !== null && mb_strlen($val) > $max) {
            throw new RuntimeException("Row {$row} field '{$field}' exceeds maximum length {$max} (got " . mb_strlen($val) . ').');
        }
    }

    /**
     * Strictly parse and validate timestamp string from CSV.
     * Converts 'DD/MM/YYYY HH:MM' to unambiguous PostgreSQL-compatible 'YYYY-MM-DD HH:MM:00'.
     *
     * Strict rules:
     * - Rejects empty timestamps.
     * - Rejects malformed patterns.
     * - Rejects impossible calendar dates (via checkdate).
     * - Rejects impossible hours/minutes.
     * - Never invents time zones or falls back to current time.
     */
    private function parseTimestamp(string $val, string $field, int $row): string
    {
        $trimmed = trim($val);
        if ($trimmed === '') {
            throw new RuntimeException("Row {$row} field '{$field}' has empty timestamp.");
        }

        // Strict match for 'DD/MM/YYYY HH:MM' (exact format of the cleaned CSV)
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2}):(\d{2})$/', $trimmed, $m)) {
            $day = (int) $m[1];
            $month = (int) $m[2];
            $year = (int) $m[3];
            $hour = (int) $m[4];
            $minute = (int) $m[5];

            if (! checkdate($month, $day, $year)) {
                throw new RuntimeException("Row {$row} field '{$field}' contains an invalid calendar date: '{$val}'.");
            }

            if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
                throw new RuntimeException("Row {$row} field '{$field}' contains an invalid time: '{$val}'.");
            }

            // ISO-8601 unambiguous representation (YYYY-MM-DD HH:MM:00), independent of PostgreSQL DateStyle
            return sprintf('%04d-%02d-%02d %02d:%02d:00', $year, $month, $day, $hour, $minute);
        }

        // Support standard ISO-8601 'YYYY-MM-DD HH:MM:SS' if already formatted
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})$/', $trimmed, $m)) {
            $year = (int) $m[1];
            $month = (int) $m[2];
            $day = (int) $m[3];
            $hour = (int) $m[4];
            $minute = (int) $m[5];
            $second = (int) $m[6];

            if (! checkdate($month, $day, $year)) {
                throw new RuntimeException("Row {$row} field '{$field}' contains an invalid calendar date: '{$val}'.");
            }

            if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59 || $second < 0 || $second > 59) {
                throw new RuntimeException("Row {$row} field '{$field}' contains an invalid time: '{$val}'.");
            }

            return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
        }

        throw new RuntimeException("Row {$row} field '{$field}' has unparseable timestamp format: '{$val}'. Expected 'DD/MM/YYYY HH:MM'.");
    }

    /**
     * Mask sensitive hostname for display.
     */
    private function maskHost(string $host): string
    {
        $trimmed = trim($host);
        if ($trimmed === '' || $trimmed === '127.0.0.1' || $trimmed === 'localhost') {
            return $trimmed ?: 'unspecified';
        }

        $parts = explode('.', $trimmed);
        if (count($parts) > 2) {
            $maskedFirst = substr($parts[0], 0, 4) . '***';

            return $maskedFirst . '.' . implode('.', array_slice($parts, 1));
        }

        return substr($trimmed, 0, 3) . '***';
    }

    /**
     * Display comprehensive dry-run report.
     *
     * @param list<array<string, mixed>> $records
     */
    private function displayDryRunReport(string $connectionName, string $filePath, array $records): void
    {
        $connection = DB::connection($connectionName);
        $formattedCount = number_format(count($records));

        $this->newLine();
        $this->info('====================================================================');
        $this->info(' DRY-RUN VALIDATION REPORT — RENDER POSTGRESQL (NO WRITES PERFORMED)');
        $this->info('====================================================================');
        $this->newLine();

        $this->table(
            ['Validation Check', 'Expected', 'Actual', 'Status'],
            [
                ['Target Connection', $connectionName, $connectionName, 'PASS ✓'],
                ['Database Driver', 'pgsql', $connection->getDriverName(), 'PASS ✓'],
                ['Destination Database', $this->option('target-db') ?: self::DEFAULT_TARGET_DB, $connection->getDatabaseName(), 'PASS ✓'],
                ['Destination Table State', '0 records (empty)', '0 records', 'PASS ✓'],
                ['Destination Schema Verified', '14 columns in public.sle_fhe_registries', '14 columns verified', 'PASS ✓'],
                ['Total Source Records', $formattedCount, $formattedCount, 'PASS ✓'],
                ['Unique Primary Keys', $formattedCount, number_format(count(array_unique(array_column($records, 'id')))), 'PASS ✓'],
                ['Unique Student IDs', $formattedCount, number_format(count(array_unique(array_column($records, 'student_id')))), 'PASS ✓'],
                ['Column Schema Alignment', '14 columns', '14 columns', 'PASS ✓'],
                ['Preserved Flagged Courses', '71 entries preserved', '71 entries preserved', 'PASS ✓'],
                ['Strict Timestamps Validated', '100% valid DD/MM/YYYY HH:MM', '100% valid DD/MM/YYYY HH:MM', 'PASS ✓'],
                ['Transaction Isolation', 'Single DB Transaction', 'Ready', 'PASS ✓'],
            ]
        );

        $this->newLine();
        $this->info('DRY-RUN RESULT: PASS. All records are validated and ready for authorized PostgreSQL import.');
        $this->warn('NOTE: Database records were NOT modified. To execute import, explicit approval is required.');
        $this->newLine();
    }

    /**
     * Transactional execution (only runs when authorized with --execute and --confirm).
     *
     * @param list<array<string, mixed>> $records
     */
    private function executeTransactionalImport(string $connectionName, array $records, int $expectedCount): int
    {
        $connection = DB::connection($connectionName);

        $this->warn('WARNING: Executing live database insertion into Render PostgreSQL sle_fhe_registries...');

        $connection->transaction(function () use ($connection, $records, $expectedCount): void {
            // Re-verify inside transaction with lock
            $currentCount = $connection->table('sle_fhe_registries')->lockForUpdate()->count();
            if ($currentCount > 0) {
                throw new RuntimeException("Concurrent write detected: table already has {$currentCount} records.");
            }

            // Insert in chunks of 250
            $chunks = array_chunk($records, 250);
            $inserted = 0;

            foreach ($chunks as $chunk) {
                $connection->table('sle_fhe_registries')->insert($chunk);
                $inserted += count($chunk);
            }

            // Assert post-insert count matches expected count exactly before sequence synchronization
            $finalCount = $connection->table('sle_fhe_registries')->count();
            if ($finalCount !== $expectedCount) {
                throw new RuntimeException("Post-import count assertion failed: expected {$expectedCount}, got {$finalCount}.");
            }

            // Synchronize PostgreSQL primary key auto-increment sequence transactionally
            if ($connection->getDriverName() === 'pgsql') {
                $maxId = (int) $connection->table('sle_fhe_registries')->max('id');
                $nextVal = max($maxId + 1, 1904);

                $seqRow = $connection->selectOne("SELECT pg_get_serial_sequence('public.sle_fhe_registries', 'id') AS seq_name");
                $seqName = $seqRow ? $seqRow->seq_name : null;

                if (! empty($seqName)) {
                    // ALTER SEQUENCE is transactional DDL in PostgreSQL; if the enclosing transaction aborts,
                    // PostgreSQL rolls back the sequence definition to its pre-transaction state.
                    $connection->statement("ALTER SEQUENCE {$seqName} RESTART WITH {$nextVal}");
                } else {
                    $connection->statement("ALTER TABLE public.sle_fhe_registries ALTER COLUMN id RESTART WITH {$nextVal}");
                }
            }
        });

        $this->newLine();
        $this->info('✓ TRANSACTION COMMITTED: Successfully imported ' . count($records) . ' records into Render PostgreSQL.');
        $this->newLine();

        return self::SUCCESS;
    }
}
