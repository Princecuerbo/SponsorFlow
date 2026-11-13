<?php

namespace Tests\Support;

use App\Support\Corrections\CorrectionEvidence as E;
use App\Support\Corrections\CorrectionStore;
use Closure;
use RuntimeException;
use Throwable;

/** Synthetic fixtures only; never reads institutional data or application configuration. */
final class CorrectionFixtures
{
    public array $policy;
    private array $keys;
    public string $evidence;
    public string $workbook;
    private string $directory;

    public function __construct()
    {
        $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sponsorflow-d1-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
        $this->evidence = $this->directory.DIRECTORY_SEPARATOR.'synthetic-evidence.txt';
        file_put_contents($this->evidence, 'SYNTHETIC TEST EVIDENCE. NOT INSTITUTIONAL APPROVAL.');
        $options = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
        if (is_file('C:/xampp/php/extras/ssl/openssl.cnf')) {
            $options['config'] = 'C:/xampp/php/extras/ssl/openssl.cnf';
        }
        foreach (['approval', 'execution'] as $role) {
            $this->keys[$role] = openssl_pkey_new($options);
            E::require($this->keys[$role] !== false, 'Cannot generate memory-only test keys.');
            $this->policy[$role.'_keys'][$role] = ['authority' => 'Synthetic '.$role,
                'pem' => openssl_pkey_get_details($this->keys[$role])['key']];
        }
        $this->policy += ['production_apply_enabled' => false, 'protected_databases' => ['sponsorflow']];
        $this->workbook = $this->directory.DIRECTORY_SEPARATOR.'synthetic-source.xlsx';
        $headers = ['B' => 'STUDENT ID', 'C' => 'SEX', 'D' => 'CIVIL STATUS', 'E' => 'PUROK',
            'F' => 'BARANGAY', 'G' => 'MUNICIPALITY', 'H' => 'COURSE', 'I' => 'YEAR LEVEL', 'J' => 'NAME OF SCHOOL'];
        MiniXlsx::write($this->workbook, [
            'DISTRICT 1' => [[], $headers, ['B' => '2099-9001', 'H' => 'Malformed synthetic course'],
                ['B' => '2099-9002', 'H' => 'Malformed synthetic course']],
            'DISTRICT 2' => [[], $headers],
        ]);
    }

    public function close(): void
    {
        foreach ([$this->evidence, $this->workbook] as $file) {
            if (is_file($file) && dirname(realpath($file)) === realpath($this->directory)) {
                unlink($file);
            }
        }
        rmdir($this->directory);
    }

    public static function identity(): array
    {
        return ['account' => 'isolated_fixture@localhost', 'database' => 'sponsorflow_test',
            'host' => '127.0.0.1', 'port' => '3306', 'server_hostname' => 'synthetic-server', 'server_id' => '1'];
    }

    public static function program(): array
    {
        return ['program_id' => 12, 'code' => 'TEST-EXISTING', 'name' => 'Canonical synthetic course', 'short_name' => 'Synthetic Existing',
            'is_board_program' => 0, 'is_undergraduate' => 1, 'is_active' => 1,
            'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00'];
    }

