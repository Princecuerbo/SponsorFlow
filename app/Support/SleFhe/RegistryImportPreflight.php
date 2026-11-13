<?php

namespace App\Support\SleFhe;

use App\Support\Spreadsheet\XlsxReader;
use RuntimeException;

/** Evidence inspection only. No models, database connections, or persistence. */
class RegistryImportPreflight
{
    public const SHEETS = ['DISTRICT 1' => 1, 'DISTRICT 2' => 2];

    // Phase A source-conflict holds. Neither a clean source nor a catalog match releases them.
    public const BLOCKED = [886 => '2025-3046', 1388 => '2023-2491'];

    public const MALFORMED_BSIT = [
        'Bachelor of Science in Information TechnologyNOLOGY',
        'Bachelor of Science in Information Technologynology',
    ];

    private const COLUMNS = [
        'B' => 'student_id', 'C' => 'sex', 'D' => 'civil_status', 'E' => 'purok',
        'F' => 'barangay', 'G' => 'municipality', 'H' => 'course',
        'I' => 'year_level', 'J' => 'campus',
    ];

    /** Exact source spellings observed in the institutional workbook, never substring matches. */
    private const CAMPUS_ALIASES = [
        'DAVAO ORIENTAL STATE UNIVERSITY-MAIN CAMPUS' => 'DOrSU Main Campus',
        'DAVAO ORIENTAL STATE UNIVERSITY - MAIN CAMPUS' => 'DOrSU Main Campus',
        'DAVAO ORIENTAL STATE UNIVERSITY' => 'DOrSU Main Campus',
        'DORSU-BAGANGA EXTENSION CAMPUS' => 'DOrSU Baganga Extension Campus',
        'DORSU-CATEEL EXTENTION CAMPUS' => 'DOrSU Cateel Extension Campus',
        'DORSU-TARRAGONA EXTENSION CAMPUS' => 'DOrSU Tarragona Extension Campus',
        'DAVAO ORIENTAL STATE UNIVERSITY BANAYBANAY EXTENSION CAMPUS' => 'DOrSU Banaybanay Extension Campus',
        'SAN ISIDRO CAMPUS' => 'DOrSU San Isidro Campus',
    ];

    public function readWorkbook(string $path): array
    {
        $resolved = realpath($path);
        if ($resolved === false || ! is_file($resolved) || ! is_readable($resolved)) {
            throw new RuntimeException('Source workbook is missing or unreadable. No fallback or database inspection performed.');
        }

        $hash = hash_file('sha256', $resolved);
        $reader = XlsxReader::open($resolved);
        $rows = [];
        $counts = [];
        foreach (self::SHEETS as $sheet => $district) {
            if (! in_array($sheet, $reader->sheetNames(), true)) {
                throw new RuntimeException("Required worksheet {$sheet} is missing or unsupported.");
            }
            $sheetRows = $reader->rows($sheet);
            $headers = ['C' => 'SEX', 'D' => 'CIVIL STATUS', 'E' => 'PUROK', 'F' => 'BARANGAY',
                'G' => 'MUNICIPALITY', 'H' => 'COURSE', 'I' => 'YEAR LEVEL', 'J' => 'NAME OF SCHOOL'];
            foreach ($headers as $column => $header) {
                if (self::key($sheetRows[2][$column] ?? null) !== self::key($header)) {
                    throw new RuntimeException("Invalid source layout: {$sheet}!{$column}2 must contain {$header}.");
                }
            }
            $counts[$sheet] = 0;
            foreach ($sheetRows as $number => $cells) {
                if ($number < 3 || ! array_filter($cells, fn ($value) => self::key($value) !== '')) {
                    continue;
                }
                $values = [];
                foreach (self::COLUMNS as $column => $field) {
                    $values[$field] = $cells[$column] ?? null;
                }
                $values['district'] = $district;
                $rows[] = ['sheet' => $sheet, 'row' => $number, 'values' => $values];
                $counts[$sheet]++;
            }
        }
        if ($rows === [] || $hash === false || $hash !== hash_file('sha256', $resolved)) {
            throw new RuntimeException('Source is empty or changed during inspection. Obtain stable source evidence.');
        }

        return ['source' => ['path' => $resolved, 'sha256' => $hash, 'bytes' => filesize($resolved),
            'sheet_rows' => $counts, 'phase_a_reference_rows' => ['DISTRICT 1' => 874, 'DISTRICT 2' => 1058]],
            'rows' => $rows];
    }

