<?php

declare(strict_types=1);

namespace Database\Seeders;

use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * One-time, non-destructive import of the reviewed institutional masterlist.
 *
 * Put the CSV in Render's Secret Files as sle_fhe_registries_cleaned.csv.
 * Never place actual student records in this class, a migration, or Git.
 *
 * Execute only from the Render runtime, after a verified backup and
 * institutional authorization. Repeated deploys will not overwrite records.
 */
final class SleFheInstitutionalRegistrySeeder extends Seeder
{
    private const EXPECTED_DATABASE = 'sponsorflow_db_jh27';
    private const EXPECTED_COUNT = 1903;
    private const EXPECTED_CANONICAL_SHA256 = 'f1d9ee36a1f75a0234b80613e0e8b0cc063d570d5abc849bc5906d3c84e10af2';
    private const COLUMNS = [
        'id', 'student_id', 'sex', 'civil_status', 'purok',
        'barangay', 'municipality', 'course', 'year_level',
        'campus', 'district', 'is_verified', 'created_at', 'updated_at',
    ];

    public function run(): void
    {
        if (getenv('SPONSORFLOW_SEED_REGISTRY_ON_DEPLOY') !== 'true') {
            throw new RuntimeException('Registry seed requires the explicit Render deploy opt-in.');
        }

        if (config('database.default') !== 'pgsql') {
            throw new RuntimeException('Registry seed requires the production pgsql connection.');
        }

        $db = DB::connection('pgsql');
        $databaseName = (string) ($db->selectOne('SELECT current_database() AS name')->name ?? '');
        if ($databaseName !== self::EXPECTED_DATABASE) {
            throw new RuntimeException('Registry seed refused: unexpected PostgreSQL database.');
        }

        $path = getenv('SLE_FHE_REGISTRY_CSV_PATH') ?: '/etc/secrets/sle_fhe_registries_cleaned.csv';
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Registry seed refused: private CSV is missing or unreadable.');
        }

        $bytes = file_get_contents($path);
        if ($bytes === false) {
            throw new RuntimeException('Registry seed refused: cannot read private CSV.');
        }
        // The Render Secret File editor may normalize Windows CRLF to LF.
        // Preserve a strict content checksum across newline conventions.
        $canonical = rtrim(str_replace(["\r\n", "\r"], "\n", $bytes), "\n")."\n";
        if (!hash_equals(self::EXPECTED_CANONICAL_SHA256, hash('sha256', $canonical))) {
            throw new RuntimeException('Registry seed refused: CSV content differs from approved source.');
        }

        $rows = $this->readAndValidateCsv($path);

