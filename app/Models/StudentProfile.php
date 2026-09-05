<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentProfile extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'student_id_number',
        'course',
        'academic_program_id',
        'year_level',
        'gender',
        'birthdate',
        'address',
        'barangay',
        'is_rural',
        'is_sle_fhe_verified',
        'sle_fhe_cg_path',
        'sle_fhe_residence_path',
        'sle_fhe_barangay_path',
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
            'is_rural' => 'boolean',
            'is_sle_fhe_verified' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function academicProgram(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class, 'academic_program_id', 'program_id');
    }

    public function activeSponsorship(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'active_sponsorship_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
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
            && filled($this->course)
            && $this->year_level !== null;
    }

    public function syncSleFheFromFixedLists(): bool
    {
        $verified = FixedListItem::query()
            ->where('student_id_number', $this->student_id_number)
            ->where('is_sle_fhe_verified', true)
            ->exists();

        if ($this->is_sle_fhe_verified !== $verified) {
            $this->update(['is_sle_fhe_verified' => $verified]);
        }

        return $verified;
    }
}
