<?php

namespace App\Models;

use App\Enums\ConfirmationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SponsorApproval extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sponsorship_program_id',
        'fixed_list_id',
        'approval_document_path',
        'confirmation_status',
        'uploaded_by_sponsor_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confirmation_status' => ConfirmationStatus::class,
        ];
    }

    public function sponsorshipProgram(): BelongsTo
    {
        return $this->belongsTo(SponsorshipProgram::class);
    }

    public function fixedList(): BelongsTo
    {
        return $this->belongsTo(FixedList::class);
    }

    public function uploadedBySponsor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_sponsor_id');
    }

    public function isConfirmed(): bool
    {
        return $this->confirmation_status === ConfirmationStatus::Confirmed;
    }

    public function isPending(): bool
    {
        return $this->confirmation_status === ConfirmationStatus::Pending;
    }
}
