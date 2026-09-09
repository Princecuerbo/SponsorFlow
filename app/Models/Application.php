<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\ProgramStatus;
use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Application extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'student_profile_id',
        'sponsorship_program_id',
        'gpa_submitted',
        'address_submitted',
        'is_rural_submitted',
        'employee_name',
        'employee_id_number',
        'employee_relationship',
        'status',
        'submitted_at',
        'verified_at',
        'approved_at',
        'sponsor_approval_path',
        'rejection_reason',
        'resubmission_notes',
        'requested_documents',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gpa_submitted' => 'decimal:2',
            'is_rural_submitted' => 'boolean',
            'status' => ApplicationStatus::class,
            'requested_documents' => 'array',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function sponsorshipProgram(): BelongsTo
    {
        return $this->belongsTo(SponsorshipProgram::class);
    }

    public function getStatusAttribute($value): ?ApplicationStatus
    {
        if ($this->sponsorshipProgram?->status === ProgramStatus::Expired) {
            return ApplicationStatus::Expired;
        }

        return $value instanceof ApplicationStatus
            ? $value
            : ApplicationStatus::tryFrom((string) $value);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    /**
     * Program-required document groups vs. how many are satisfied by uploaded
     * documents. Mirrors the canonical grouping used by the FASSG verification
     * detail view (Proof of Residence / Barangay Certificate are one slot).
     *
     * @return array{required: int, uploaded: int}
     */
    public function documentStatusCounts(): array
    {
        $required = $this->sponsorshipProgram?->requiredDocumentCanonicalValues() ?? [];

        $uploaded = $this->documents
            ->pluck('document_type')
            ->map(fn ($type): string => DocumentType::canonicalValue($type))
            ->unique()
            ->values()
            ->all();

        return [
            'required' => count($required),
            'uploaded' => count(array_intersect($required, $uploaded)),
        ];
    }

    public function activeForStudent(): HasOne
    {
        return $this->hasOne(StudentProfile::class, 'active_sponsorship_id');
    }

    public function isActiveSponsorship(): bool
    {
        return $this->status?->isActiveSponsorship() ?? false;
    }

    public function scopeApprovedBeneficiaries(Builder $query): Builder
    {
        return $query->whereIn('applications.status', [
            ApplicationStatus::Approved,
            ApplicationStatus::Ongoing,
        ]);
    }

    public function scopePreviouslyApprovedBeneficiaries(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereIn('applications.status', [
                ApplicationStatus::Approved,
                ApplicationStatus::Ongoing,
            ])->orWhere(function (Builder $query): void {
                $query->where('applications.status', ApplicationStatus::Expired)
                    ->whereNotNull('applications.approved_at');
            });
        });
    }
}
