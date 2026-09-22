<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\SleFheStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentProfile extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'student_id_number',
        'first_name',
        'middle_name',
        'last_name',
        'extension_name',
        'course',
        'academic_program_id',
        'campus',
        'contact_number',
        'year_level',
        'gender',
        'birthdate',
        'active_sponsorship_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'year_level' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->extension_name,
        ]);

        return trim(implode(' ', $parts));
    }

    public function getFullAddressAttribute(): string
    {
        return $this->sleFheVerification?->verified_address
            ?? $this->sleFheRequest?->full_address
            ?? '';
    }

    public function getDisplayCourseAttribute(): string
    {
        return $this->academicProgram?->name ?? $this->course ?? 'Unspecified';
    }

    public function academicProgram(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class, 'academic_program_id', 'program_id');
    }

    public function program(): BelongsTo
    {
        return $this->academicProgram();
    }

    public function activeSponsorship(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'active_sponsorship_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function sleFheRequest(): HasOne
    {
        return $this->hasOne(SleFheRequest::class);
    }

    public function sleFheRequests(): HasMany
    {
        return $this->hasMany(SleFheRequest::class, 'student_profile_id');
    }

    public function sleFheVerification(): HasOne
    {
        return $this->hasOne(SleFheVerification::class);
    }

    public function sleFheRejections(): HasMany
    {
        return $this->hasMany(SleFheRejection::class);
    }

    public function getSleFheStatusAttribute(): string
    {
        if ($this->sleFheVerification()->exists()) {
            return SleFheStatus::Verified->value;
        }

        if ($this->sleFheRequest()->exists()) {
            return SleFheStatus::PendingReview->value;
        }

        if ($this->sleFheRejections()->exists()) {
            return SleFheStatus::Rejected->value;
        }

        return SleFheStatus::Unverified->value;
    }

    public function hasActiveSponsorship(): bool
    {
        // Check if there is an active sponsorship and verify the program is not expired
        if ($this->active_sponsorship_id !== null) {
            $activeApp = $this->activeSponsorship;
            if ($activeApp && $this->isProgramActive($activeApp->sponsorshipProgram)) {
                return true;
            }
            // Clear the inactive sponsorship reference
            $this->update(['active_sponsorship_id' => null]);
        }

        // Check for any approved/ongoing applications with active programs
        return $this->applications()
            ->whereIn('status', [
                ApplicationStatus::Approved->value,
                ApplicationStatus::Ongoing->value,
            ])
            ->whereHas('sponsorshipProgram', function ($query) {
                $query->where('status', '!=', 'Expired')
                    ->where(function ($q) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', now()->toDateString());
                    });
            })
            ->exists();
    }

    /**
     * Check if a program is still active (not expired and end_date hasn't passed)
     */
    private function isProgramActive(?SponsorshipProgram $program): bool
    {
        if ($program === null) {
            return false;
        }

        // Program must not be expired
        if ($program->status?->value === 'Expired') {
            return false;
        }

        // If end_date exists, it must be in the future or today
        if ($program->end_date !== null && $program->end_date < now()->toDateString()) {
            return false;
        }

        return true;
    }

    public function hasCompleteIdentity(): bool
    {
        return filled($this->student_id_number)
            && (filled($this->academic_program_id) || filled($this->course))
            && $this->year_level !== null;
    }

    public function syncSleFheFromFixedLists(): bool
    {
        $verified = FixedListItem::query()
            ->where('student_id_number', $this->student_id_number)
            ->where('is_sle_fhe_verified', true)
            ->exists();

        if ($verified === true) {
            SleFheVerification::firstOrCreate(
                ['student_profile_id' => $this->id],
                ['verified_address' => '', 'verified_at' => now()]
            );
        }

        return $verified;
    }

    public function scopeSleFheVerified(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereHas('sleFheVerification')
                ->orWhereHas('sleFheRequest', fn ($query) => $query->where('status', 'verified'));
        });
    }

    public function isSleFheVerified(): bool
    {
        return $this->sleFheVerification()->exists()
            || $this->sleFheRequest()->where('status', 'verified')->exists();
    }
}
