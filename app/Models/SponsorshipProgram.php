<?php

namespace App\Models;

use App\Enums\ProgramCategory;
use App\Enums\ProgramStatus;
use App\Enums\ApplicationStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'available_slots',
        'status',
        'min_gpa',
        'target_course',
        'address_requirement',
        'end_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ProgramCategory::class,
            'status' => ProgramStatus::class,
            'available_slots' => 'integer',
            'min_gpa' => 'decimal:2',
            'end_date' => 'date',
        ];
    }

    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
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

    public function isOpen(): bool
    {
        return $this->status === ProgramStatus::Open;
    }

    public function decrementAvailableSlot(): bool
    {
        if ($this->available_slots <= 0) {
            return false;
        }

        $this->decrement('available_slots');
        $this->refresh();

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

        if (filled($this->target_course)) {
            $allowedCourses = array_map('trim', explode(',', (string) $this->target_course));

            if (! in_array(trim((string) $profile->course), $allowedCourses, true)) {
                $errors[] = 'Your course is not eligible for this program.';
            }
        }

        if (filled($this->address_requirement)) {
            $requirement = strtolower((string) $this->address_requirement);

            if (str_contains($requirement, 'rural') && ! $isRural) {
                $errors[] = 'This program requires rural residency.';
            }

            $location = strtolower(trim($address . ' ' . $profile->barangay));

            if (str_contains($requirement, 'davao oriental') && ! str_contains($location, 'davao oriental')) {
                $errors[] = 'Your address does not meet the program location requirement.';
            }
        }

        return $errors;
    }
}
