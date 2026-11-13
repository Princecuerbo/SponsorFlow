<?php

namespace App\Models;

use App\Enums\GeneratedBatchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class GeneratedBatch extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sponsorship_program_id',
        'fixed_list_id',
        'batch_name',
        'total_slots',
        'status',
        'created_by_fassg_id',
        'fassg_assigned_at',
        'fassg_assigned_by_id',
        'submission_version',
        'submitted_roster',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_slots' => 'integer',
            'status' => GeneratedBatchStatus::class,
            'fassg_assigned_at' => 'datetime',
            'submission_version' => 'integer',
            'submitted_roster' => 'array',
        ];
    }

    public function sponsorshipProgram(): BelongsTo
    {
        return $this->belongsTo(SponsorshipProgram::class);
    }

    /**
     * Historical list linkage. Individual source identities are retained on items.
     */
    public function fixedList(): BelongsTo
    {
        return $this->belongsTo(FixedList::class);
    }

    public function createdByFassg(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_fassg_id');
    }

    public function fassgAssignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fassg_assigned_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BatchCandidate::class);
    }

    public function candidates(): HasMany
    {
        return $this->items();
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'batch_id');
    }

    public function sponsorApprovals(): HasMany
    {
        return $this->hasMany(SponsorApproval::class, 'generated_batch_id');
    }

    public function latestApproval(): HasOne
    {
        return $this->hasOne(SponsorApproval::class, 'generated_batch_id')->latestOfMany();
    }

    public function isForwardedToSponsor(): bool
    {
        return $this->status === GeneratedBatchStatus::Submitted;
    }

    public function currentApproval(): ?SponsorApproval
    {
        if ($this->submission_version < 1) {
            return null;
        }

        return $this->sponsorApprovals()->where('submission_version', $this->submission_version)
            ->where('sponsorship_program_id', $this->sponsorship_program_id)->latest('id')->first();
    }

    public function rosterMembers(): array
    {
        return $this->items()->with('application.studentProfile')->orderBy('id')->get()->map(fn ($item) => [
            'item_id' => (int) $item->id,
            'application_id' => (int) $item->application_id,
            'student_profile_id' => $item->application?->student_profile_id,
            'student_id_number' => $item->application?->studentProfile?->student_id_number,
            'sponsorship_program_id' => $item->application?->sponsorship_program_id,
            'origin_type' => $item->origin_type,
            'fixed_list_item_id' => $item->fixed_list_item_id,
        ])->all();
    }

    public function hasIntactSubmittedRoster(): bool
    {
        $snapshot = $this->submitted_roster;

        return $this->submission_version > 0 && is_array($snapshot) && ! empty($snapshot['members'])
            && (int) ($snapshot['program_id'] ?? 0) === (int) $this->sponsorship_program_id
            && (int) ($snapshot['sponsor_id'] ?? 0) === (int) $this->sponsorshipProgram?->sponsor_id
            && $snapshot['members'] === $this->rosterMembers();
    }

    public function assertSubmittedRoster(): void
    {
        if (! $this->hasIntactSubmittedRoster()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'batch' => 'The submitted roster or its evidence is missing or changed. FASSG must review the original records.',
            ]);
        }
    }

    /** Validate every member; approval must never skip a failing candidate. */
    public function assertEligibleMembers(bool $alreadyApproved = false, bool $lockConflicts = false): void
    {
        // Current locking reads avoid validating stale profile/status snapshots after waiting for another award decision.
        $items = $this->items()->with([
            'application' => fn ($query) => $query->when($lockConflicts, fn ($query) => $query->lockForUpdate()),
            'application.studentProfile' => fn ($query) => $query->when($lockConflicts, fn ($query) => $query->lockForUpdate()),
            'application.documents', 'fixedListItem.fixedList',
        ])->get();
        $profileIds = $items->map(fn ($item) => $item->application?->student_profile_id);
        if ($items->isEmpty() || $items->count() !== $profileIds->filter()->unique()->count()
            || $items->count() !== $items->pluck('application_id')->unique()->count()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['batch' => 'A nonempty roster with one valid record per student is required.']);
        }
        foreach ($items as $item) {
            $application = $item->application;
            if ($application?->studentProfile === null) {
                throw \Illuminate\Validation\ValidationException::withMessages(['batch' => 'A roster member no longer has a valid student record.']);
            }
            $allowed = $alreadyApproved
                ? [\App\Enums\ApplicationStatus::Approved, \App\Enums\ApplicationStatus::Ongoing]
                : [\App\Enums\ApplicationStatus::Verified];
            $errors = $this->sponsorshipProgram->beneficiaryEligibilityErrors($application);
            if (! in_array($application->status, $allowed, true) || $application->verified_at === null || ($alreadyApproved && $application->approved_at === null)) {
                $errors[] = 'Each member must have the required individually verified/approved status.';
            }
            if ($application->is_auto_provisioned && ($application->endorsed_by_id === null || $application->endorsed_at === null)) {
                $errors[] = 'FASSG must explicitly verify the fixed-list processing record.';
            }
            if ($application->studentProfile->hasConflictingActiveSponsorship($alreadyApproved ? $application->id : null, $lockConflicts)) {
                $errors[] = 'The student has a conflicting active sponsorship.';
            }
            if ($item->origin_type === 'fixed_list' && ($item->fixedListItem === null
                || (int) $item->fixedListItem->fixedList?->sponsorship_program_id !== (int) $this->sponsorship_program_id
                || $item->fixedListItem->student_id_number !== $application->studentProfile->student_id_number
                || ! $item->fixedListItem->is_sle_fhe_verified)) {
                $errors[] = 'The original verified fixed-list source must match this student and program.';
            }
            if ($errors !== []) {
                throw \Illuminate\Validation\ValidationException::withMessages(['batch' => 'Application '.$application->id.': '.implode(' ', $errors)]);
            }
        }
    }
}
