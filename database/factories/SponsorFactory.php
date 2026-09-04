<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sponsor>
 */
class SponsorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => UserRole::Sponsor]),
            'company_organization_name' => fake()->company(),
            'contact_person' => fake()->name(),
            'contact_email' => fake()->unique()->companyEmail(),
        ];
    }
}
