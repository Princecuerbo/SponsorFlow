<?php

namespace Database\Factories;

use App\Enums\GeneratedBatchStatus;
use App\Enums\UserRole;
use App\Models\GeneratedBatch;
use App\Models\SponsorshipProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GeneratedBatch>
 */
class GeneratedBatchFactory extends Factory
{
    protected $model = GeneratedBatch::class;

    public function definition(): array
    {
        return [
            'sponsorship_program_id' => SponsorshipProgram::factory(),
            'fixed_list_id' => null,
            'batch_name' => 'Batch '.fake()->unique()->numerify('###'),
            'total_slots' => 0,
            'status' => GeneratedBatchStatus::Submitted,
            'created_by_fassg_id' => User::factory()->state(['role' => UserRole::Fassg]),
        ];
    }
}
