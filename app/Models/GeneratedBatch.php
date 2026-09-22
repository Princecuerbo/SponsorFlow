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
        ];
    }

    public function sponsorshipProgram(): BelongsTo
    {
        return $this->belongsTo(SponsorshipProgram::class);
    }

    /**
     * The Finalized Fixed List whose endorsed candidates were locked into the
     * top slots of this batch (Stage 1 lock source).
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
}
