<?php

namespace App\Models;

use App\Enums\FixedListItemStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedListItem extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'fixed_list_id',
        'application_id',
        'student_name',
        'student_id_number',
        'course',
        'year_level',
        'campus',
        'is_sle_fhe_verified',
        'status',
        'is_manually_endorsed',
        'endorsed_by_id',
        'endorsed_at',
        'fassg_assigned_at',
        'fassg_assigned_by_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year_level' => 'integer',
            'is_sle_fhe_verified' => 'boolean',
            'is_manually_endorsed' => 'boolean',
            'status' => FixedListItemStatus::class,
            'endorsed_at' => 'datetime',
            'fassg_assigned_at' => 'datetime',
        ];
    }

    public function fixedList(): BelongsTo
    {
        return $this->belongsTo(FixedList::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function endorsedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'endorsed_by_id');
    }

    public function fassgAssignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fassg_assigned_by_id');
    }

    public function matchingStudentProfile(): ?StudentProfile
    {
        return StudentProfile::query()
            ->where('student_id_number', $this->student_id_number)
            ->first();
    }
}
