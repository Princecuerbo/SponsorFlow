<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicProgram>
 */
class AcademicProgramFactory extends Factory
{
    protected $model = \App\Models\AcademicProgram::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->bothify('??###')),
            'name' => $this->faker->unique()->sentence(3),
            'short_name' => $this->faker->word(),
            'is_board_program' => false,
            'is_undergraduate' => true,
            'is_active' => true,
        ];
    }
}
