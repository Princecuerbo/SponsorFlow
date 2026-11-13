<?php

namespace App\Support\Corrections;

use App\Support\SleFhe\RegistryImportPreflight;
use App\Support\Spreadsheet\XlsxReader;
use DateTimeImmutable;
use RuntimeException;

/** Pure validation, hashing and file reads. No database or application bootstrap. */
final class CorrectionEvidence
{
    public const REGISTRY_FIELDS = ['id', 'student_id', 'sex', 'civil_status', 'purok', 'barangay',
        'municipality', 'course', 'year_level', 'campus', 'district', 'is_verified', 'created_at', 'updated_at'];

    public static function require(bool $condition, string $message): void
    {
        if (! $condition) {
            throw new RuntimeException($message);
        }
    }

    public static function json(array $data): string
    {
        $sort = function (array $value) use (&$sort): array {
            if (! array_is_list($value)) {
                ksort($value, SORT_STRING);
            }
            foreach ($value as &$item) {
                if (is_array($item)) {
                    $item = $sort($item);
                }
            }

            return $value;
        };

        return json_encode($sort($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public static function hash(array $data): string
    {
        return hash('sha256', self::json($data));
    }

    /** Phase A registry-row-json-v1: ordered fields, strings for non-null values. */
    public static function rowHash(array $row): string
    {
        $ordered = [];
        foreach (self::REGISTRY_FIELDS as $field) {
            self::require(array_key_exists($field, $row), 'Incomplete registry before/after image.');
            $ordered[$field] = $row[$field] === null ? null : (string) $row[$field];
        }

        return hash('sha256', json_encode($ordered, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    public static function text(mixed $value, int $max = 255): bool
    {
        return is_string($value) && trim($value) !== '' && trim($value) === $value
            && mb_strlen($value) <= $max && ! preg_match('/[\x00-\x1f]/', $value)
            && ! in_array(strtoupper($value), ['PENDING', 'UNKNOWN', 'TBD'], true);
    }

    public static function file(string $path, ?string $expected = null): string
    {
        $resolved = realpath($path);
        self::require($resolved !== false && is_file($resolved) && is_readable($resolved), 'Evidence file missing or unreadable.');
        self::require(filesize($resolved) <= 128 * 1024 * 1024, 'Evidence file exceeds the inspection size limit.');
        $bytes = file_get_contents($resolved);
        self::require($bytes !== false && hash('sha256', $bytes) === hash_file('sha256', $resolved), 'Evidence changed during inspection.');
        if ($expected !== null) {
            self::require(preg_match('/^[a-f0-9]{64}$/', $expected) === 1
                && hash_equals($expected, hash('sha256', $bytes)), 'Evidence checksum mismatch.');
        }

        return $bytes;
    }

    public static function document(string $bytes): array
    {
        self::require(strlen($bytes) <= 1024 * 1024, 'Approval document exceeds the size limit.');
        $data = json_decode($bytes, true, 32, JSON_THROW_ON_ERROR);
        self::require(is_array($data) && ! array_is_list($data), 'Expected a JSON object.');
        // Canonical input rules also reject duplicate JSON keys and ambiguous encodings.
        self::require(trim($bytes) === self::json($data), 'Use canonical sorted-key JSON; duplicate keys or noncanonical encoding are not accepted.');

        return $data;
    }

    public static function manifest(string $bytes): array
    {
        $m = self::document($bytes);
        self::require(($m['version'] ?? null) === 1 && ($m['status'] ?? null) === 'APPROVED', 'Manifest is not institutionally approved.');
        self::require(in_array($m['kind'] ?? null, ['catalog', 'registry', 'rollback_registry'], true), 'Unsupported correction kind.');
        self::require(self::text($m['reference'] ?? null, 100), 'Manifest reference required.');
        self::require(preg_match('/^[a-f0-9]{64}$/', $m['catalog_sha256'] ?? '') === 1, 'Approved catalog snapshot checksum required.');
        foreach (['database', 'host', 'port', 'server_hostname', 'server_id', 'account'] as $field) {
            self::require(self::text($m['target'][$field] ?? null), 'Complete explicit target identity required.');
        }
        self::require(preg_match('/^[a-zA-Z0-9_]+$/D', $m['target']['database']) === 1
            && preg_match('/^[a-zA-Z0-9.:-]+$/D', $m['target']['host']) === 1
            && ctype_digit($m['target']['port']) && (int) $m['target']['port'] > 0
            && (int) $m['target']['port'] <= 65535, 'Invalid explicit database connection identity.');
        $items = $m['items'] ?? null;
        self::require(is_array($items) && array_is_list($items) && count($items) > 0 && count($items) <= 100, 'Select one atomic manifest of 1 to 100 approved entries.');
        $refs = $ids = [];
        foreach ($items as $item) {
            self::require(is_array($item) && self::text($item['reference'] ?? null, 100)
                && ! isset($refs[$item['reference']]), 'Missing or duplicate item reference.');
            $refs[$item['reference']] = true;
            self::require(($item['approval_status'] ?? null) === 'APPROVED'
                && self::text($item['approval_reference'] ?? null), 'Every selected item requires explicit approval; no skipped PENDING rows.');
            if ($m['kind'] === 'catalog') {
                foreach (['code' => 50, 'name' => 255] as $field => $max) {
                    self::require(self::text($item[$field] ?? null, $max), 'Approved catalog code and exact name required.');
                }
                self::require(array_key_exists('short_name', $item)
                    && ($item['short_name'] === null || self::text($item['short_name']))
                    && ($item['short_name'] !== null || ($item['short_name_null_approved'] ?? false) === true), 'Short name or explicitly approved NULL required.');
                foreach (['is_active', 'is_undergraduate', 'is_board_program'] as $flag) {
                    self::require(is_bool($item[$flag] ?? null), 'Catalog flags require explicit approved booleans.');
                }
                self::require(! array_key_exists('program_id', $item), 'New IDs must be database-assigned.');
            } else {
                self::require(is_int($item['registry_id'] ?? null) && $item['registry_id'] > 0
                    && ! isset($ids[$item['registry_id']]), 'Missing or duplicate registry identity.');
                $ids[$item['registry_id']] = true;
                self::require(! isset(RegistryImportPreflight::BLOCKED[$item['registry_id']])
                    && ! in_array($item['student_id'] ?? null, RegistryImportPreflight::BLOCKED, true), 'Blocked source-conflict identity cannot execute.');
                self::require(is_string($item['student_id'] ?? null)
                    && preg_match('/^\d{4}-\d{3,6}$/D', $item['student_id']) === 1, 'Exact approved Student ID required.');
                if ($m['kind'] === 'registry') {
                    self::require(self::text($item['original_course'] ?? null, 191)
                        && self::text($item['replacement_course'] ?? null, 191)
                        && $item['original_course'] !== $item['replacement_course'], 'Exact original and replacement course required.');
                    self::require(is_int($item['catalog_program_id'] ?? null) && $item['catalog_program_id'] > 0, 'Existing canonical program ID required.');
                    self::require(preg_match('/^[a-f0-9]{64}$/', $item['original_row_sha256'] ?? '') === 1
                        && array_key_exists('original_updated_at', $item), 'Phase A row fingerprint and timestamp required.');
                    self::require($item['original_updated_at'] === null
                        || (is_string($item['original_updated_at']) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D', $item['original_updated_at']) === 1),
                        'Exact original timestamp or explicit NULL required.');
                    self::require(in_array($item['source_sheet'] ?? null, ['DISTRICT 1', 'DISTRICT 2'], true)
                        && is_int($item['source_row'] ?? null) && $item['source_row'] >= 3, 'Source sheet and row required.');
                }
            }
        }
        if ($m['kind'] === 'registry') {
            self::require(self::text($m['source']['path'] ?? null)
                && preg_match('/^[a-f0-9]{64}$/', $m['source']['sha256'] ?? '') === 1, 'Source workbook identity required.');
        }
        if ($m['kind'] === 'rollback_registry') {
            self::require(preg_match('/^[a-f0-9]{64}$/', $m['original_manifest_sha256'] ?? '') === 1, 'Original committed registry manifest checksum required.');
        }

        return $m;
    }

    public static function signed(string $bytes, array $trustedKeys, string $purpose, string $manifestHash, int $now): array
    {
        $envelope = self::document($bytes);
        $key = $trustedKeys[$envelope['key_id'] ?? ''] ?? null;
        self::require(is_array($key) && self::text($key['authority'] ?? null), 'Signer is not an enrolled independent authority.');
        $public = openssl_pkey_get_public($key['pem'] ?? '');
        self::require($public !== false, 'Invalid enrolled public key.');
        $details = openssl_pkey_get_details($public);
        self::require($details['type'] === OPENSSL_KEYTYPE_RSA && $details['bits'] >= 2048, 'RSA public key of at least 2048 bits required.');
        $payload = $envelope['payload'] ?? null;
        self::require(is_array($payload), 'Signed approval payload missing.');
        $signature = base64_decode($envelope['signature'] ?? '', true);
        self::require($signature !== false && openssl_verify(self::json($payload), $signature, $public, OPENSSL_ALGO_SHA256) === 1, 'Approval signature invalid.');
        self::require(($payload['purpose'] ?? null) === $purpose
            && ($payload['manifest_sha256'] ?? null) === $manifestHash
            && ($payload['authority'] ?? null) === $key['authority']
            && self::text($payload['reference'] ?? null), 'Approval scope, authority or checksum mismatch.');
        foreach (['issued_at', 'expires_at'] as $date) {
            self::require(is_string($payload[$date] ?? null)
                && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z|[+-]\d{2}:\d{2})$/', $payload[$date]) === 1, 'Explicit approval date/time and expiry required.');
            self::require((new DateTimeImmutable($payload[$date]))->format('Y-m-d\TH:i:sP') === str_replace('Z', '+00:00', $payload[$date]), 'Invalid calendar date in approval evidence.');
        }
        self::require((new DateTimeImmutable($payload['issued_at']))->getTimestamp() <= $now
            && (new DateTimeImmutable($payload['expires_at']))->getTimestamp() > $now, 'Approval evidence is future-dated or expired.');
        self::require(is_array($payload['evidence'] ?? null) && count($payload['evidence']) > 0, 'Independent written institutional evidence required.');
        foreach ($payload['evidence'] as $evidence) {
            self::require(($evidence['sha256'] ?? '') !== $manifestHash, 'The manifest itself is not independent institutional evidence.');
            self::file($evidence['path'] ?? '', $evidence['sha256'] ?? '');
        }
        $payload['_key_hash'] = hash('sha256', $details['key']);

        return $payload;
    }

    /** Review files never become execution input merely by editing status cells. */
    public static function review(string $path): array
    {
        self::file($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $statuses = [];
        if ($extension === 'csv') {
            $stream = fopen($path, 'rb');
            try {
                $headers = fgetcsv($stream);
                self::require(is_array($headers), 'Empty review CSV.');
                $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
                $column = array_search('approval_status', $headers, true);
                self::require($column !== false, 'Review approval_status column missing.');
                while (($row = fgetcsv($stream)) !== false) {
                    $statuses[] = $row[$column] ?? 'MISSING';
                }
            } finally {
                fclose($stream);
            }
        } elseif ($extension === 'xlsx') {
            $reader = XlsxReader::open($path);
            foreach ($reader->rows('Approval Manifest') as $number => $row) {
                if ($number >= 5 && isset($row['A'])) {
                    $statuses[] = $row['J'] ?? 'MISSING';
                }
            }
        } else {
            throw new RuntimeException('Expected a review CSV or approval XLSX.');
        }

        return ['mode' => 'REVIEW_ONLY', 'rows' => count($statuses), 'statuses' => array_count_values($statuses),
            'executable_changes' => 0, 'blocker' => 'Review files are not signed execution manifests; independent approval and execution authorization required.'];
    }
}
