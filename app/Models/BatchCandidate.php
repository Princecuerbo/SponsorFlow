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
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rank_position' => 'integer',
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

    public function getStudentNameAttribute(): string
    {
        return (string) ($this->application?->studentProfile?->full_name ?? 'Unknown');
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
