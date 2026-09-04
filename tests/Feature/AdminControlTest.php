<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_toggle_user_accounts(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Accounting Officer',
                'email' => 'accounting@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => UserRole::Accounting->value,
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'User account created.');

        $account = User::query()->where('email', 'accounting@example.test')->firstOrFail();
        $this->assertSame(UserStatus::Active, $account->status);

        $this->actingAs($admin)
            ->post(route('admin.users.toggle', $account))
            ->assertRedirect();

        $this->assertSame(UserStatus::Inactive, $account->fresh()->status);
    }

    public function test_sponsor_profile_is_created_when_admin_creates_sponsor_account(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Sponsor Organization',
                'email' => 'sponsor@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => UserRole::Sponsor->value,
            ])
            ->assertRedirect();

        $sponsorUser = User::query()->where('email', 'sponsor@example.test')->firstOrFail();

        $this->assertDatabaseHas('sponsors', [
            'user_id' => $sponsorUser->id,
            'company_organization_name' => 'Sponsor Organization',
            'contact_person' => 'Sponsor Organization',
            'contact_email' => 'sponsor@example.test',
        ]);
    }

    public function test_sponsor_profile_is_created_when_admin_updates_user_role(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $user = User::factory()->create(['role' => UserRole::Student]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'role' => UserRole::Sponsor->value,
            ])
            ->assertRedirect();

        $this->assertSame(1, Sponsor::query()->where('user_id', $user->id)->count());
        $this->assertDatabaseHas('sponsors', [
            'user_id' => $user->id,
            'company_organization_name' => $user->name,
            'contact_email' => $user->email,
        ]);
    }

    public function test_users_page_add_button_targets_creation_modal(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('data-bs-target="#addUserModal"', false)
            ->assertSee('id="addUserModal"', false)
            ->assertSee('window.searchTimer', false)
            ->assertSee('name="role"', false)
            ->assertSee('onchange="this.form.submit()"', false);
    }

    public function test_audit_log_filter_lists_system_roles(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertSee('value="student"', false)
            ->assertSee('Student')
            ->assertSee('FASSG')
            ->assertSee('Sponsor')
            ->assertSee('Accounting')
            ->assertSee('Admin')
            ->assertSee('window.searchTimer', false)
            ->assertSee('type="date"', false)
            ->assertSee('onchange="this.form.submit()"', false);
    }

    public function test_audit_log_filter_queries_by_recorded_role(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        AuditLog::create([
            'role' => UserRole::Student->value,
            'action' => 'student.user.login',
            'target_module' => 'authentication',
        ]);
        AuditLog::create([
            'role' => UserRole::Fassg->value,
            'action' => 'fassg.user.login',
            'target_module' => 'authentication',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index', ['role' => UserRole::Student->value]))
            ->assertOk()
            ->assertSee('student.user.login')
            ->assertDontSee('fassg.user.login');
    }

    public function test_audit_log_timestamps_use_philippine_timezone(): void
    {
        $this->assertSame('Asia/Manila', config('app.timezone'));
    }

    public function test_admin_backup_store_route_creates_snapshot(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->from(route('admin.dashboard'))
            ->post(route('admin.backups.store'))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success');
    }

    public function test_backup_run_route_creates_snapshot_without_shell_dependency(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->from(route('admin.dashboard'))
            ->post(route('admin.backup.run'))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success');
    }

    public function test_user_model_exposes_is_active_contractor_and_settings_lookup(): void
    {
        $user = User::make(['status' => UserStatus::Active]);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->isActive());

        \App\Models\SystemSetting::query()->updateOrCreate(
            ['setting_key' => 'maintenance_mode'],
            ['setting_value' => 'true', 'description' => 'Maintenance mode'],
        );

        $this->assertSame('true', \App\Models\SystemSetting::get('maintenance_mode'));
        $this->assertSame('maintenance_mode', \App\Models\SystemSetting::query()->where('setting_key', 'maintenance_mode')->value('setting_key'));
    }
}
