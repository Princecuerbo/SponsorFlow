<?php

namespace Database\Factories;

use App\Enums\FixedListItemStatus;
use App\Models\FixedList;
use App\Models\FixedListItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FixedListItem>
 */
class FixedListItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'fixed_list_id' => FixedList::factory(),
            'student_name' => fake()->name(),
            'student_id_number' => fake()->unique()->numerify('2024-#####'),
            'course' => 'Bachelor of Science in Information Technology',
            'year_level' => 3,
            'is_sle_fhe_verified' => true,
            'status' => FixedListItemStatus::Verified,
        ];
    }
}