    /** Inputs are snapshots, never Eloquent objects. All original values are retained. */
    public function inspect(array $workbook, array $catalog, array $registry): array
    {
        if (empty($workbook['rows']) || empty($workbook['source']['path'])
            || ! preg_match('/^[a-f0-9]{64}$/', $workbook['source']['sha256'] ?? '') || $catalog === []) {
            throw new RuntimeException('Complete source evidence and a nonempty authoritative catalog are required.');
        }

        $aliases = [];
        $catalogById = [];
        foreach ($catalog as $program) {
            $id = $program['program_id'] ?? null;
            if (! is_numeric($id) || $id < 1 || self::key($program['name'] ?? null) === ''
                || self::key($program['code'] ?? null) === '' || isset($catalogById[$id])) {
                throw new RuntimeException('Invalid or duplicate catalog identity. No catalog row can be selected safely.');
            }
            $catalogById[$id] = $program;
            foreach (['name', 'code', 'short_name'] as $field) {
                $key = self::key($program[$field] ?? null);
                if ($key !== '') {
                    $aliases[$key][$id] = $id;
                }
            }
        }
        $collisions = array_map('array_values', array_filter($aliases, fn ($ids) => count($ids) > 1));
        $existingByStudent = [];
        foreach ($registry as $record) {
            $studentId = RegistryNormalizer::studentId($record['student_id'] ?? null);
            if ($studentId === null || ! isset($record['id'])) {
                throw new RuntimeException('Registry snapshot contains an invalid identity; resolve it before comparison.');
            }
            $existingByStudent[$studentId][] = $record;
        }

        $rows = [];
        $groups = [];
        $locations = [];
        foreach ($workbook['rows'] as $source) {
            if (! isset(self::SHEETS[$source['sheet'] ?? '']) || ($source['row'] ?? 0) < 3
                || ! isset($source['values']) || ! is_array($source['values'])) {
                throw new RuntimeException('A source row lacks its district, row number, or original values.');
            }
            $location = $source['sheet'].':'.$source['row'];
            if (isset($locations[$location])) {
                throw new RuntimeException('Duplicate source coordinates: '.$location);
            }
            $locations[$location] = true;
            $raw = $source['values'];
            $studentId = RegistryNormalizer::studentId($raw['student_id'] ?? null);
            $issues = [];
            if ($studentId === null) {
                $issues[] = 'INVALID_OR_MISSING_STUDENT_ID';
            }
            if (($raw['district'] ?? null) !== self::SHEETS[$source['sheet']]) {
                $issues[] = 'SOURCE_DISTRICT_MISMATCH';
            }
            $course = RegistryNormalizer::text($raw['course'] ?? null);
            $matches = array_values($aliases[self::key($course)] ?? []);
            $program = count($matches) === 1 ? $catalogById[$matches[0]] : null;
            if (in_array($course, self::MALFORMED_BSIT, true)) {
                $issues[] = 'KNOWN_MALFORMED_BSIT_PENDING_REVIEW';
                // Recognising a defect is not permission to map it, even if the catalog contains it.
                $program = null;
            } elseif ($course === null) {
                $issues[] = 'MISSING_PROGRAM';
            } elseif (count($matches) > 1) {
                $issues[] = 'AMBIGUOUS_CATALOG_MATCH';
            } elseif ($program === null) {
                $issues[] = in_array(self::key($course), ['bsba', 'bsed',
                    'bachelor of science in business administration', 'bachelor of secondary education'], true)
                    ? 'AMBIGUOUS_PROGRAM_REQUIRES_SPECIALIZATION' : 'UNKNOWN_PROGRAM';
            } elseif (! ($program['is_active'] ?? false)) {
                $issues[] = 'INACTIVE_CATALOG_PROGRAM';
            }
            $campus = self::campus($raw['campus'] ?? null);
            if ($campus === null) {
                $issues[] = 'INVALID_OR_UNKNOWN_CAMPUS';
            }
            $existing = $studentId === null ? [] : ($existingByStudent[$studentId] ?? []);
            if (count($existing) > 1) {
                $issues[] = 'DUPLICATE_REGISTRY_IDENTITY';
            }
            $blocked = in_array($studentId, self::BLOCKED, true);
            $comparisons = [];
            $observations = [];
            foreach ($existing as $record) {
                $blocked = $blocked || array_key_exists($record['id'], self::BLOCKED);
                $differences = [];
                $fields = [];
                foreach (array_merge(array_values(self::COLUMNS), ['district']) as $field) {
                    $fields[$field] = $this->compareField($field, $raw[$field] ?? null, $record[$field] ?? null, $aliases);
                    // Keep the original B1 raw differences, independent of their classification.
                    if (($raw[$field] ?? null) !== ($record[$field] ?? null)
                        && ! ($field === 'district' && (string) ($raw[$field] ?? '') === (string) ($record[$field] ?? ''))) {
                        $differences[$field] = ['existing' => $record[$field] ?? null, 'incoming' => $raw[$field] ?? null];
                    }
                }
                $unresolvedChanges = array_filter($fields, fn ($field) => ! $field['raw_exact']
                    && in_array($field['classification'], ['DIFFERENT_VALUE', 'UNKNOWN_VALUE'], true));
                if ($unresolvedChanges !== []) {
                    $issues[] = 'EXISTING_RECORD_OVERWRITE_REQUIRES_REVIEW';
                }
                $attributes = array_diff_key($fields, ['course' => true]);
                if (in_array('UNKNOWN_VALUE', array_column($attributes, 'classification'), true)) {
                    $issues[] = 'UNRECOGNIZED_EXISTING_OR_SOURCE_ATTRIBUTE';
                }
                if ($fields['course']['classification'] === 'UNKNOWN_VALUE') {
                    $issues[] = 'UNRESOLVED_EXISTING_OR_SOURCE_PROGRAM';
                }
                $storedMatches = array_values($aliases[self::key($record['course'] ?? null)] ?? []);
                if (isset($differences['course']) && count($storedMatches) === 1) {
                    if ($program !== null && (string) $storedMatches[0] === (string) $program['program_id']) {
                        $observations[] = 'CATALOG_EQUIVALENT_REPRESENTATION_CHANGE';
                    } else {
                        $issues[] = 'CATALOG_VALUE_REGRESSION_OR_CONFLICT';
                    }
                }
                $comparisons[] = ['registry_id' => $record['id'], 'original' => $record,
                    'row_sha256' => self::fingerprint($record), 'differences' => $differences,
                    'field_comparisons' => $fields,
                    'attribute_comparison' => self::worstComparison(array_column($attributes, 'classification'))];
            }
            if ($blocked) {
                $issues[] = 'BLOCKED_PHASE_A_SOURCE_CONFLICT';
            }
            $rows[] = ['sheet' => $source['sheet'], 'row' => $source['row'], 'student_id' => $studentId,
                'original' => $raw, 'source_row_sha256' => self::fingerprint($source),
                'catalog_match' => $program, 'recognized_campus' => $campus,
                'existing_records' => $comparisons, 'issues' => array_values(array_unique($issues)),
                'observations' => array_values(array_unique($observations)), 'duplicate_source_fields' => [],
                'attribute_comparison' => self::worstComparison(array_column($comparisons, 'attribute_comparison')),
                'ready_for_import' => false];
            if ($studentId !== null) {
                $groups[$studentId][] = array_key_last($rows);
            }
        }

        $duplicateGroups = [];
        foreach ($groups as $studentId => $indexes) {
            if (count($indexes) < 2) {
                continue;
            }
            $rawFields = $genuineFields = $unresolvedFields = $equivalentFields = [];
            $pairComparisons = [];
            // Compare all occurrences, without choosing an authoritative source row.
            foreach ($indexes as $position => $left) {
                foreach (array_slice($indexes, $position + 1) as $right) {
                    $pair = [];
                    foreach (array_merge(array_values(self::COLUMNS), ['district']) as $field) {
                        $comparison = $this->compareField($field, $rows[$left]['original'][$field] ?? null,
                            $rows[$right]['original'][$field] ?? null, $aliases);
                        if ($comparison['raw_exact']) {
                            continue;
                        }
                        $rawFields[$field] = true;
                        $pair[$field] = $comparison;
                        match ($comparison['classification']) {
                            'DIFFERENT_VALUE' => $genuineFields[$field] = true,
                            'UNKNOWN_VALUE' => $unresolvedFields[$field] = true,
                            default => $equivalentFields[$field] = true,
                        };
                    }
                    $pairComparisons[] = ['left' => ['sheet' => $rows[$left]['sheet'], 'row' => $rows[$left]['row']],
                        'right' => ['sheet' => $rows[$right]['sheet'], 'row' => $rows[$right]['row']], 'fields' => $pair];
                }
            }
            $conflictingFields = array_keys($genuineFields + $unresolvedFields);
            $duplicateGroups[] = ['student_id' => $studentId, 'conflicting_fields' => $conflictingFields,
                'raw_differing_fields' => array_keys($rawFields), 'genuine_conflicting_fields' => array_keys($genuineFields),
                'unresolved_fields' => array_keys($unresolvedFields), 'normalization_equivalent_fields' => array_keys($equivalentFields),
                'pair_comparisons' => $pairComparisons,
                'locations' => array_map(fn ($i) => ['sheet' => $rows[$i]['sheet'], 'row' => $rows[$i]['row']], $indexes)];
            foreach ($indexes as $i) {
                $rows[$i]['issues'][] = $conflictingFields === [] ? 'DUPLICATE_STUDENT_ID' : 'CONFLICTING_DUPLICATE_STUDENT_ID';
                $rows[$i]['duplicate_source_fields'] = array_fill_keys($conflictingFields, 'DUPLICATE_SOURCE_CONFLICT');
            }
        }

        $counts = ['total' => count($rows), 'valid' => 0, 'requires_review' => 0, 'blocked' => 0,
            'duplicate_rows' => 0, 'conflicting_rows' => 0, 'missing_program_rows' => 0];
        foreach ($rows as &$row) {
            $row['status'] = in_array('BLOCKED_PHASE_A_SOURCE_CONFLICT', $row['issues'], true)
                ? 'BLOCKED' : ($row['issues'] === [] ? 'VALID' : 'REQUIRES_REVIEW');
            $counts[strtolower($row['status'])]++;
            $counts['duplicate_rows'] += (int) (count($groups[$row['student_id']] ?? []) > 1);
            $counts['conflicting_rows'] += (int) in_array('CONFLICTING_DUPLICATE_STUDENT_ID', $row['issues'], true);
            $counts['missing_program_rows'] += (int) (bool) array_intersect($row['issues'], ['MISSING_PROGRAM', 'UNKNOWN_PROGRAM']);
        }
        unset($row);

        return ['report_version' => 'B1.1', 'mode' => 'READ_ONLY', 'writes_enabled' => false, 'import_authorized' => false,
            'ready_for_import' => false, 'approval_evidence_status' => 'NOT_EVALUATED',
            'source' => $workbook['source'], 'catalog_sha256' => self::fingerprint($catalog),
            'registry_sha256' => self::fingerprint($registry), 'catalog_collisions' => $collisions,
            'blocked_identities' => self::BLOCKED, 'summary' => $counts, 'duplicate_groups' => $duplicateGroups,
            'reconciliation_summary' => $this->summarizeComparisons($rows),
            'has_issues' => $counts['valid'] !== $counts['total'] || $collisions !== [],
            'review_policy' => 'VALID is a comparison result, never verification or import approval. '
                .'Proven normalization equivalence describes values only and does not authorize overwrites. '
                .'Program checks remain separate. Unknown program candidates are not approved catalog additions. '
                .'Fingerprints identify current evidence; freshness against prior approvals is not attested. '
                .'Approval provenance is not inferred from registry values; '
                .'Phase A proposals remain PENDING and both source-conflict holds remain BLOCKED.',
            'rows' => $rows];
    }

