<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
