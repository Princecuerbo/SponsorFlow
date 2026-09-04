<?php

namespace Database\Factories;

use App\Enums\FixedListStatus;
use App\Enums\UserRole;
use App\Models\FixedList;
use App\Models\SponsorshipProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FixedList>
 */
class FixedListFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sponsorship_program_id' => SponsorshipProgram::factory(),
            'batch_name' => 'Batch '.fake()->unique()->numerify('###'),
            'uploaded_by_fassg_id' => User::factory()->state(['role' => UserRole::Fassg]),
            'total_names' => 0,
            'status' => FixedListStatus::Submitted,
        ];
    }
}
