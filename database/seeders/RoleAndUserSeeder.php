<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
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
    }

    private function seedStudentProfile(User $student): void
    {
        StudentProfile::query()->updateOrCreate(
            ['user_id' => $student->id],
            [
                'student_id_number' => '2024-00001',
                'course' => 'Bachelor of Science in Information Technology',
                'year_level' => 3,
                'gender' => 'Male',
                'birthdate' => '2004-06-15',
                'province' => 'Davao Oriental',
                'municipality' => 'Mati City',
                'barangay' => 'San Isidro',
                'home_address' => 'Purok 2',
                'is_rural' => true,
                'is_sle_fhe_verified' => false,
                'active_sponsorship_id' => null,
            ],
        );
    }
}