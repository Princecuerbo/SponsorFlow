<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\BatchCandidate;
use App\Models\GeneratedBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BatchCandidate>
 */
class BatchCandidateFactory extends Factory
{
    protected $model = BatchCandidate::class;

    public function definition(): array
    {
        return [
            'generated_batch_id' => GeneratedBatch::factory(),
            'application_id' => Application::factory(),
            'rank_position' => 1,
            'origin_type' => 'ranked_queue',
        ];
    }
}
