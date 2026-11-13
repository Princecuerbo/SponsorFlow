<?php

namespace App\Support\Corrections;

use App\Support\SleFhe\RegistryImportPreflight;
use App\Support\SleFhe\RegistryNormalizer;

final class CorrectionApproval
{
    public function __construct(private array $policy) {}

    public function check(string $manifestBytes, string $approvalBytes, ?string $authorizationBytes, string $operator, string $target, bool $apply): array
    {
        $m = CorrectionEvidence::manifest($manifestBytes);
        $hash = hash('sha256', $manifestBytes);
        CorrectionEvidence::require($target === $m['target']['database'], 'Explicit target differs from approved database.');
        $approval = CorrectionEvidence::signed($approvalBytes, $this->policy['approval_keys'] ?? [],
            'institutional_approval', $hash, time());
        $refs = array_column($m['items'], 'reference');
        CorrectionEvidence::require(($approval['items'] ?? null) === $refs, 'Approval must cover exactly the selected atomic manifest.');
        foreach ($m['items'] as $item) {
            CorrectionEvidence::require($item['approval_reference'] === $approval['reference'], 'Item approval reference conflicts with signed authority.');
        }
        if ($m['kind'] === 'registry') {
            CorrectionEvidence::file($m['source']['path'], $m['source']['sha256']);
            $source = (new RegistryImportPreflight)->readWorkbook($m['source']['path']);
            CorrectionEvidence::require($source['source']['sha256'] === $m['source']['sha256'], 'Source workbook changed between evidence checks.');
            foreach ($m['items'] as $item) {
                $matches = array_values(array_filter($source['rows'], fn ($row) =>
                    RegistryNormalizer::studentId($row['values']['student_id']) === $item['student_id']));
                CorrectionEvidence::require(count($matches) === 1, 'Missing or duplicate source Student ID; institutional review required.');
                $row = $matches[0];
                CorrectionEvidence::require($row['sheet'] === $item['source_sheet'] && $row['row'] === $item['source_row']
                    && RegistryNormalizer::text($row['values']['course']) === $item['original_course'], 'Approved source coordinates/course do not match the unchanged workbook.');
            }
        }
        $protected = in_array(strtolower($target), array_map('strtolower', $this->policy['protected_databases'] ?? ['sponsorflow']), true);
        $productionEnabled = ($this->policy['production_apply_enabled'] ?? false) === true;
        $blockers = [];
        if ($protected && ! $productionEnabled) {
            $blockers[] = 'Production APPLY is disabled by deployment policy.';
        }
        if (! $protected && ! preg_match('/^sponsorflow_(?:test|correction_test)(?:_[a-z0-9]+)*$/D', $target)) {
            $blockers[] = 'Target is neither an explicitly protected deployment nor an isolated test database.';
        }
        $authorization = null;
        if ($authorizationBytes === null) {
            $blockers[] = 'Separate signed execution authorization is missing.';
        } else {
            $authorization = CorrectionEvidence::signed($authorizationBytes, $this->policy['execution_keys'] ?? [],
                'execution_authorization', $hash, time());
            CorrectionEvidence::require($authorization['_key_hash'] !== $approval['_key_hash'], 'Approval and execution require independent signers.');
            CorrectionEvidence::require(($authorization['approval_sha256'] ?? null) === hash('sha256', $approvalBytes)
                && ($authorization['operator'] ?? null) === $operator
                && ($authorization['target'] ?? null) === $m['target']
                && ($authorization['operation'] ?? null) === ($m['kind'] === 'rollback_registry' ? 'rollback' : 'apply')
                && ($authorization['exclusive_maintenance'] ?? null) === true, 'Execution scope, operator or exclusive maintenance evidence is incomplete.');
            foreach (['backup', 'isolated_test'] as $kind) {
                $proof = $authorization[$kind] ?? [];
                CorrectionEvidence::require(($proof['verified'] ?? null) === true && CorrectionEvidence::text($proof['reference'] ?? null)
                    && CorrectionEvidence::text($proof['verified_by'] ?? null)
                    && $proof['verified_by'] !== $operator, 'Independently verified backup and isolated-test evidence required.');
                CorrectionEvidence::require(is_string($proof['verified_at'] ?? null)
                    && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z|[+-]\d{2}:\d{2})$/D', $proof['verified_at']) === 1
                    && strtotime($proof['verified_at']) !== false
                    && strtotime($proof['verified_at']) <= time(), 'Evidence verification date required.');
                CorrectionEvidence::file($proof['path'] ?? '', $proof['sha256'] ?? '');
            }
            CorrectionEvidence::require(($authorization['isolated_test']['database'] ?? '') !== ''
                && ! in_array(strtolower($authorization['isolated_test']['database']), array_map('strtolower', $this->policy['protected_databases'] ?? ['sponsorflow']), true),
                'Isolated test evidence must identify a non-production database.');
        }
        if ($apply) {
            CorrectionEvidence::require($blockers === [], implode(' ', $blockers));
        }

        return ['manifest' => $m, 'manifest_sha256' => $hash, 'approval' => $approval,
            'authorization' => $authorization, 'operator' => $operator, 'protected' => $protected,
            'execution_blockers' => $blockers];
    }
}