    /** One comparison always retains both raw values and names the exact rule used. */
    private function compareField(string $field, mixed $incoming, mixed $existing, array $aliases): array
    {
        if ($field === 'course') {
            // Academic equivalence requires the same unambiguous catalog identity, not text cleanup.
            $left = array_values($aliases[self::key($incoming)] ?? []);
            $right = array_values($aliases[self::key($existing)] ?? []);
            $malformed = in_array(RegistryNormalizer::text($incoming), self::MALFORMED_BSIT, true)
                || in_array(RegistryNormalizer::text($existing), self::MALFORMED_BSIT, true);
            $recognized = ! $malformed && count($left) === 1 && count($right) === 1;
            $normalizedIncoming = $recognized ? (string) $left[0] : null;
            $normalizedExisting = $recognized ? (string) $right[0] : null;
            $rule = 'AcademicProgram catalog name/code/short_name; case and whitespace only';
        } else {
            [$leftKnown, $normalizedIncoming, $rule] = $this->normalizeAttribute($field, $incoming);
            [$rightKnown, $normalizedExisting] = $this->normalizeAttribute($field, $existing);
            $recognized = $leftKnown && $rightKnown;
        }

        $classification = ! $recognized ? 'UNKNOWN_VALUE' : ($incoming === $existing ? 'EXACT_MATCH'
            : ($normalizedIncoming === $normalizedExisting ? 'NORMALIZATION_EQUIVALENT' : 'DIFFERENT_VALUE'));

        return ['incoming' => $incoming, 'existing' => $existing, 'raw_exact' => $incoming === $existing,
            'scope' => $field === 'course' ? 'ACADEMIC_PROGRAM' : 'ATTRIBUTE',
            'classification' => $classification, 'rule' => $rule,
            'compared_incoming' => $normalizedIncoming, 'compared_existing' => $normalizedExisting];
    }

