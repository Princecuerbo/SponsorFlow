<?php

namespace App\Support\Corrections;

use Closure;

/** Only a validated whole manifest can reach a write transaction. No partial approvals. */
final class CorrectionExecutor
{
    public function __construct(private CorrectionApproval $approval, private Closure $storeFactory) {}

    public function run(string $manifest, string $approval, ?string $authorization, string $operator, string $target, bool $apply = false): array
    {
        $context = $this->approval->check($manifest, $approval, $authorization, $operator, $target, $apply);
        /** @var CorrectionStore $store */
        $store = ($this->storeFactory)($context);

        return $store->transaction($apply, function () use ($store, $context, $apply, $manifest, $approval, $authorization, $operator, $target): array {
            $m = $context['manifest'];
            CorrectionEvidence::require($store->identity() === $m['target'], 'Effective database/server/account identity mismatch.');
            $catalog = $store->catalog(); // PDO implementation locks the complete indexed range for APPLY.
            $receipt = $store->receipt($context['manifest_sha256']);
            if ($receipt !== null) {
                CorrectionEvidence::require($receipt['metadata']['catalog_after_sha256'] === CorrectionEvidence::hash($catalog), 'Catalog changed since the committed operation.');
                foreach ($receipt['changes'] as $change) {
                    $current = $change['table'] === 'academic_programs'
                        ? current(array_filter($catalog, fn ($row) => (int) $row['program_id'] === (int) $change['after']['program_id']))
                        : $store->registry((int) $change['after']['id']);
                    CorrectionEvidence::require(is_array($current) && CorrectionEvidence::hash($current) === CorrectionEvidence::hash($change['after']), 'Committed post-image has changed; repeat execution refused.');
                }

                return ['mode' => $apply ? 'APPLY' : 'DRY_RUN', 'status' => 'ALREADY_APPLIED',
                    'changes' => [], 'writes' => 0, 'execution_blockers' => $context['execution_blockers']];
            }
            CorrectionEvidence::require(CorrectionEvidence::hash($catalog) === $m['catalog_sha256'], 'Approved catalog snapshot is stale.');
            $plan = [];
            if ($m['kind'] === 'catalog') {
                $selected = [];
                foreach ($m['items'] as $item) {
                    // Cross-field collisions are intentionally conservative, including inactive rows.
                    foreach (['code', 'name', 'short_name'] as $field) {
                        if ($item[$field] === null) {
                            continue;
                        }
                        $key = mb_strtolower(preg_replace('/\s+/u', ' ', trim($item[$field])));
                        foreach ($selected as $previous) {
                            CorrectionEvidence::require($previous['reference'] === $item['reference']
                                || ($previous['key'] !== $key && ! $store->sameText($previous['text'], $item[$field])),
                                'Selected catalog entries collide with each other.');
                        }
                        $selected[] = ['key' => $key, 'text' => $item[$field], 'reference' => $item['reference']];
                    }
                    $collisions = $store->collisions($item);
                    if ($collisions !== []) {
                        CorrectionEvidence::require(count($collisions) === 1 && self::sameProgram($collisions[0], $item), 'Catalog collision or conflicting definition; existing rows will not be updated.');
                        continue;
                    }
                    $plan[] = ['table' => 'academic_programs', 'item' => $item, 'before' => null];
                }
            } elseif ($m['kind'] === 'registry') {
                $items = $m['items'];
                usort($items, fn ($a, $b) => $a['registry_id'] <=> $b['registry_id']);
                foreach ($items as $item) {
                    $before = $store->registry($item['registry_id']);
                    CorrectionEvidence::require($before !== null && (string) $before['student_id'] === $item['student_id']
                        && $before['course'] === $item['original_course']
                        && $before['updated_at'] === $item['original_updated_at']
                        && CorrectionEvidence::rowHash($before) === $item['original_row_sha256'], 'Registry identity/course/fingerprint/timestamp is stale.');
                    $program = current(array_filter($catalog, fn ($row) => (int) $row['program_id'] === $item['catalog_program_id']));
                    CorrectionEvidence::require($program !== false && $program['name'] === $item['replacement_course'], 'Replacement must exactly match the approved existing canonical program.');
                    $plan[] = ['table' => 'sle_fhe_registries', 'item' => $item, 'before' => $before];
                }
            } else {
                $original = $store->receipt($m['original_manifest_sha256']);
                CorrectionEvidence::require($original !== null && $original['metadata']['kind'] === 'registry', 'Rollback requires a committed registry correction receipt. Catalog deletion is never automatic.');
                $ids = array_column($m['items'], 'registry_id');
                $priorIds = array_map(fn ($change) => (int) $change['after']['id'], $original['changes']);
                sort($ids); sort($priorIds);
                CorrectionEvidence::require($ids === $priorIds, 'Rollback must cover the exact original atomic registry operation.');
                foreach ($original['changes'] as $change) {
                    $row = $store->registry((int) $change['after']['id']);
                    $item = current(array_filter($m['items'], fn ($entry) => $entry['registry_id'] === (int) $change['after']['id']));
                    CorrectionEvidence::require($row !== null && $row['student_id'] === $item['student_id']
                        && CorrectionEvidence::hash($row) === CorrectionEvidence::hash($change['after']), 'Intervening changes prevent compensating rollback.');
                    $plan[] = ['table' => 'sle_fhe_registries', 'item' => $item, 'before' => $row, 'restore' => $change['before']];
                }
            }
            if (! $apply) {
                return ['mode' => 'DRY_RUN', 'status' => $plan === [] ? 'NO_CHANGES' : 'REVIEW_REQUIRED',
                    'writes' => 0, 'execution_blockers' => $context['execution_blockers'],
                    'changes' => array_map(fn ($entry) => [
                        'reference' => $entry['item']['reference'], 'table' => $entry['table'],
                        'registry_id' => $entry['item']['registry_id'] ?? null,
                        'before_course' => $entry['before']['course'] ?? null,
                        'proposed' => $entry['restore']['course'] ?? $entry['item']['replacement_course'] ?? array_intersect_key($entry['item'], array_flip(['code', 'name', 'short_name', 'is_active', 'is_undergraduate', 'is_board_program'])),
                    ], $plan)];
            }
            $changes = [];
            // Recheck signatures, expiries and external evidence while database locks are held.
            $this->approval->check($manifest, $approval, $authorization, $operator, $target, true);
            // All members passed before the first write. Any failure rolls back the whole transaction.
            foreach ($plan as $entry) {
                $after = $entry['table'] === 'academic_programs'
                    ? $store->insertProgram($entry['item'])
                    : $store->changeCourse($entry['before'], $entry['restore']['course'] ?? $entry['item']['replacement_course'],
                        array_key_exists('restore', $entry) ? $entry['restore']['updated_at'] : null,
                        array_key_exists('restore', $entry));
                if ($entry['table'] === 'academic_programs') {
                    CorrectionEvidence::require(self::sameProgram($after, $entry['item']), 'Inserted catalog attributes differ from approval; abort.');
                } else {
                    CorrectionEvidence::require($after['course'] === ($entry['restore']['course'] ?? $entry['item']['replacement_course']),
                        'Stored course differs from the exact approved value; abort.');
                }
                if ($entry['before'] !== null) {
                    foreach (CorrectionEvidence::REGISTRY_FIELDS as $field) {
                        if (! in_array($field, ['course', 'updated_at'], true)) {
                            CorrectionEvidence::require($after[$field] === $entry['before'][$field], 'Protected registry attribute changed; abort.');
                        }
                    }
                }
                $changes[] = ['table' => $entry['table'], 'reference' => $entry['item']['reference'],
                    'before' => $entry['before'], 'after' => $after];
            }
            if ($changes !== []) {
                $store->record(['manifest_sha256' => $context['manifest_sha256'], 'kind' => $m['kind'],
                    'manifest_reference' => $m['reference'], 'target' => $m['target'], 'operator' => $context['operator'],
                    'approval' => $context['approval'], 'authorization' => $context['authorization'],
                    'catalog_before_sha256' => $m['catalog_sha256'],
                    'catalog_after_sha256' => CorrectionEvidence::hash($store->catalog()),
                    'original_manifest_sha256' => $m['original_manifest_sha256'] ?? null], $changes);
            }

            return ['mode' => 'APPLY', 'status' => $changes === [] ? 'NO_CHANGES' : 'COMMITTED',
                'writes' => count($changes), 'manifest_sha256' => $context['manifest_sha256']];
        });
    }

    private static function sameProgram(array $row, array $item): bool
    {
        foreach (['code', 'name', 'short_name'] as $field) {
            if ($row[$field] !== $item[$field]) {
                return false;
            }
        }
        foreach (['is_active', 'is_undergraduate', 'is_board_program'] as $field) {
            if ((bool) $row[$field] !== $item[$field]) {
                return false;
            }
        }

        return true;
    }
}
