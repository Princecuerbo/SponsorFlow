<?php

namespace App\Models;

use App\Enums\FixedListItemStatus;
use App\Enums\ProgramStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedListItem extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'fixed_list_id',
        'application_id',
        'student_name',
        'student_id_number',
        'course',
        'year_level',
        'campus',
        'is_sle_fhe_verified',
        'is_fixed_list',
        'origin_type',
        'rank_position',
        'status',
        'is_manually_endorsed',
        'endorsed_by_id',
        'endorsed_at',
        'fassg_assigned_at',
        'fassg_assigned_by_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year_level' => 'integer',
            'is_sle_fhe_verified' => 'boolean',
            'is_fixed_list' => 'boolean',
            'rank_position' => 'integer',
            'is_manually_endorsed' => 'boolean',
            'status' => FixedListItemStatus::class,
            'endorsed_at' => 'datetime',
            'fassg_assigned_at' => 'datetime',
        ];
    }

    public function fixedList(): BelongsTo
    {
        return $this->belongsTo(FixedList::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function endorsedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'endorsed_by_id');
    }

    public function fassgAssignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fassg_assigned_by_id');
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_id_number', 'student_id_number');
    }

    public function getDisplayCourseAttribute(): string
    {
        return $this->studentProfile?->academicProgram?->name ?? $this->course ?? 'Unspecified';
    }

    public function matchingStudentProfile(): ?StudentProfile
    {
        return $this->studentProfile;
    }

    /** List preparation only; qualification documents are verified in Generated Batch review. */
    public function endorsementErrors(SponsorshipProgram $program): array
    {
        $profile = $this->studentProfile;
        if ($profile === null) {
            return ['A matching registered student profile is required.'];
        }

        $errors = [];
        if (! $this->is_sle_fhe_verified || ! $profile->hasDocumentedSleFheVerification()) {
            $errors[] = 'Verify this list entry against documented FASSG SLE-FHE evidence first.';
        }
        if ($this->status === FixedListItemStatus::Ineligible) {
            $errors[] = 'An ineligible candidate cannot be endorsed through ordinary endorsement.';
        }
        if ($profile->hasConflictingActiveSponsorship()) {
            $errors[] = 'A conflicting active sponsorship prevents endorsement.';
        }
        $errors = array_merge($errors, $program->checkEligibility($profile)['reasons']);
        if ($program->status === ProgramStatus::Expired
            || ($program->end_date !== null && $program->end_date->toDateString() < now()->toDateString())) {
            $errors[] = 'The sponsorship program has expired.';
        }
        if (! empty($program->eligible_year_levels) && $profile->year_level === null) {
            $errors[] = 'Record the student year level required by this program.';
        }

        return array_values(array_unique($errors));
    }

    public function hasRecordedEndorsement(): bool
    {
        return $this->is_manually_endorsed && $this->status === FixedListItemStatus::Endorsed
            && $this->endorsed_by_id !== null && $this->endorsed_at !== null;
    }
}
