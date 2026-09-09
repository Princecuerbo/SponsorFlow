<?php

namespace Database\Seeders;

use App\Enums\ProgramCategory;
use App\Enums\ProgramStatus;
use App\Enums\UserRole;
use App\Models\AcademicProgram;
use App\Models\Sponsor;
use App\Models\SponsorshipProgram;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SponsorshipProgramSeeder extends Seeder
{
    /**
     * Seed the 5 initial sponsorship programs from the sponsor account.
     */
    public function run(): void
    {
        $sponsorUser = User::query()->where('role', UserRole::Sponsor)->first();

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
                'total_slots' => 25,
                'available_slots' => 25,
                'status' => ProgramStatus::Open,
                'min_gpa' => 2.50,
                'target_course' => null,
                'address_requirement' => 'Rural barangay in Davao Oriental',
            ],
            [
                'program_name' => 'IT Merit Individual Sponsorship',
                'category' => ProgramCategory::Individual,
                'total_slots' => 10,
                'available_slots' => 10,
                'status' => ProgramStatus::Open,
                'min_gpa' => 1.75,
                'target_course' => 'Bachelor of Science in Information Technology',
                'address_requirement' => null,
            ],
            [
                'program_name' => 'Foundation Employee Dependents Grant',
                'category' => ProgramCategory::EmployeeBased,
                'total_slots' => 8,
                'available_slots' => 8,
                'status' => ProgramStatus::Open,
                'min_gpa' => 2.00,
                'target_course' => null,
                'address_requirement' => null,
            ],
            [
                'program_name' => 'Coastal Barangay Closed Batch',
                'category' => ProgramCategory::Group,
                'total_slots' => 15,
                'available_slots' => 15,
                'status' => ProgramStatus::Closed,
                'min_gpa' => 2.25,
                'target_course' => null,
                'address_requirement' => 'Coastal barangay in Davao Oriental',
            ],
            [
                'program_name' => 'AY 2024 Expired Merit Grant',
                'category' => ProgramCategory::Individual,
                'total_slots' => 5,
                'available_slots' => 5,
                'status' => ProgramStatus::Expired,
                'min_gpa' => 1.50,
                'target_course' => 'Bachelor of Science in Education',
                'address_requirement' => null,
            ],
        ];

        // Rename any existing record that still carries the old "Scholarship" label.
        DB::table('sponsorship_programs')
            ->where('program_name', 'IT Merit Individual Scholarship')
            ->update(['program_name' => 'IT Merit Individual Sponsorship']);

        // Backfill total_slots for any live records where it was never set.
        // Uses available_slots as a conservative baseline; slot utilization in
        // the reports KPI requires total_slots > 0 to produce a meaningful %.
        DB::table('sponsorship_programs')
            ->where('total_slots', 0)
            ->update(['total_slots' => DB::raw('available_slots')]);

        foreach ($programs as $program) {
            $createdProgram = SponsorshipProgram::query()->updateOrCreate(
                [
                    'sponsor_id' => $sponsor->id,
                    'program_name' => $program['program_name'],
                ],
                $program,
            );

            if ($createdProgram->program_name === 'IT Merit Individual Sponsorship') {
                $bsit = AcademicProgram::query()->where('code', 'BSIT')->first();
                if ($bsit && $createdProgram->academicPrograms()->doesntExist()) {
                    $createdProgram->academicPrograms()->sync([$bsit->program_id]);
                }
            }
        }
    }
}