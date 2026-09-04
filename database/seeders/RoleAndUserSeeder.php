<?php

namespace Database\Seeders;

use App\Enums\ProgramCategory;
use App\Enums\ProgramStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Sponsor;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleAndUserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Maria Santos',
                'email' => 'student@sponsorflow.test',
                'role' => UserRole::Student,
            ],
            [
                'name' => 'FASSG Officer',
                'email' => 'fassg@sponsorflow.test',
                'role' => UserRole::Fassg,
            ],
            [
                'name' => 'Provincial Merit Foundation',
                'email' => 'sponsor@sponsorflow.test',
                'role' => UserRole::Sponsor,
            ],
            [
                'name' => 'Accounting Clerk',
                'email' => 'accounting@sponsorflow.test',
                'role' => UserRole::Accounting,
            ],
            [
                'name' => 'System Admin',
                'email' => 'admin@sponsorflow.test',
                'role' => UserRole::Admin,
            ],
        ];

        $users = [];

        foreach ($accounts as $account) {
            $users[$account['role']->value] = User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => 'password123',
                    'role' => $account['role'],
                    'status' => UserStatus::Active,
                    'email_verified_at' => now(),
                ],
            );
        }

        $this->seedStudentProfile($users[UserRole::Student->value]);
        $this->seedSponsorPrograms($users[UserRole::Sponsor->value]);
    }

    private function seedStudentProfile(User $student): void
    {
        StudentProfile::query()->updateOrCreate(
            ['user_id' => $student->id],
            [
                'student_id_number' => '2024-00001',
                'course' => 'Bachelor of Science in Information Technology',
                'year_level' => 3,
                'birthdate' => '2004-06-15',
                'address' => 'Purok 2, Barangay San Isidro, Mati City, Davao Oriental',
                'barangay' => 'San Isidro',
                'is_rural' => true,
                'is_sle_fhe_verified' => false,
                'active_sponsorship_id' => null,
            ],
        );
    }

    private function seedSponsorPrograms(User $sponsorUser): void
    {
        $sponsor = Sponsor::query()->updateOrCreate(
            ['user_id' => $sponsorUser->id],
            [
                'company_organization_name' => 'Provincial Merit Foundation',
                'contact_person' => $sponsorUser->name,
                'contact_email' => $sponsorUser->email,
            ],
        );

        $programs = [
            [
                'program_name' => 'Rural Scholars Group Grant',
                'category' => ProgramCategory::Group,
                'available_slots' => 25,
                'status' => ProgramStatus::Open,
                'min_gpa' => 2.50,
                'target_course' => null,
                'address_requirement' => 'Rural barangay in Davao Oriental',
            ],
            [
                'program_name' => 'IT Merit Individual Scholarship',
                'category' => ProgramCategory::Individual,
                'available_slots' => 10,
                'status' => ProgramStatus::Open,
                'min_gpa' => 1.75,
                'target_course' => 'Bachelor of Science in Information Technology',
                'address_requirement' => null,
            ],
            [
                'program_name' => 'Foundation Employee Dependents Grant',
                'category' => ProgramCategory::EmployeeBased,
                'available_slots' => 8,
                'status' => ProgramStatus::Open,
                'min_gpa' => 2.00,
                'target_course' => null,
                'address_requirement' => null,
            ],
            [
                'program_name' => 'Coastal Barangay Closed Batch',
                'category' => ProgramCategory::Group,
                'available_slots' => 15,
                'status' => ProgramStatus::Closed,
                'min_gpa' => 2.25,
                'target_course' => null,
                'address_requirement' => 'Coastal barangay in Davao Oriental',
            ],
            [
                'program_name' => 'AY 2024 Expired Merit Grant',
                'category' => ProgramCategory::Individual,
                'available_slots' => 5,
                'status' => ProgramStatus::Expired,
                'min_gpa' => 1.50,
                'target_course' => 'Bachelor of Science in Education',
                'address_requirement' => null,
            ],
        ];

        foreach ($programs as $program) {
            SponsorshipProgram::query()->updateOrCreate(
                [
                    'sponsor_id' => $sponsor->id,
                    'program_name' => $program['program_name'],
                ],
                $program,
            );
        }
    }
}
