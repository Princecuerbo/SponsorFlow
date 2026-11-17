<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Sponsor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffPortalSidebarLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function createFassgUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::Fassg,
            'name' => 'FASSG Officer Maria',
            'privacy_consent_at' => now(),
        ]);
    }

    private function createSponsorUser(): User
    {
        $user = User::factory()->create([
            'role' => UserRole::Sponsor,
            'name' => 'Sponsor John',
            'privacy_consent_at' => now(),
        ]);

        Sponsor::factory()->create([
            'user_id' => $user->id,
            'company_organization_name' => 'Apex Education Foundation',
        ]);

        return $user;
    }

    private function createAccountingUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::Accounting,
            'name' => 'Accounting Officer Clara',
            'privacy_consent_at' => now(),
        ]);
    }

    private function createAdminUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'name' => 'SysAdmin Roberto',
            'privacy_consent_at' => now(),
        ]);
    }

    // -------------------------------------------------------------
    // 1. FASSG PORTAL TESTS
    // -------------------------------------------------------------
    public function test_fassg_dashboard_renders_desktop_sidebar_and_preserves_mobile_navigation(): void
    {
        $fassg = $this->createFassgUser();

        $response = $this->actingAs($fassg)->get(route('fassg.dashboard'));
        $response->assertOk();

        // Check body has portal classes
        $response->assertSee('sf-staff-portal', false);
        $response->assertSee('sf-fassg-portal', false);
        $response->assertDontSee('sf-student-portal');

        // Check desktop sidebar is rendered with SponsorFlow branding
        $response->assertSee('sf-staff-sidebar', false);
        $response->assertSee('sf-staff-brand', false);
        $response->assertSee('SponsorFlow');
        $response->assertSee('FASSG Portal');

        // Check desktop top header is rendered with title and user controls
        $response->assertSee('sf-staff-header', false);
        $response->assertSee('staffSidebarToggle', false);
        $response->assertSee('staffDesktopAccountMenu', false);
        $response->assertSee('FASSG Personnel');
        $response->assertSee('FASSG Officer Maria');

        // Check mobile navigation wrapper is present with original navbar
        $response->assertSee('sf-staff-mobile-navigation', false);
        $response->assertSee('mobileMenuDropdown', false);

        // Check all 8 FASSG routes are present in sidebar
        $response->assertSee(route('fassg.dashboard'));
        $response->assertSee(route('fassg.programs.index'));
        $response->assertSee(route('fassg.sle-fhe.index'));
        $response->assertSee(route('fassg.sle-fhe.verified'));
        $response->assertSee(route('fassg.applications.index'));
        $response->assertSee(route('fassg.generated-batches.index'));
        $response->assertSee(route('fassg.fixed-lists.index'));
        $response->assertSee(route('fassg.reports.index'));

        // Check Sign Out form is present
        $response->assertSee('sf-staff-sign-out', false);
        $response->assertSee(route('logout'));

        // Check Dashboard active link
        $content = $response->getContent();
        $this->assertStringContainsString('is-active', $content);
        $this->assertStringContainsString('aria-current="page"', $content);
        $this->assertStringContainsString('sf_fassg_sidebar_collapsed', $content);
    }

    public function test_active_highlighting_works_across_fassg_pages(): void
    {
        $fassg = $this->createFassgUser();

        // 1. Programs
        $response = $this->actingAs($fassg)->get(route('fassg.programs.index'));
        $response->assertOk();
        $this->assertStringContainsString('Programs', $response->getContent());

        // 2. SLE-FHE Verification
        $response = $this->actingAs($fassg)->get(route('fassg.sle-fhe.index'));
        $response->assertOk();
        $this->assertStringContainsString('SLE-FHE Verification', $response->getContent());

        // 3. Verified Students
        $response = $this->actingAs($fassg)->get(route('fassg.sle-fhe.verified'));
        $response->assertOk();
        $this->assertStringContainsString('Verified Students', $response->getContent());

        // 4. Application Queue
        $response = $this->actingAs($fassg)->get(route('fassg.applications.index'));
        $response->assertOk();
        $this->assertStringContainsString('Application Queue', $response->getContent());

        // 5. Generated Batches
        $response = $this->actingAs($fassg)->get(route('fassg.generated-batches.index'));
        $response->assertOk();
        $this->assertStringContainsString('Generated Batches', $response->getContent());

        // 6. Fixed Lists
        $response = $this->actingAs($fassg)->get(route('fassg.fixed-lists.index'));
        $response->assertOk();
        $this->assertStringContainsString('Fixed Lists', $response->getContent());

        // 7. Reports
        $response = $this->actingAs($fassg)->get(route('fassg.reports.index'));
        $response->assertOk();
        $this->assertStringContainsString('Reports', $response->getContent());
    }

    // -------------------------------------------------------------
    // 2. SPONSOR PORTAL TESTS
    // -------------------------------------------------------------
    public function test_sponsor_dashboard_renders_desktop_sidebar_and_preserves_mobile_navigation(): void
    {
        $sponsor = $this->createSponsorUser();

        $response = $this->actingAs($sponsor)->get(route('sponsor.dashboard'));
        $response->assertOk();

        // Check body has portal classes
        $response->assertSee('sf-staff-portal', false);
        $response->assertSee('sf-sponsor-portal', false);
        $response->assertDontSee('sf-student-portal');

        // Check desktop sidebar is rendered with SponsorFlow branding
        $response->assertSee('sf-staff-sidebar', false);
        $response->assertSee('sf-staff-brand', false);
        $response->assertSee('SponsorFlow');
        $response->assertSee('Sponsor Portal');

        // Check desktop top header
        $response->assertSee('sf-staff-header', false);
        $response->assertSee('staffSidebarToggle', false);
        $response->assertSee('External Sponsor');
        $response->assertSee('Apex Education Foundation');

        // Check mobile navigation wrapper
        $response->assertSee('sf-staff-mobile-navigation', false);
        $response->assertSee('mobileMenuDropdown', false);

        // Check all 3 Sponsor routes are present
        $response->assertSee(route('sponsor.dashboard'));
        $response->assertSee(route('sponsor.approvals.index'));
        $response->assertSee(route('sponsor.approvals.history'));

        // Check Sign Out
        $response->assertSee('sf-staff-sign-out', false);

        // Check active link & storage key
        $content = $response->getContent();
        $this->assertStringContainsString('is-active', $content);
        $this->assertStringContainsString('sf_sponsor_sidebar_collapsed', $content);
    }

    public function test_active_highlighting_works_across_sponsor_pages(): void
    {
        $sponsor = $this->createSponsorUser();

        // 1. Approvals Queue
        $response = $this->actingAs($sponsor)->get(route('sponsor.approvals.index'));
        $response->assertOk();
        $this->assertStringContainsString('Approvals Queue', $response->getContent());

        // 2. Approval History
        $response = $this->actingAs($sponsor)->get(route('sponsor.approvals.history'));
        $response->assertOk();
        $this->assertStringContainsString('Approval History', $response->getContent());
    }

    // -------------------------------------------------------------
    // 3. ACCOUNTING PORTAL TESTS
    // -------------------------------------------------------------
    public function test_accounting_dashboard_renders_desktop_sidebar_and_preserves_mobile_navigation(): void
    {
        $accounting = $this->createAccountingUser();

        $response = $this->actingAs($accounting)->get(route('accounting.dashboard'));
        $response->assertOk();

        // Check body has portal classes
        $response->assertSee('sf-staff-portal', false);
        $response->assertSee('sf-accounting-portal', false);
        $response->assertDontSee('sf-student-portal');

        // Check desktop sidebar
        $response->assertSee('sf-staff-sidebar', false);
        $response->assertSee('sf-staff-brand', false);
        $response->assertSee('SponsorFlow');
        $response->assertSee('Accounting Portal');

        // Check desktop top header
        $response->assertSee('sf-staff-header', false);
        $response->assertSee('staffSidebarToggle', false);
        $response->assertSee('Accounting Personnel');
        $response->assertSee('Accounting Officer Clara');

        // Check mobile navigation wrapper
        $response->assertSee('sf-staff-mobile-navigation', false);

        // Check all 2 Accounting routes are present
        $response->assertSee(route('accounting.dashboard'));
        $response->assertSee(route('accounting.beneficiaries.index'));

        // Check active link & storage key
        $content = $response->getContent();
        $this->assertStringContainsString('is-active', $content);
        $this->assertStringContainsString('sf_accounting_sidebar_collapsed', $content);
    }

    public function test_active_highlighting_works_across_accounting_pages(): void
    {
        $accounting = $this->createAccountingUser();

        // Confirmed Beneficiaries
        $response = $this->actingAs($accounting)->get(route('accounting.beneficiaries.index'));
        $response->assertOk();
        $this->assertStringContainsString('Confirmed Beneficiaries', $response->getContent());
    }

    // -------------------------------------------------------------
    // 4. ADMIN PORTAL TESTS
    // -------------------------------------------------------------
    public function test_admin_dashboard_renders_desktop_sidebar_and_preserves_mobile_navigation(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();

        // Check body has portal classes
        $response->assertSee('sf-staff-portal', false);
        $response->assertSee('sf-admin-portal', false);
        $response->assertDontSee('sf-student-portal');

        // Check desktop sidebar
        $response->assertSee('sf-staff-sidebar', false);
        $response->assertSee('sf-staff-brand', false);
        $response->assertSee('SponsorFlow');
        $response->assertSee('Admin Portal');

        // Check desktop top header
        $response->assertSee('sf-staff-header', false);
        $response->assertSee('staffSidebarToggle', false);
        $response->assertSee('System Administrator');
        $response->assertSee('SysAdmin Roberto');

        // Check mobile navigation wrapper
        $response->assertSee('sf-staff-mobile-navigation', false);

        // Check all 5 Admin routes are present
        $response->assertSee(route('admin.dashboard'));
        $response->assertSee(route('admin.users.index'));
        $response->assertSee(route('admin.audit-logs.index'));
        $response->assertSee(route('admin.settings.index'));
        $response->assertSee(route('admin.backups.index'));

        // Check active link & storage key
        $content = $response->getContent();
        $this->assertStringContainsString('is-active', $content);
        $this->assertStringContainsString('sf_admin_sidebar_collapsed', $content);
    }

    public function test_active_highlighting_works_across_admin_pages(): void
    {
        $admin = $this->createAdminUser();

        // 1. User Accounts
        $response = $this->actingAs($admin)->get(route('admin.users.index'));
        $response->assertOk();
        $this->assertStringContainsString('User Accounts', $response->getContent());

        // 2. Audit Logs
        $response = $this->actingAs($admin)->get(route('admin.audit-logs.index'));
        $response->assertOk();
        $this->assertStringContainsString('Audit Logs', $response->getContent());

        // 3. Security Settings
        $response = $this->actingAs($admin)->get(route('admin.settings.index'));
        $response->assertOk();
        $this->assertStringContainsString('Security Settings', $response->getContent());

        // 4. Backups
        $response = $this->actingAs($admin)->get(route('admin.backups.index'));
        $response->assertOk();
        $this->assertStringContainsString('Backups', $response->getContent());
    }

    // -------------------------------------------------------------
    // 5. ROLE ISOLATION TESTS
    // -------------------------------------------------------------
    public function test_role_navigation_isolation_ensures_no_unauthorized_sidebar_links(): void
    {
        $fassg = $this->createFassgUser();
        $sponsor = $this->createSponsorUser();
        $accounting = $this->createAccountingUser();
        $admin = $this->createAdminUser();

        // FASSG cannot see Admin links in sidebar
        $resp = $this->actingAs($fassg)->get(route('fassg.dashboard'));
        $resp->assertDontSee(route('admin.users.index'));
        $resp->assertDontSee(route('admin.backups.index'));
        $resp->assertDontSee('Admin Portal');

        // Sponsor cannot see FASSG or Admin links in sidebar
        $resp = $this->actingAs($sponsor)->get(route('sponsor.dashboard'));
        $resp->assertDontSee(route('fassg.sle-fhe.index'));
        $resp->assertDontSee(route('admin.users.index'));
        $resp->assertDontSee('FASSG Portal');

        // Accounting cannot see FASSG or Admin links in sidebar
        $resp = $this->actingAs($accounting)->get(route('accounting.dashboard'));
        $resp->assertDontSee(route('fassg.programs.index'));
        $resp->assertDontSee(route('admin.settings.index'));
        $resp->assertDontSee('FASSG Portal');

        // Admin cannot see Student links in sidebar
        $resp = $this->actingAs($admin)->get(route('admin.dashboard'));
        $resp->assertDontSee(route('student.dashboard'));
        $resp->assertDontSee('Student Portal');
    }
}
