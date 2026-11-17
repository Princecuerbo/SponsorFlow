<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPortalSidebarLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function createStudentWithProfile(): array
    {
        $student = User::factory()->create([
            'role' => UserRole::Student,
            'name' => 'Maria Santos',
            'privacy_consent_at' => now(),
        ]);

        $profile = StudentProfile::factory()->verified()->create([
            'user_id' => $student->id,
            'first_name' => 'Maria',
            'last_name' => 'Santos',
        ]);

        return [$student, $profile];
    }

    public function test_student_dashboard_renders_desktop_sidebar_and_preserves_mobile_navigation(): void
    {
        [$student] = $this->createStudentWithProfile();

        $response = $this->actingAs($student)
            ->withSession(['data_privacy_consented' => true, 'privacy_consented_session' => true])
            ->get(route('student.dashboard'));

        $response->assertOk();

        // Check body has student portal class
        $response->assertSee('sf-student-portal', false);

        // Check desktop sidebar is rendered with SponsorFlow branding
        $response->assertSee('sf-student-sidebar', false);
        $response->assertSee('sf-student-brand', false);
        $response->assertSee('SponsorFlow');

        // Check desktop top header is rendered with title and user controls
        $response->assertSee('sf-student-header', false);
        $response->assertSee('studentSidebarToggle', false);
        $response->assertSee('studentDesktopAccountMenu', false);
        $response->assertSee('Student Applicant');

        // Check mobile navigation wrapper is present with original navbar
        $response->assertSee('sf-student-mobile-navigation', false);
        $response->assertSee('mobileMenuDropdown', false);

        // Check all 6 student routes are present
        $response->assertSee(route('student.dashboard'));
        $response->assertSee(route('student.sle-fhe'));
        $response->assertSee(route('student.programs.index'));
        $response->assertSee(route('student.applications.index'));
        $response->assertSee(route('notifications.index'));
        $response->assertSee(route('student.profile'));

        // Check Sign Out form is present
        $response->assertSee('sf-student-sign-out', false);
        $response->assertSee(route('logout'));

        // Check Dashboard active link
        $content = $response->getContent();
        $this->assertStringContainsString('is-active', $content);
        $this->assertStringContainsString('aria-current="page"', $content);
    }

    public function test_active_highlighting_works_across_student_pages(): void
    {
        [$student, $profile] = $this->createStudentWithProfile();

        // 1. SLE-FHE Verification
        $response = $this->actingAs($student)
            ->withSession(['data_privacy_consented' => true, 'privacy_consented_session' => true])
            ->get(route('student.sle-fhe'));
        $response->assertOk();
        $response->assertSee('SLE-FHE Verification');

        // 2. Sponsorship Opportunities
        $response = $this->actingAs($student)
            ->withSession(['data_privacy_consented' => true, 'privacy_consented_session' => true])
            ->get(route('student.programs.index'));
        $response->assertOk();
        $response->assertSee('Sponsorship Opportunities');

        // 3. My Applications
        $response = $this->actingAs($student)
            ->withSession(['data_privacy_consented' => true, 'privacy_consented_session' => true])
            ->get(route('student.applications.index'));
        $response->assertOk();
        $response->assertSee('My Applications');

        // 4. My Profile
        $response = $this->actingAs($student)
            ->withSession(['data_privacy_consented' => true, 'privacy_consented_session' => true])
            ->get(route('student.profile'));
        $response->assertOk();
        $response->assertSee('My Profile');

        // 5. Notifications
        $response = $this->actingAs($student)
            ->withSession(['data_privacy_consented' => true, 'privacy_consented_session' => true])
            ->get(route('notifications.index'));
        $response->assertOk();
        $response->assertSee('Notifications');
    }

    public function test_application_detail_child_page_highlights_my_applications(): void
    {
        [$student, $profile] = $this->createStudentWithProfile();
        $program = SponsorshipProgram::factory()->create();
        $application = Application::factory()->create([
            'student_profile_id' => $profile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Pending,
        ]);

        $response = $this->actingAs($student)
            ->withSession(['data_privacy_consented' => true, 'privacy_consented_session' => true])
            ->get(route('student.applications.show', $application));

        $response->assertOk();
        $response->assertSee('sf-student-sidebar', false);

        // Verify "My Applications" navigation link is active on application detail view
        $content = $response->getContent();
        $this->assertStringContainsString('My Applications', $content);
        $this->assertMatchesRegularExpression(
            '/<a href="[^"]*student\/applications"[^>]*class="[^"]*is-active[^"]*"[^>]*aria-current="page"/i',
            $content
        );
    }

    public function test_fassg_role_is_not_affected_by_student_sidebar_layout(): void
    {
        $fassgUser = User::factory()->create([
            'role' => UserRole::Fassg,
            'privacy_consent_at' => now(),
        ]);

        $response = $this->actingAs($fassgUser)
            ->withSession(['data_privacy_consented' => true, 'privacy_consented_session' => true])
            ->get(route('fassg.dashboard'));

        $response->assertOk();

        // Body must NOT have sf-student-portal class
        $this->assertStringNotContainsString('sf-student-portal', $response->getContent());

        // Must NOT render sf-student-sidebar or sf-student-header
        $response->assertDontSee('sf-student-sidebar', false);
        $response->assertDontSee('sf-student-header', false);
        $response->assertDontSee('sf-student-mobile-navigation', false);

        // Standard navbar must be rendered
        $response->assertSee('sf-navbar', false);
    }

    public function test_admin_role_is_not_affected_by_student_sidebar_layout(): void
    {
        $adminUser = User::factory()->create([
            'role' => UserRole::Admin,
            'privacy_consent_at' => now(),
        ]);

        $response = $this->actingAs($adminUser)
            ->withSession(['data_privacy_consented' => true, 'privacy_consented_session' => true])
            ->get(route('admin.dashboard'));

        $response->assertOk();

        // Body must NOT have sf-student-portal class
        $this->assertStringNotContainsString('sf-student-portal', $response->getContent());

        // Must NOT render sf-student-sidebar or sf-student-header
        $response->assertDontSee('sf-student-sidebar', false);
        $response->assertDontSee('sf-student-header', false);
        $response->assertDontSee('sf-student-mobile-navigation', false);

        // Standard navbar must be rendered
        $response->assertSee('sf-navbar', false);
    }
}
