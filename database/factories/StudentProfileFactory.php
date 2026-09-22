<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\AcademicProgram;
use App\Models\SleFheVerification;
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
            'academic_program_id' => AcademicProgram::factory(),
            'course' => 'Bachelor of Science in Information Technology',
            'year_level' => 3,
            'gender' => 'Female',
            'birthdate' => '2004-06-15',
            'active_sponsorship_id' => null,
        ];
    }

    public function verified(): static
    {
        return $this->afterCreating(function (StudentProfile $profile): void {
            SleFheVerification::create([
                'student_profile_id' => $profile->id,
                'verified_address' => 'Purok 2, Brgy. San Isidro, Mati City, Davao Oriental',
                'verified_at' => now(),
            ]);
        });
    }
}
