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

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_application_populates_student_dashboard_cards_and_timeline(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);
        $student->update(['privacy_consent_at' => now()]);
        $profile = StudentProfile::factory()->create([
            'user_id' => $student->id,
            'is_sle_fhe_verified' => true,
        ]);
        $program = SponsorshipProgram::factory()->create(['program_name' => 'DORSU Completion Grant']);
        Application::factory()->create([
            'student_profile_id' => $profile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Approved,
            'approved_at' => now(),
        ]);

        $this->actingAs($student)
            ->withSession(['data_privacy_consented' => true, 'privacy_consented_session' => true])
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Active Sponsorships')
            ->assertSee('Grant Awarded')
            ->assertSee('DORSU Completion Grant')
            ->assertSee('Final Approval')
            ->assertSee('Approved &amp; Confirmed', false);
    }

    public function test_student_without_applications_sees_documents_as_not_uploaded(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);
        $student->update(['privacy_consent_at' => now()]);
        StudentProfile::factory()->create([
            'user_id' => $student->id,
            'is_sle_fhe_verified' => false,
        ]);

        $this->actingAs($student)
            ->withSession(['privacy_consented_session' => true])
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Not Uploaded')
            ->assertDontSee('[cite:');
    }

    public function test_expired_program_is_not_counted_as_an_active_sponsorship(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);
        $student->update(['privacy_consent_at' => now()]);
        $profile = StudentProfile::factory()->create([
            'user_id' => $student->id,
            'is_sle_fhe_verified' => true,
        ]);
        $program = SponsorshipProgram::factory()->create(['status' => ProgramStatus::Expired]);
        Application::factory()->create([
            'student_profile_id' => $profile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Approved,
        ]);

        $this->actingAs($student)
            ->withSession(['data_privacy_consented' => true, 'privacy_consented_session' => true])
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Active Sponsorships')
            ->assertSeeInOrder(['Active Sponsorships', '0']);
    }
}
