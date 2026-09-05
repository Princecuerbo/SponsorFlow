<?php

namespace Tests\Feature\Fassg;

use App\Enums\ApplicationStatus;
use App\Enums\ProgramCategory;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_fassg_can_view_all_report_analytics(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $program = SponsorshipProgram::factory()->create([
            'program_name' => 'Analytics Grant',
            'category' => ProgramCategory::Individual,
            'total_slots' => 10,
            'available_slots' => 8,
        ]);
        $profile = StudentProfile::factory()->create([
            'gender' => 'Female',
            'course' => 'Information Technology',
            'year_level' => 3,
            'barangay' => 'San Isidro',
        ]);
        $application = Application::factory()->create([
            'student_profile_id' => $profile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Approved,
            'submitted_at' => '2026-08-15 10:00:00',
            'approved_at' => '2026-08-20 10:00:00',
        ]);
        $pendingProfile = StudentProfile::factory()->create([
            'gender' => 'Male',
            'course' => 'Business Administration',
            'year_level' => 2,
            'barangay' => 'Central',
        ]);
        Application::factory()->create([
            'student_profile_id' => $pendingProfile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Pending,
            'submitted_at' => '2026-08-20 10:00:00',
        ]);

        $this->actingAs($fassg)
            ->get(route('fassg.reports.index'))
            ->assertOk()
            ->assertViewHas('applicantTrends', fn(array $trends): bool => $trends === [
                '2026-08' => 2,
            ])
            ->assertViewHas('genderDistribution', fn(array $genders): bool => $genders === [
                'Female' => 1,
                'Male' => 1,
            ])
            ->assertViewHas('slotUtilization', function (array $utilization) use ($program): bool {
                return $utilization[0]['program_name'] === $program->program_name
                    && $utilization[0]['total_slots'] === 10
                    && $utilization[0]['filled_slots'] === 1
                    && $utilization[0]['available_slots'] === 8
                    && $utilization[0]['utilization_pct'] === 10.0;
            })
            ->assertViewHas('demographics', function (array $demographics): bool {
                return $demographics['by_course']['Information Technology'] === 1
                    && $demographics['by_year_level'][3] === 1
                    && $demographics['by_barangay']['San Isidro'] === 1;
            });

        $this->assertSame(ApplicationStatus::Approved, $application->fresh()->status);
    }
}
