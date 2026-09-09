<?php

namespace App\Models;

use App\Enums\ProgramCategory;
use App\Enums\ProgramStatus;
use App\Enums\ApplicationStatus;
use App\Enums\DocumentType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SponsorshipProgram extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sponsor_id',
        'program_name',
        'category',
        'total_slots',
        'available_slots',
        'status',
        'min_gpa',
        'target_course',
        'address_requirement',
        'requires_relative_verification',
        'eligible_year_levels',
        'eligible_campuses',
        'required_documents',
        'end_date',
        'application_deadline',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ProgramCategory::class,
            'total_slots' => 'integer',
            'status' => ProgramStatus::class,
            'available_slots' => 'integer',
            'min_gpa' => 'decimal:2',
            'requires_relative_verification' => 'boolean',
            'eligible_year_levels' => 'array',
            'eligible_campuses' => 'array',
            'required_documents' => 'array',
            'end_date' => 'date',
            'application_deadline' => 'date',
        ];
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    public function academicPrograms(): BelongsToMany
    {
        return $this->belongsToMany(
            AcademicProgram::class,
            'program_academic_program',
            'sponsorship_program_id',
            'academic_program_id'
        )->withTimestamps();
    }

    public function courses(): BelongsToMany
    {
        return $this->academicPrograms();
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function getEligibleCoursesAttribute(): \Illuminate\Support\Collection
    {
        $programs = $this->relationLoaded('courses')
            ? $this->courses
            : ($this->relationLoaded('academicPrograms') ? $this->academicPrograms : $this->academicPrograms);

        if ($programs && $programs->isNotEmpty()) {
            return $programs->map(fn ($p) => $p->name ?: $p->code)->values();
        }

        if (filled($this->target_course)) {
            return collect(explode(',', (string) $this->target_course))
                ->map(fn ($c) => trim($c))
                ->filter()
                ->values();
        }

        return collect();
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function getFilledSlotsAttribute(): int
    {
        if (array_key_exists('approved_count', $this->attributes)) {
            return max(0, (int) $this->attributes['approved_count']);
        }

        return (int) $this->applications()
            ->previouslyApprovedBeneficiaries()
            ->distinct('student_profile_id')
            ->count('student_profile_id');
    }

    public function getAvailableSlotsAttribute(): int
    {
        return max(0, (int) $this->total_slots - $this->filled_slots);
    }

    public function getUtilizationAttribute(): int
    {
        return $this->total_slots > 0
            ? (int) round(($this->filled_slots / (int) $this->total_slots) * 100)
            : 0;
    }

    public function hasActiveApplicationForStudent(int $studentProfileId): bool
    {
        return $this->applications()
            ->where('student_profile_id', $studentProfileId)
            ->whereIn('status', [
                'submitted',
                ApplicationStatus::Pending,
                ApplicationStatus::Verified,
                ApplicationStatus::Approved,
                ApplicationStatus::Ongoing,
                ApplicationStatus::ResubmissionRequested,
            ])
            ->exists();
    }

    public function cascadeExpiredApplications(): void
    {
        $applicationIds = $this->applications()
            ->whereIn('status', [
                ApplicationStatus::Approved,
                ApplicationStatus::Verified,
                ApplicationStatus::Pending,
                ApplicationStatus::Ongoing,
                ApplicationStatus::ResubmissionRequested,
            ])
            ->pluck('id');

        if ($applicationIds->isEmpty()) {
            return;
        }

        $this->applications()
            ->whereKey($applicationIds)
            ->update(['status' => ApplicationStatus::Expired->value]);

        StudentProfile::query()
            ->whereIn('active_sponsorship_id', $applicationIds)
            ->update(['active_sponsorship_id' => null]);
    }

    public function fixedLists(): HasMany
    {
        return $this->hasMany(FixedList::class);
    }

    public function sponsorApprovals(): HasMany
    {
        return $this->hasMany(SponsorApproval::class);
    }

    /**
     * Canonical required-document values for this program, derived from the
     * required_documents checklist (label → document types). Falls back to the
     * application-level required set when no checklist is configured.
     *
     * @return list<string>
     */
    public function requiredDocumentCanonicalValues(): array
    {
        $required = [];

        foreach ((array) $this->required_documents ?? [] as $label) {
            foreach (DocumentType::typesForLabel((string) $label) as $type) {
                $required[] = DocumentType::canonicalValue($type);
            }
        }

        if ($required === []) {
            foreach (DocumentType::requiredForApplication() as $type) {
                $required[] = DocumentType::canonicalValue($type);
            }
        }

        return array_values(array_unique($required));
    }

    public function isOpen(): bool
    {
        return $this->status === ProgramStatus::Open;
    }

    public function isApplicationClosed(): bool
    {
        return $this->application_deadline && now()->gt($this->application_deadline->endOfDay());
    }

    public function decrementAvailableSlot(): bool
    {
        // Use the raw DB column for the guard — the dynamic accessor already
        // reflects the just-saved Ongoing application, so checking it here
        // would give a false "no slots" result.
        $rawAvailable = (int) $this->attributes['available_slots'];

        if ($rawAvailable <= 0) {
            return false;
        }

        $this->decrement('available_slots');
        $this->refresh();

        // After decrement, use the live accessor to decide if the program is now full.
        if ($this->available_slots <= 0) {
            $this->update(['status' => ProgramStatus::Closed]);
        }

        return true;
    }

    public function getEffectiveStatusAttribute(): ProgramStatus
    {
        $status = $this->getRawOriginal('status');

        if (
            $status === ProgramStatus::Expired->value
            || ($this->end_date !== null && Carbon::parse($this->end_date)->endOfDay()->isPast())
        ) {
            return ProgramStatus::Expired;
        }

        if ($status === ProgramStatus::Closed->value || $this->available_slots <= 0) {
            return ProgramStatus::Closed;
        }

        return ProgramStatus::tryFrom((string) $status) ?? ProgramStatus::Open;
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', ProgramStatus::Open);
    }

    /**
     * Check a student profile against this program's course, year level,
     * campus, and address/residency requirements without any submitted data.
     *
     * @return array{is_eligible: bool, reasons: list<string>}
     */
    public function checkEligibility(StudentProfile $profile): array
    {
        $reasons = [];

        if (! $this->isOpen()) {
            $reasons[] = 'This sponsorship program is not open for applications.';
        }

        if ($this->available_slots < 1) {
            $reasons[] = 'This sponsorship program has no remaining slots.';
        }

        if (! $this->courseIsEligibleFor($profile)) {
            $reasons[] = 'Your course is not eligible for this program.';
        }

        if (! empty($this->eligible_year_levels) && $profile->year_level !== null) {
            $allowedLevels = array_map('strval', $this->eligible_year_levels);

            if (! in_array((string) $profile->year_level, $allowedLevels, true)) {
                $formatted = implode(', ', array_map(fn ($y) => "Year {$y}", $allowedLevels));
                $reasons[] = "This program is only open to: {$formatted}. Your year level does not qualify.";
            }
        }

        $eligibleCampuses = (array) ($this->eligible_campuses ?? []);

        if ($eligibleCampuses !== [] && ! in_array($profile->campus, $eligibleCampuses, true)) {
            $reasons[] = 'Your registered campus (' . ($profile->campus ?? 'Not Assigned') . ') is not eligible for this sponsorship program.';
        }

        if (filled($this->address_requirement)) {
            $requirement = strtolower((string) $this->address_requirement);
            $isRural = (bool) $profile->is_rural;

            if (str_contains($requirement, 'rural') && ! $isRural) {
                $reasons[] = 'This program requires rural residency.';
            }

            if (str_contains($requirement, 'urban') && $isRural) {
                $reasons[] = 'This program requires urban residency.';
            }

            $location = strtolower(trim((string) $profile->full_address . ' ' . $profile->barangay));

            if (str_contains($requirement, 'davao oriental') && ! str_contains($location, 'davao oriental')) {
                $reasons[] = 'Your address does not meet the program location requirement.';
            }
        }

        return [
            'is_eligible' => $reasons === [],
            'reasons' => $reasons,
        ];
    }

    /**
     * Whether the student's course / degree program qualifies for this program.
     */
    private function courseIsEligibleFor(StudentProfile $profile): bool
    {
        $academicPrograms = $this->academicPrograms;

        if ($academicPrograms->isNotEmpty()) {
            if ($profile->academic_program_id !== null && $academicPrograms->contains('program_id', $profile->academic_program_id)) {
                return true;
            }

            $studentCourse = trim((string) $profile->course);

            foreach ($academicPrograms as $academicProgram) {
                if (strcasecmp(trim($academicProgram->code), $studentCourse) === 0 || strcasecmp(trim($academicProgram->name), $studentCourse) === 0) {
                    return true;
                }
            }

            return false;
        }

        if (! filled($this->target_course)) {
            return true;
        }

        $allowedCourses = array_map('trim', explode(',', (string) $this->target_course));

        return in_array(trim((string) $profile->course), $allowedCourses, true);
    }

    /**
     * GWA uses the Philippine scale (1.00 is highest). A lower submitted GWA is better.
     *
     * @return list<string>
     */
    public function eligibilityErrors(
        StudentProfile $profile,
        float $gpa,
        string $address,
        bool $isRural,
    ): array {
        $errors = [];

        if (! $this->isOpen()) {
            $errors[] = 'This sponsorship program is not open for applications.';
        }

        if ($this->available_slots < 1) {
            $errors[] = 'This sponsorship program has no remaining slots.';
        }

        if ($this->min_gpa !== null && $gpa > (float) $this->min_gpa) {
            $errors[] = "Submitted GWA must be {$this->min_gpa} or better.";
        }

        if (! $this->courseIsEligibleFor($profile)) {
            $errors[] = 'Your course is not eligible for this program.';
        }

        if (filled($this->address_requirement)) {
            $requirement = strtolower((string) $this->address_requirement);

            if (str_contains($requirement, 'rural') && ! $isRural) {
                $errors[] = 'This program requires rural residency.';
            }

            if (str_contains($requirement, 'urban') && $isRural) {
                $errors[] = 'This program requires urban residency.';
            }

            $location = strtolower(trim($address . ' ' . $profile->barangay));

            if (str_contains($requirement, 'davao oriental') && ! str_contains($location, 'davao oriental')) {
                $errors[] = 'Your address does not meet the program location requirement.';
            }
        }

        if (! empty($this->eligible_year_levels) && $profile->year_level !== null) {
            $allowedLevels = array_map('strval', $this->eligible_year_levels);
            if (! in_array((string) $profile->year_level, $allowedLevels, true)) {
                $formatted = implode(', ', array_map(fn ($y) => "Year {$y}", $allowedLevels));
                $errors[] = "This program is only open to: {$formatted}. Your year level does not qualify.";
            }
        }

        return $errors;
    }
}