        // Serialize possible concurrent deployments, then check table contents.
        // Both the insert and sequence restart occur in the same transaction.
        $db->transaction(function () use ($db, $rows): void {
            $db->statement('LOCK TABLE public.sle_fhe_registries IN ACCESS EXCLUSIVE MODE');

            $existingCount = (int) $db->table('sle_fhe_registries')->count();
            if ($existingCount !== 0) {
                // Do not overwrite / truncate any institutional registry records.
                if ($existingCount === self::EXPECTED_COUNT && $this->sameIdentities($db, $rows)) {
                    $this->command?->info('SLE-FHE registry already contains 1,903 matching identities; no changes made.');
                    return;
                }
                throw new RuntimeException('Registry seed refused: destination already contains differing records.');
            }

            foreach (array_chunk($rows, 200) as $chunk) {
                $db->table('sle_fhe_registries')->insert($chunk);
            }

            if ((int) $db->table('sle_fhe_registries')->count() !== self::EXPECTED_COUNT) {
                throw new RuntimeException('Registry seed aborted: post-insert row count mismatch.');
            }

            $sequence = $db->selectOne(
                "SELECT pg_get_serial_sequence('public.sle_fhe_registries', 'id') AS seq"
            )->seq ?? null;
            if (!is_string($sequence) || $sequence === '') {
                throw new RuntimeException('Registry seed aborted: cannot determine primary-key sequence.');
            }
            // pg_get_serial_sequence returns a database identifier; quote each part.
            $quoted = implode('.', array_map(
                static fn (string $part): string => '"'.str_replace('"', '""', $part).'"',
                explode('.', $sequence)
            ));
            $nextId = max(array_column($rows, 'id')) + 1;
            $db->statement("ALTER SEQUENCE {$quoted} RESTART WITH {$nextId}");

            $this->command?->info('SLE-FHE institutional registry imported: 1,903 records.');
        }, 1);
    }

    /** @return list<array<string,int|string>> */
    private function readAndValidateCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Cannot open private registry CSV.');
        }

        try {
            $header = fgetcsv($handle);
            if (!is_array($header)) {
                throw new RuntimeException('CSV header is missing.');
            }
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            if ($header !== self::COLUMNS) {
                throw new RuntimeException('CSV column names or order do not match the expected schema.');
            }

            $rows = [];
            $studentIds = [];
            $ids = [];
            $line = 1;

            while (($values = fgetcsv($handle)) !== false) {
                $line++;
                if ($values === [null]) {
                    continue;
                }
                if (count($values) !== count(self::COLUMNS)) {
                    throw new RuntimeException("CSV has an invalid column count at row {$line}.");
                }
                $row = array_combine(self::COLUMNS, $values);
                if ($row === false) {
                    throw new RuntimeException("CSV mapping failed at row {$line}.");
                }
                foreach ($row as $value) {
                    if (trim((string) $value) === '') {
                        throw new RuntimeException("CSV contains a blank required field at row {$line}.");
                    }
                }
                if (!ctype_digit($row['id']) || (int) $row['id'] < 1 ||
                    isset($ids[$row['id']])) {
                    throw new RuntimeException("CSV has a duplicate or invalid record ID at row {$line}.");
                }
                if (isset($studentIds[$row['student_id']])) {
                    throw new RuntimeException("CSV has a duplicate student ID at row {$line}.");
                }
                if (!ctype_digit($row['year_level']) ||
                    (int) $row['year_level'] < 1 || (int) $row['year_level'] > 5) {
                    throw new RuntimeException("CSV has an invalid year level at row {$line}.");
                }
                if (!in_array($row['is_verified'], ['0', '1'], true)) {
                    throw new RuntimeException("CSV has an invalid registry flag at row {$line}.");
                }
                $ids[$row['id']] = true;
                $studentIds[$row['student_id']] = true;
                $row['id'] = (int) $row['id'];
                $row['year_level'] = (int) $row['year_level'];
                $row['is_verified'] = (int) $row['is_verified'];
                $row['created_at'] = $this->dateTime($row['created_at'], $line);
                $row['updated_at'] = $this->dateTime($row['updated_at'], $line);
                $rows[] = $row;
            }
            if (count($rows) !== self::EXPECTED_COUNT) {
                throw new RuntimeException('CSV count is not the expected 1,903 records.');
            }
            return $rows;
        } finally {
            fclose($handle);
        }
    }

    private function dateTime(string $input, int $line): string
    {
        $date = DateTimeImmutable::createFromFormat('!d/m/Y H:i', $input);
        $errors = DateTimeImmutable::getLastErrors();
        if (!$date || ($errors !== false && ($errors['warning_count'] || $errors['error_count'])) ||
            $date->format('d/m/Y H:i') !== $input) {
            throw new RuntimeException("Invalid CSV timestamp at row {$line}.");
        }
        return $date->format('Y-m-d H:i:s');
    }

    /** @param list<array<string,int|string>> $rows */
    private function sameIdentities($db, array $rows): bool
    {
        $expected = [];
        foreach ($rows as $row) {
            $expected[$row['id']] = $row['student_id'];
        }
        foreach ($db->table('sle_fhe_registries')->select('id', 'student_id')->cursor() as $record) {
            if (!isset($expected[(int) $record->id]) ||
                $expected[(int) $record->id] !== $record->student_id) {
                return false;
            }
            unset($expected[(int) $record->id]);
        }
        return $expected === [];
    }
}
