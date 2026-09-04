<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicProgram extends Model
{
    use HasFactory;

    protected $table = 'academic_programs';
    protected $primaryKey = 'program_id';

    protected $fillable = [
        'program_id',
        'code',
        'name',
        'short_name',
        'is_board_program',
        'is_undergraduate',
        'is_active',
    ];

    protected $casts = [
        'is_board_program' => 'boolean',
        'is_undergraduate' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function studentProfiles(): HasMany
    {
        return $this->hasMany(StudentProfile::class, 'academic_program_id', 'program_id');
    }

    public function scholarshipPrograms(): BelongsToMany
    {
        return $this->belongsToMany(
            SponsorshipProgram::class,
            'program_academic_program',
            'academic_program_id',
            'sponsorship_program_id'
        )->withTimestamps();
    }
}
