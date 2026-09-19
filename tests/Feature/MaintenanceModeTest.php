<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    private function enableMaintenanceMode(): void
    {
        SystemSetting::query()->updateOrCreate(
            ['setting_key' => 'maintenance_mode'],
            ['setting_value' => 'true', 'description' => 'Maintenance mode active']
        );
    }

    private function disableMaintenanceMode(): void
    {
        SystemSetting::query()->updateOrCreate(
            ['setting_key' => 'maintenance_mode'],
            ['setting_value' => 'false', 'description' => 'Maintenance mode inactive']
        );
    }

    public function test_when_maintenance_is_disabled_all_portals_are_accessible(): void
    {
        $this->disableMaintenanceMode();

        $this->get('/')->assertOk();
        $this->get(route('login'))->assertOk();
        $this->get(route('staff.login'))->assertOk();
        $this->get(route('admin.login'))->assertOk();
    }

    public function test_when_maintenance_is_enabled_landing_page_bypasses_check(): void
    {
        $this->enableMaintenanceMode();

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('SponsorFlow');
    }

    public function test_when_maintenance_is_enabled_admin_gate_bypasses_check(): void
    {
        $this->enableMaintenanceMode();

        $response = $this->get(route('admin.login'));
        $response->assertOk();
        $response->assertSee('Admin Sign In');
    }

    public function test_when_maintenance_is_enabled_authenticated_admin_can_access_admin_dashboard(): void
    {
        $this->enableMaintenanceMode();

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();
    }

    public function test_when_maintenance_is_enabled_student_login_shows_custom_maintenance_view(): void
    {
        $this->enableMaintenanceMode();

        $response = $this->get(route('login'));
        $response->assertStatus(503);
        $response->assertSee('System Under Maintenance');
        $response->assertSee('SponsorFlow is currently undergoing scheduled maintenance to improve services. Please check back shortly or contact the FASSG office for urgent inquiries.');
        $response->assertSee('Back to Home');
    }

    public function test_when_maintenance_is_enabled_student_registration_shows_custom_maintenance_view(): void
    {
        $this->enableMaintenanceMode();

        $response = $this->get(route('register'));
        $response->assertStatus(503);
        $response->assertSee('System Under Maintenance');
    }

    public function test_when_maintenance_is_enabled_staff_login_shows_custom_maintenance_view(): void
    {
        $this->enableMaintenanceMode();

        $response = $this->get(route('staff.login'));
        $response->assertStatus(503);
        $response->assertSee('System Under Maintenance');
        $response->assertSee('SponsorFlow is currently undergoing scheduled maintenance to improve services. Please check back shortly or contact the FASSG office for urgent inquiries.');
        $response->assertSee('Back to Home');
    }

    public function test_when_maintenance_is_enabled_portal_routes_show_custom_maintenance_view(): void
    {
        $this->enableMaintenanceMode();

        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $response = $this->actingAs($fassg)->get(route('fassg.dashboard'));
        $response->assertStatus(503);
        $response->assertSee('System Under Maintenance');

        $student = User::factory()->create(['role' => UserRole::Student]);
        $response = $this->actingAs($student)->get(route('student.dashboard'));
        $response->assertStatus(503);
        $response->assertSee('System Under Maintenance');
    }

    public function test_when_maintenance_is_enabled_json_requests_receive_503_json(): void
    {
        $this->enableMaintenanceMode();

        $response = $this->getJson(route('login'));
        $response->assertStatus(503);
        $response->assertJson([
            'message' => 'SponsorFlow is currently undergoing scheduled maintenance to improve services. Please check back shortly or contact the FASSG office for urgent inquiries.',
        ]);
    }
}
