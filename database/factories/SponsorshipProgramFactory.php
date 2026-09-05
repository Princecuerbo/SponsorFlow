<?php

namespace Database\Factories;

use App\Enums\ProgramCategory;
use App\Enums\ProgramStatus;
use App\Models\Sponsor;
use App\Models\SponsorshipProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SponsorshipProgram>
 */
class SponsorshipProgramFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sponsor_id' => Sponsor::factory(),
            'program_name' => fake()->unique()->sentence(3),
            'category' => ProgramCategory::Individual,
            'total_slots' => 10,
            'available_slots' => 10,
            'status' => ProgramStatus::Open,
            'min_gpa' => 2.50,
            'target_course' => null,
            'address_requirement' => null,
        ];
    }
}
