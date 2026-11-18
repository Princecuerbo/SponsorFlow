@php
if (!function_exists('resolveAuditActionMeta')) {
    function resolveAuditActionMeta(string $action): array {
        $labels = [
            'admin.user.deactivated' => 'User Account Deactivated',
            'admin.user.restore' => 'User Account Restored',
            'admin.user.restored' => 'User Account Restored',
            'admin.user.created' => 'User Account Created',
            'admin.user.updated' => 'User Account Updated',
            'admin.user.login' => 'Administrator Signed In',
            'sponsor.user.login' => 'Sponsor Signed In',
            'fassg.user.login' => 'FASSG Officer Signed In',
            'accounting.user.login' => 'Accounting Officer Signed In',
            'student.user.login' => 'Student Signed In',
            'accounting.beneficiaries.exported' => 'Beneficiary Records Exported',
            'fassg.generated_batch.generated_from_applications' => 'Batch Created from Applications',
            'fassg.generated_batch.submitted' => 'Batch Submitted to Sponsor',
            'fassg.generated_batch.candidate_removed' => 'Batch Candidate Removed',
            'fassg.sponsor_decision.received' => 'Sponsor Decision Recorded',
            'fassg.beneficiaries.forwarded_to_accounting' => 'Beneficiaries Forwarded to Accounting',
            'fassg.fixed_list.archived' => 'Fixed List Archived',
            'fassg.fixed_list.student_removed' => 'Student Removed from Fixed List',
            'fassg.fixed_list.finalized' => 'Fixed List Finalized',
            'fassg.fixed_list.item_endorsed' => 'Fixed List Candidate Endorsed',
            'fassg.fixed_list.item_endorsement_revoked' => 'Fixed List Endorsement Revoked',
            'fassg.student.sle_fhe_verified' => 'Student SLE-FHE Verified',
            'fassg.student.sle_fhe_verified_registry_override' => 'Student Verified with Registry Override',
            'fassg.student.sle_fhe_fix_requested' => 'Student Correction Requested',
            'fassg.application.verified' => 'Application Verified',
            'sponsor.approval.uploaded' => 'Sponsor Confirmation Uploaded',
            'sponsor.approval.confirmed' => 'Sponsor Approved Whole Batch',
            'sponsor.approval.rejected' => 'Sponsor Rejected Whole Batch',
            'admin.settings.updated' => 'System Settings Updated',
            'admin.backup.created' => 'Database Backup Created',
            'admin.backup.downloaded' => 'Database Backup Downloaded',
        ];

        $icons = [
            'admin.user.deactivated' => 'bi-person-slash',
            'admin.user.restore' => 'bi-person-check',
            'admin.user.restored' => 'bi-person-check',
            'admin.user.created' => 'bi-person-plus',
            'admin.user.updated' => 'bi-pencil-square',
            'admin.user.login' => 'bi-box-arrow-in-right',
            'sponsor.user.login' => 'bi-box-arrow-in-right',
            'fassg.user.login' => 'bi-box-arrow-in-right',
            'accounting.user.login' => 'bi-box-arrow-in-right',
            'student.user.login' => 'bi-box-arrow-in-right',
            'accounting.beneficiaries.exported' => 'bi-file-earmark-spreadsheet',
            'fassg.generated_batch.generated_from_applications' => 'bi-collection',
            'fassg.generated_batch.submitted' => 'bi-send',
            'fassg.generated_batch.candidate_removed' => 'bi-person-x',
            'fassg.sponsor_decision.received' => 'bi-clipboard-check',
            'fassg.beneficiaries.forwarded_to_accounting' => 'bi-forward',
            'fassg.fixed_list.archived' => 'bi-archive',
            'fassg.fixed_list.student_removed' => 'bi-person-x',
            'fassg.fixed_list.finalized' => 'bi-check2-circle',
            'fassg.fixed_list.item_endorsed' => 'bi-patch-check',
            'fassg.fixed_list.item_endorsement_revoked' => 'bi-patch-minus',
            'fassg.student.sle_fhe_verified' => 'bi-shield-check',
            'fassg.student.sle_fhe_verified_registry_override' => 'bi-shield-exclamation',
            'fassg.student.sle_fhe_fix_requested' => 'bi-exclamation-circle',
            'fassg.application.verified' => 'bi-check-circle',
            'sponsor.approval.uploaded' => 'bi-upload',
            'sponsor.approval.confirmed' => 'bi-check-circle',
            'sponsor.approval.rejected' => 'bi-x-circle',
            'admin.settings.updated' => 'bi-sliders',
            'admin.backup.created' => 'bi-database-add',
            'admin.backup.downloaded' => 'bi-download',
        ];

        $label = $labels[$action] ?? str($action)->replace(['.', '_'], ' ')->headline()->toString();
        $icon = $icons[$action] ?? 'bi-shield-check';

        return ['label' => $label, 'icon' => $icon];
    }
}
@endphp
