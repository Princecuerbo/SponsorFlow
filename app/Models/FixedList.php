<?php

namespace App\Models;

use App\Enums\FixedListStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FixedList extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sponsorship_program_id',
        'batch_name',
        'uploaded_by_fassg_id',
        'total_names',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_names' => 'integer',
            'status' => FixedListStatus::class,
        ];
    }

    public function sponsorshipProgram(): BelongsTo
    {
        return $this->belongsTo(SponsorshipProgram::class);
    }

    public function uploadedByFassg(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_fassg_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FixedListItem::class);
    }

    public function sponsorApprovals(): HasMany
    {
        return $this->hasMany(SponsorApproval::class);
    }

    public function latestApproval(): HasOne
    {
        return $this->hasOne(SponsorApproval::class)->latestOfMany();
    }

    public function isForwardedToSponsor(): bool
    {
        return $this->status === FixedListStatus::Submitted;
    }
}
