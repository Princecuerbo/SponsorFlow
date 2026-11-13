<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchCandidate extends Model
{
    use HasFactory;

    protected $table = 'generated_batch_items';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'generated_batch_id',
        'application_id',
        'rank_position',
        'origin_type',
        'fixed_list_item_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rank_position' => 'integer',
            'fixed_list_item_id' => 'integer',
        ];
    }

    public function generatedBatch(): BelongsTo
    {
        return $this->belongsTo(GeneratedBatch::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function fixedListItem(): BelongsTo
    {
        return $this->belongsTo(FixedListItem::class);
    }

    public function scopeAccountingVisible(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereHas('application', fn ($applications) => $applications->approvedBeneficiaries()
            ->whereNotNull('approved_at')->whereNotNull('verified_at')
            ->whereHas('studentProfile.sleFheVerification', fn ($verification) => $verification->whereNotNull('verified_by')->whereNotNull('verified_at')))
            ->whereHas('generatedBatch', fn ($batches) => $batches->where('status', \App\Enums\GeneratedBatchStatus::Approved)
                ->where('submission_version', '>', 0)->whereNotNull('submitted_roster')
                ->whereHas('sponsorApprovals', fn ($approvals) => $approvals
                    ->whereColumn('sponsor_approvals.submission_version', 'generated_batches.submission_version')
                    ->whereColumn('sponsor_approvals.sponsorship_program_id', 'generated_batches.sponsorship_program_id')
                    ->where('confirmation_status', \App\Enums\ConfirmationStatus::Confirmed)
                    ->whereNotNull('decided_by_id')->whereNotNull('decided_at')
                    ->whereNotNull('fassg_recorded_by_id')->whereNotNull('fassg_recorded_at')
                    ->whereNotNull('accounting_forwarded_by_id')->whereNotNull('accounting_forwarded_at')));
    }

    /** File existence, frozen identities and conflicts supplement the SQL eligibility gate. */
    public function isAccountingEligible(): bool
    {
        $batch = $this->generatedBatch;
        $application = $this->application;
        $profile = $application?->studentProfile;
        if ($batch === null || $application === null || $profile === null
            || $batch->status !== \App\Enums\GeneratedBatchStatus::Approved
            || ! $application->isActiveSponsorship() || $application->approved_at === null || $application->verified_at === null
            || (int) $application->sponsorship_program_id !== (int) $batch->sponsorship_program_id
            || ! $batch->hasIntactSubmittedRoster() || ! $batch->currentApproval()?->isForwardedToAccounting()
            || $profile->hasConflictingActiveSponsorship($application->id)
            || $batch->sponsorshipProgram->beneficiaryEligibilityErrors($application) !== []) {
            return false;
        }
        if ($application->is_auto_provisioned && ($application->endorsed_by_id === null || $application->endorsed_at === null)) {
            return false;
        }

        return $this->origin_type !== 'fixed_list' || ($this->fixedListItem !== null
            && $this->fixedListItem->is_sle_fhe_verified
            && $this->fixedListItem->student_id_number === $profile->student_id_number
            && (int) $this->fixedListItem->fixedList?->sponsorship_program_id === (int) $batch->sponsorship_program_id);
    }

    public static function accountingBeneficiaries(): \Illuminate\Support\Collection
    {
        return static::query()->accountingVisible()->with([
            'application.studentProfile.user', 'application.studentProfile.academicProgram', 'application.documents',
            'generatedBatch.sponsorshipProgram.sponsor', 'fixedListItem.fixedList',
        ])->get()->filter(fn (self $item) => $item->isAccountingEligible())->unique('application_id')->values();
    }

    public function getStudentNameAttribute(): string
    {
        return (string) ($this->application?->studentProfile?->full_name ?: $this->application?->studentProfile?->user?->name ?: 'Unknown');
    }

    public function getStudentIdNumberAttribute(): string
    {
        return (string) ($this->application?->studentProfile?->student_id_number ?? 'N/A');
    }

    public function getCourseAttribute(): string
    {
        $profile = $this->application?->studentProfile;

        return (string) ($profile?->academicProgram?->name ?? $profile?->course ?? 'Unspecified');
    }

    public function getDisplayCourseAttribute(): string
    {
        return $this->course;
    }

    public function getYearLevelAttribute(): ?int
    {
        return $this->application?->studentProfile?->year_level;
    }

    public function getCampusAttribute(): ?string
    {
        return $this->application?->studentProfile?->campus;
    }

    public function getIsSleFheVerifiedAttribute(): bool
    {
        return (bool) $this->application?->studentProfile?->isSleFheVerified();
    }

    public function getIsFixedListAttribute(): bool
    {
        return $this->origin_type === 'fixed_list';
    }

    public function getIsManuallyEndorsedAttribute(): bool
    {
        return $this->origin_type === 'fixed_list';
    }
}
