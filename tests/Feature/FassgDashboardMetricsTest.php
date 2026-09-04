<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ProgramStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FassgDashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_fassg_dashboard_displays_live_metrics_and_status_breakdown(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $verifiedProfile = StudentProfile::factory()->create(['is_sle_fhe_verified' => true]);
        $applicantProfile = StudentProfile::factory()->create(['is_sle_fhe_verified' => false]);
        $program = SponsorshipProgram::factory()->create(['status' => ProgramStatus::Open]);

        Application::factory()->create([
            'student_profile_id' => $applicantProfile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Pending,
        ]);
        Application::factory()->create([
            'student_profile_id' => $verifiedProfile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Approved,
            'approved_at' => now(),
        ]);

        $this->actingAs($fassg)
            ->get(route('fassg.dashboard'))
            ->assertOk()
            ->assertSee('Total Applicants')
            ->assertSee('2')
            ->assertSee('Verified SLE-FHE')
            ->assertSee('Active Programs')
            ->assertSee('Confirmed Beneficiaries')
            ->assertSee('Pending')
            ->assertSee('Approved');
    }

    public function test_fassg_dashboard_displays_expired_programs_as_expired(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        SponsorshipProgram::factory()->create([
            'program_name' => 'LIMBERT',
            'status' => ProgramStatus::Expired,
            'available_slots' => 0,
        ]);

        $this->actingAs($fassg)
            ->get(route('fassg.dashboard'))
            ->assertOk()
            ->assertSee('LIMBERT')
            ->assertSee('Expired');
    }
}
