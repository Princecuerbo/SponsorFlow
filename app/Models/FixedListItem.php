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
        'student_name',
        'student_id_number',
        'course',
        'year_level',
        'is_sle_fhe_verified',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year_level' => 'integer',
            'is_sle_fhe_verified' => 'boolean',
            'status' => FixedListItemStatus::class,
        ];
    }

    public function fixedList(): BelongsTo
    {
        return $this->belongsTo(FixedList::class);
    }

    public function matchingStudentProfile(): ?StudentProfile
    {
        return StudentProfile::query()
            ->where('student_id_number', $this->student_id_number)
            ->first();
    }
}