    public static function row(int $id = 42): array
    {
        return ['id' => $id, 'student_id' => $id === 42 ? '2099-9001' : '2099-9002', 'sex' => 'Female',
            'civil_status' => 'Single', 'purok' => null, 'barangay' => 'Synthetic', 'municipality' => 'Synthetic',
            'course' => 'Malformed synthetic course', 'year_level' => '1', 'campus' => 'Synthetic', 'district' => 1,
            'is_verified' => 0, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-02 00:00:00'];
    }

    public function manifest(string $kind = 'catalog', ?array $catalog = null): array
    {
        $item = ['reference' => 'TEST-1', 'approval_status' => 'APPROVED', 'approval_reference' => 'SYNTHETIC-APPROVAL'];
        if ($kind === 'catalog') {
            $item += ['code' => 'TEST-NEW', 'name' => 'Synthetic New Degree', 'short_name' => null,
                'short_name_null_approved' => true, 'is_undergraduate' => true, 'is_active' => true, 'is_board_program' => false];
        } else {
            $row = self::row();
            $item += ['registry_id' => 42, 'student_id' => $row['student_id'], 'original_course' => $row['course'],
                'replacement_course' => self::program()['name'], 'catalog_program_id' => 12,
                'original_row_sha256' => E::rowHash($row), 'original_updated_at' => $row['updated_at'],
                'source_sheet' => 'DISTRICT 1', 'source_row' => 3];
        }

        return ['version' => 1, 'reference' => 'SYNTHETIC-MANIFEST', 'kind' => $kind, 'status' => 'APPROVED',
            'target' => self::identity(), 'catalog_sha256' => E::hash($catalog ?? [self::program()]),
            'source' => ['path' => $this->workbook, 'sha256' => hash_file('sha256', $this->workbook)], 'items' => [$item]];
    }

    public function sign(array $payload, string $role): string
    {
        openssl_sign(E::json($payload), $signature, $this->keys[$role], OPENSSL_ALGO_SHA256);

        return E::json(['key_id' => $role, 'payload' => $payload, 'signature' => base64_encode($signature)]);
    }

    public function package(array $manifest, array $authorizationOverrides = []): array
    {
        $bytes = E::json($manifest);
        $evidence = ['path' => $this->evidence, 'sha256' => hash_file('sha256', $this->evidence)];
        $common = ['manifest_sha256' => hash('sha256', $bytes),
            'issued_at' => gmdate('Y-m-d\TH:i:s\Z', time() - 60), 'expires_at' => gmdate('Y-m-d\TH:i:s\Z', time() + 3600),
            'evidence' => [$evidence]];
        $approval = $this->sign($common + ['purpose' => 'institutional_approval', 'authority' => 'Synthetic approval',
            'reference' => 'SYNTHETIC-APPROVAL', 'items' => array_column($manifest['items'], 'reference')], 'approval');
        $proof = $evidence + ['reference' => 'SYNTHETIC-PROOF', 'verified' => true, 'verified_by' => 'independent-fixture-reviewer',
            'verified_at' => gmdate('Y-m-d\TH:i:s\Z', time() - 60)];
        $execution = array_replace($common + ['purpose' => 'execution_authorization', 'authority' => 'Synthetic execution',
            'reference' => 'SYNTHETIC-EXECUTION', 'approval_sha256' => hash('sha256', $approval), 'operator' => 'fixture-operator',
            'target' => $manifest['target'], 'operation' => $manifest['kind'] === 'rollback_registry' ? 'rollback' : 'apply',
            'exclusive_maintenance' => true, 'backup' => $proof, 'isolated_test' => $proof + ['database' => 'sponsorflow_test']], $authorizationOverrides);

        return [$bytes, $approval, $this->sign($execution, 'execution')];
    }
}

/** Transactional memory substitute; all unexpected state changes can be inspected by tests. */
final class MemoryCorrectionStore implements CorrectionStore
{
    public array $programs;
    public array $rows;
    public array $receipts = [];
    public array $protectedRecords = ['profiles' => ['untouched'], 'verification' => ['untouched'], 'sponsorships' => ['untouched']];
    public int $writes = 0;
    public int $failAfter = PHP_INT_MAX;
    public bool $readOnly = true;
    public array $target;

    public function __construct()
    {
        $this->programs = [CorrectionFixtures::program()];
        $this->rows = [42 => CorrectionFixtures::row(), 43 => CorrectionFixtures::row(43)];
        $this->target = CorrectionFixtures::identity();
    }

    public function transaction(bool $write, Closure $work): mixed
    {
        $before = serialize([$this->programs, $this->rows, $this->receipts, $this->writes, $this->protectedRecords]);
        $this->readOnly = ! $write;
        try {
            return $work();
        } catch (Throwable $e) {
            [$this->programs, $this->rows, $this->receipts, $this->writes, $this->protectedRecords] = unserialize($before);
            throw $e;
        } finally {
            $this->readOnly = true;
        }
    }

    public function identity(): array { return $this->target; }
    public function catalog(): array { return $this->programs; }
    public function registry(int $id): ?array { return $this->rows[$id] ?? null; }
    public function receipt(string $manifestHash): ?array { return $this->receipts[$manifestHash] ?? null; }
    public function sameText(string $left, string $right): bool
    {
        // A synthetic accent-insensitive collation fixture; production asks MariaDB.
        $normalize = fn ($s) => mb_strtolower(trim(preg_replace('/\s+/u', ' ', strtr($s, ['É' => 'E', 'é' => 'e']))));

        return $normalize($left) === $normalize($right);
    }
    public function collisions(array $item): array
    {
        return array_values(array_filter($this->programs, function ($row) use ($item): bool {
            foreach (['code', 'name', 'short_name'] as $a) {
                foreach (['code', 'name', 'short_name'] as $b) {
                    if ($row[$a] !== null && $item[$b] !== null && $this->sameText($row[$a], $item[$b])) {
                        return true;
                    }
                }
            }

            return false;
        }));
    }
    private function write(): void
    {
        E::require(! $this->readOnly, 'Memory store cannot write in dry-run.');
        if (++$this->writes > $this->failAfter) {
            throw new RuntimeException('Synthetic transaction failure');
        }
    }
    public function insertProgram(array $item): array
    {
        $this->write();
        $row = array_intersect_key($item, array_flip(['code', 'name', 'short_name', 'is_board_program', 'is_undergraduate', 'is_active']))
            + ['program_id' => 700 + count($this->programs), 'created_at' => '2026-01-03 00:00:00', 'updated_at' => '2026-01-03 00:00:00'];
        $this->programs[] = $row;

        return $row;
    }
    public function changeCourse(array $before, string $course, ?string $timestamp = null, bool $restoreTimestamp = false): array
    {
        $this->write();
        $this->rows[$before['id']]['course'] = $course;
        $this->rows[$before['id']]['updated_at'] = $restoreTimestamp ? $timestamp : '2026-01-03 00:00:00';

        return $this->rows[$before['id']];
    }
    public function record(array $metadata, array $changes): void
    {
        $this->write();
        $this->receipts[$metadata['manifest_sha256']] = ['metadata' => $metadata, 'changes' => $changes];
    }
}