    private function normalizeAttribute(string $field, mixed $value): array
    {
        // These are the former seeder's mapRow rules, with explicit safety limits on
        // closed vocabularies. Never use its permissive unknown-campus Main fallback.
        $normalized = match ($field) {
            'student_id' => RegistryNormalizer::studentId($value),
            'sex' => RegistryNormalizer::sex($value),
            'civil_status' => RegistryNormalizer::civilStatus($value),
            'municipality' => RegistryNormalizer::municipality($value),
            'campus' => self::campus($value),
            'year_level' => RegistryNormalizer::yearLevel($value),
            'district' => in_array((string) $value, ['1', '2'], true) ? (int) $value : null,
            default => RegistryNormalizer::text($value),
        };
        $recognized = match ($field) {
            'student_id', 'campus', 'district' => $normalized !== null,
            'sex' => in_array($normalized, ['Male', 'Female'], true),
            'civil_status' => in_array($normalized, ['Single', 'Married', 'Widowed'], true),
            // Free text and nullable fields have no closed vocabulary in the original importer.
            // A matching normalized municipality is not an attestation of geography/eligibility.
            default => true,
        };
        $rule = match ($field) {
            'campus' => 'RegistryImportPreflight exact documented campus aliases; no substring/fallback',
            'district' => 'Declared district 1/2 integer representation',
            'student_id' => 'RegistryNormalizer::studentId',
            'civil_status' => 'RegistryNormalizer::civilStatus (recognized values only)',
            'sex' => 'RegistryNormalizer::sex (recognized values only)',
            'municipality' => 'RegistryNormalizer::municipality (text/explicit aliases only)',
            'year_level' => 'RegistryNormalizer::yearLevel (whitespace only)',
            default => 'RegistryNormalizer::text (whitespace only; no case/abbreviation inference)',
        };

        return [$recognized, $normalized, $rule];
    }

