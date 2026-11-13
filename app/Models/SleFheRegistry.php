<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SleFheRegistry extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'student_id',
        'sex',
        'civil_status',
        'purok',
        'barangay',
        'municipality',
        'course',
        'year_level',
        'campus',
        'district',
        'is_verified',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'district' => 'integer',
            'is_verified' => 'boolean',
        ];
    }

    /**
     * @param  Builder<SleFheRegistry>  $query
     */
    public function scopeByStudentId(Builder $query, string $id): Builder
    {
        return $query->where('student_id', trim($id));
    }
}
