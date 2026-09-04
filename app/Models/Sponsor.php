<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\FixedListStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Sponsor extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'company_organization_name',
        'contact_person',
        'contact_email',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sponsorshipPrograms(): HasMany
    {
        return $this->hasMany(SponsorshipProgram::class);
    }

    public function forwardedFixedLists(): HasManyThrough
    {
        return $this->hasManyThrough(FixedList::class, SponsorshipProgram::class)
            ->where('fixed_lists.status', FixedListStatus::Submitted);
    }

    public function verifiedApplicants(): HasManyThrough
    {
        return $this->hasManyThrough(Application::class, SponsorshipProgram::class)
            ->where('applications.status', ApplicationStatus::Verified);
    }

    public function ownsProgram(SponsorshipProgram $program): bool
    {
        return $program->sponsor_id === $this->id;
    }
}
