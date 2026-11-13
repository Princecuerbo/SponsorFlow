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
        'generated_batch_id',
        'approval_document_path',
        'confirmation_status',
        'uploaded_by_sponsor_id',
        'submission_version',
        'decided_by_id',
        'decided_at',
        'fassg_recorded_by_id',
        'fassg_recorded_at',
        'accounting_forwarded_by_id',
        'accounting_forwarded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confirmation_status' => ConfirmationStatus::class,
            'submission_version' => 'integer',
            'decided_at' => 'datetime',
            'fassg_recorded_at' => 'datetime',
            'accounting_forwarded_at' => 'datetime',
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

    public function generatedBatch(): BelongsTo
    {
        return $this->belongsTo(GeneratedBatch::class);
    }

    public function uploadedBySponsor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_sponsor_id');
    }

    public function isConfirmed(): bool
    {
        return $this->confirmation_status === ConfirmationStatus::Confirmed;
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fassg_recorded_by_id');
    }

    public function forwardedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accounting_forwarded_by_id');
    }

    public function hasConfirmationEvidence(): bool
    {
        return $this->isConfirmed() && $this->decided_by_id !== null && $this->decided_at !== null
            && $this->uploaded_by_sponsor_id !== null && filled($this->approval_document_path)
            && \Illuminate\Support\Facades\Storage::disk('local')->exists($this->approval_document_path);
    }

    public function isForwardedToAccounting(): bool
    {
        return $this->hasConfirmationEvidence()
            && $this->fassg_recorded_by_id !== null && $this->fassg_recorded_at !== null
            && $this->accounting_forwarded_by_id !== null && $this->accounting_forwarded_at !== null
            && $this->fassg_recorded_at->gte($this->decided_at)
            && $this->accounting_forwarded_at->gte($this->fassg_recorded_at);
    }

    public function isPending(): bool
    {
        return $this->confirmation_status === ConfirmationStatus::Pending;
    }
}