    /** Conservative roll-up: one conflicting occurrence prevents an identity being counted equivalent. */
    private static function worstComparison(array $classifications): string
    {
        foreach (['DIFFERENT_VALUE', 'UNKNOWN_VALUE', 'NORMALIZATION_EQUIVALENT', 'EXACT_MATCH'] as $classification) {
            if (in_array($classification, $classifications, true)) {
                return $classification;
            }
        }

        return 'NO_REGISTRY_MATCH';
    }

    private function summarizeComparisons(array $rows): array
    {
        $classes = array_fill_keys(['EXACT_MATCH', 'NORMALIZATION_EQUIVALENT', 'DIFFERENT_VALUE', 'UNKNOWN_VALUE', 'NO_REGISTRY_MATCH'], 0);
        $sourceCounts = $classes;
        $identityCounts = $classes;
        $identityComparisons = [];
        $metrics = [];
        $programGroups = [];
        foreach ($rows as $row) {
            $sourceCounts[$row['attribute_comparison']]++;
            $ids = array_unique(array_column($row['existing_records'], 'registry_id'));
            foreach ($row['existing_records'] as $comparison) {
                $identityComparisons[$comparison['registry_id']][] = $comparison['attribute_comparison'];
            }
            $checks = [
                'raw_text_differences' => (bool) array_filter(array_column($row['existing_records'], 'differences')),
                'catalog_matched_program' => $row['catalog_match'] !== null,
                'missing_catalog_program_candidates' => in_array('UNKNOWN_PROGRAM', $row['issues'], true),
                'missing_program_value' => in_array('MISSING_PROGRAM', $row['issues'], true),
                'known_malformed_programs' => in_array('KNOWN_MALFORMED_BSIT_PENDING_REVIEW', $row['issues'], true),
                'ambiguous_programs' => (bool) array_intersect(['AMBIGUOUS_PROGRAM_REQUIRES_SPECIALIZATION', 'AMBIGUOUS_CATALOG_MATCH'], $row['issues']),
                'duplicate_identity' => (bool) array_intersect(['DUPLICATE_STUDENT_ID', 'CONFLICTING_DUPLICATE_STUDENT_ID'], $row['issues']),
                'duplicate_source_conflict' => $row['duplicate_source_fields'] !== [],
                'blocked_phase_a' => $row['status'] === 'BLOCKED',
                'requires_institutional_review' => $row['issues'] !== [],
            ];
            foreach ($checks as $category => $present) {
                $metrics[$category] ??= ['source_rows' => 0, 'registry_ids' => []];
                if ($present) {
                    $metrics[$category]['source_rows']++;
                    foreach ($ids as $id) {
                        $metrics[$category]['registry_ids'][$id] = true;
                    }
                }
            }
            foreach (['UNKNOWN_PROGRAM', 'MISSING_PROGRAM', 'KNOWN_MALFORMED_BSIT_PENDING_REVIEW',
                'AMBIGUOUS_PROGRAM_REQUIRES_SPECIALIZATION', 'AMBIGUOUS_CATALOG_MATCH'] as $issue) {
                if (! in_array($issue, $row['issues'], true)) {
                    continue;
                }
                // Exact raw grouping: do not manufacture canonical names or merge guessed aliases.
                $value = $row['original']['course'] ?? null;
                $key = json_encode([$issue, $value], JSON_THROW_ON_ERROR);
                $programGroups[$key] ??= ['classification' => $issue, 'source_value' => $value, 'source_rows' => 0, 'registry_ids' => []];
                $programGroups[$key]['source_rows']++;
                foreach ($ids as $id) {
                    $programGroups[$key]['registry_ids'][$id] = true;
                }
            }
        }
        foreach ($identityComparisons as $comparisons) {
            $identityCounts[self::worstComparison($comparisons)]++;
        }
        $countIds = static function (array $item): array {
            $item['distinct_registry_identities'] = count($item['registry_ids']);
            unset($item['registry_ids']);

            return $item;
        };

        return ['attributes_excluding_academic_program' => ['source_rows' => $sourceCounts, 'distinct_registry_identities' => $identityCounts],
            'metrics' => array_map($countIds, $metrics), 'program_review_groups' => array_values(array_map($countIds, $programGroups)),
            'counting_policy' => 'Attribute classes are exclusive and identities use their worst comparison across every occurrence. '
                .'Other metrics overlap and count each source row and matched registry ID once per category. '
                .'Unmatched/invalid identities have no registry ID. Missing catalog candidates include unresolved spellings; '
                .'they are not confirmed new programs. Normalization equivalence never establishes approval or readiness.'];
    }

    public static function fingerprint(array $value): string
    {
        // Stable sorted-key JSON for B1 snapshots; not the Phase A manifest fingerprint format.
        $sort = function (array $items) use (&$sort): array {
            if (! array_is_list($items)) {
                ksort($items);
            }
            foreach ($items as &$item) {
                if (is_array($item)) {
                    $item = $sort($item);
                }
            }
            return $items;
        };

        return hash('sha256', json_encode($sort($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    private static function key(?string $value): string
    {
        return mb_strtolower(RegistryNormalizer::text($value) ?? '', 'UTF-8');
    }

    private static function campus(?string $value): ?string
    {
        foreach (RegistryNormalizer::CANONICAL_CAMPUSES as $campus) {
            if (self::key($value) === self::key($campus)) {
                return $campus;
            }
        }

        return self::CAMPUS_ALIASES[strtoupper(RegistryNormalizer::text($value) ?? '')] ?? null;
    }
}
