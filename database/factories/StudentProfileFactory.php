<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => UserRole::Student]),
            'student_id_number' => fake()->unique()->numerify('2024-#####'),
            'course' => 'Bachelor of Science in Information Technology',
            'year_level' => 3,
            'gender' => 'Female',
            'birthdate' => '2004-06-15',
            'province' => 'Davao Oriental',
            'municipality' => 'Mati City',
            'barangay' => 'San Isidro',
            'home_address' => 'Purok 2',
            'is_rural' => true,
            'is_sle_fhe_verified' => false,
            'active_sponsorship_id' => null,
        ];
    }
}
