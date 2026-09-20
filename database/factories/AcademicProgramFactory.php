<?php

namespace Database\Factories;

use App\Models\AcademicProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicProgram>
 */
class AcademicProgramFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'BSIT',
            'name' => 'Bachelor of Science in Information Technology',
            'short_name' => 'BSIT',
            'is_board_program' => false,
            'is_undergraduate' => true,
            'is_active' => true,
        ];
    }
}