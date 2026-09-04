<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_profile_id' => StudentProfile::factory(),
            'sponsorship_program_id' => SponsorshipProgram::factory(),
            'gpa_submitted' => 1.75,
            'address_submitted' => 'Purok 2, Barangay San Isidro, Mati City, Davao Oriental',
            'is_rural_submitted' => true,
            'status' => ApplicationStatus::Pending,
            'submitted_at' => now(),
            'verified_at' => null,
            'approved_at' => null,
        ];
    }
}
