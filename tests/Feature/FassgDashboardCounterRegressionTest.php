<?php

namespace Tests\Feature;

use App\Enums\ProgramStatus;
use App\Enums\UserRole;
use App\Models\SleFheRejection;
use App\Models\SleFheRequest;
use App\Models\SleFheVerification;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FassgDashboardCounterRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_sle_fhe_counter_matches_actionable_request_queue(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);

        $pending = $this->profile('2026-90001');
        $this->requestFor($pending, 'pending');

        $noRequest = $this->profile('2026-90002');

        $rejected = $this->profile('2026-90003');
        $this->requestFor($rejected, 'rejected');
        $this->rejectionFor($rejected, $fassg);

        $previouslyVerified = $this->profile('2026-90004');
        $this->requestFor($previouslyVerified, 'verified');
        SleFheVerification::create([
            'student_profile_id' => $previouslyVerified->id,
            'verified_address' => 'Purok 1, Mati City',
            'verified_by' => $fassg->id,
            'verified_at' => now(),
        ]);

        $historicalRequests = $this->profile('2026-90005');
        $this->requestFor($historicalRequests, 'rejected');
        $this->rejectionFor($historicalRequests, $fassg);
        $this->requestFor($historicalRequests, 'pending');

        $dashboard = $this->actingAs($fassg)->get(route('fassg.dashboard'));
        $dashboard->assertOk();

        $queue = $this->actingAs($fassg)->get(route('fassg.sle-fhe.index'));
        $queue->assertOk();

        $dashboardCount = $dashboard->viewData('pendingVerificationCount');

        $this->assertSame(2, $dashboardCount);
        $this->assertSame($queue->viewData('pendingSleFheCount'), $dashboardCount);
        $queue->assertSee($pending->student_id_number)
            ->assertSee($historicalRequests->student_id_number)
            ->assertDontSee($noRequest->student_id_number)
            ->assertDontSee($rejected->student_id_number)
            ->assertDontSee($previouslyVerified->student_id_number);
    }

    public function test_active_program_counter_counts_only_open_programs_accepting_applications(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);

        $open = SponsorshipProgram::factory()->create([
            'program_name' => 'Open Grant',
            'status' => ProgramStatus::Open,
            'available_slots' => 5,
        ]);

        SponsorshipProgram::factory()->create([
            'program_name' => 'Closed Grant',
            'status' => ProgramStatus::Closed,
            'available_slots' => 5,
        ]);

        SponsorshipProgram::factory()->create([
            'program_name' => 'Expired Grant',
            'status' => ProgramStatus::Expired,
            'available_slots' => 5,
        ]);

        $zeroSlots = SponsorshipProgram::factory()->create([
            'program_name' => 'Zero Slot Grant',
            'status' => ProgramStatus::Open,
            'total_slots' => 0,
            'available_slots' => 0,
        ]);

        $pastEndDate = SponsorshipProgram::factory()->create([
            'program_name' => 'Past End Date Grant',
            'status' => ProgramStatus::Open,
            'available_slots' => 5,
            'end_date' => now()->subDay()->toDateString(),
        ]);

        $this->assertSame(0, (int) $zeroSlots->fresh()->getRawOriginal('available_slots'));

        $dashboard = $this->actingAs($fassg)->get(route('fassg.dashboard'));
        $dashboard->assertOk();

        $this->assertSame(1, $dashboard->viewData('stats')['active_programs']);

        $this->assertSame(ProgramStatus::Open, $open->fresh()->status);
        $this->assertSame(ProgramStatus::Open, $zeroSlots->fresh()->status);
        $this->assertSame(ProgramStatus::Closed, $zeroSlots->fresh()->effective_status);
        $this->assertSame(ProgramStatus::Expired, $pastEndDate->fresh()->status);

        $programs = $this->actingAs($fassg)->get(route('fassg.programs.index'));
        $programs->assertOk();
        $this->assertCount(5, $programs->viewData('programs'));
    }

    private function profile(string $studentId): StudentProfile
    {
        return StudentProfile::factory()->create([
            'student_id_number' => $studentId,
        ]);
    }

    private function requestFor(StudentProfile $profile, string $status): SleFheRequest
    {
        return SleFheRequest::create([
            'student_profile_id' => $profile->id,
            'province' => 'Davao Oriental',
            'municipality_city' => 'City of Mati',
            'barangay' => 'Central',
            'street_purok' => 'Purok 1',
            'status' => $status,
            'submitted_at' => now(),
        ]);
    }

    private function rejectionFor(StudentProfile $profile, User $fassg): SleFheRejection
    {
        return SleFheRejection::create([
            'student_profile_id' => $profile->id,
            'rejection_reason' => 'Returned for correction',
            'rejected_by' => $fassg->id,
            'rejected_at' => now(),
        ]);
    }
}
