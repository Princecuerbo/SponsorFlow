<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\GeneratedBatchStatus;
use App\Enums\ProgramStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\BatchCandidate;
use App\Models\GeneratedBatch;
use App\Models\Sponsor;
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
        $verifiedProfile = StudentProfile::factory()->verified()->create();
        $applicantProfile = StudentProfile::factory()->create();
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

    public function test_application_progress_breakdown_satisfies_math_verification(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $sponsor = Sponsor::factory()->create();
        $program = SponsorshipProgram::factory()->create(['sponsor_id' => $sponsor->id, 'status' => ProgramStatus::Open]);

        Application::factory()->create([
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Pending,
        ]);

        Application::factory()->create([
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Verified,
        ]);

        // Verified application linked to confirmed batch
        $batch = GeneratedBatch::factory()->create([
            'sponsorship_program_id' => $program->id,
            'created_by_fassg_id' => $fassg->id,
            'status' => GeneratedBatchStatus::Approved,
            'fassg_assigned_at' => now(),
        ]);
        $confirmedBatchApp = Application::factory()->create([
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Verified,
        ]);
        BatchCandidate::factory()->create([
            'generated_batch_id' => $batch->id,
            'application_id' => $confirmedBatchApp->id,
        ]);

        Application::factory()->create([
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Rejected,
        ]);

        $response = $this->actingAs($fassg)->get(route('fassg.dashboard'));
        $response->assertOk();

        $breakdown = $response->viewData('applicationStatusBreakdown');
        $this->assertSame(1, $breakdown[ApplicationStatus::Pending->value]);
        $this->assertSame(1, $breakdown[ApplicationStatus::Verified->value]);
        $this->assertSame(1, $breakdown[ApplicationStatus::Approved->value]);
        $this->assertSame(1, $breakdown[ApplicationStatus::Rejected->value]);
        $this->assertSame(4, array_sum($breakdown));
        $this->assertSame(4, $response->viewData('stats')['total_applicants']);
    }
}
