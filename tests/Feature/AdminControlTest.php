<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\DatabaseBackup;
use App\Models\Sponsor;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
        Storage::fake('local');
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->from(route('admin.dashboard'))
            ->post(route('admin.backups.store'))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success');

        $backup = DatabaseBackup::query()->latest('id')->firstOrFail();
        Storage::disk('local')->assertExists($backup->file_path);
        $this->assertSame(strlen(Storage::disk('local')->get($backup->file_path)), $backup->file_size);
        $this->get(route('admin.backups.download', $backup))->assertDownload($backup->file_name);
    }

    public function test_backup_run_route_creates_snapshot_without_shell_dependency(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->from(route('admin.dashboard'))
            ->post(route('admin.backup.run'))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success');
    }

    public function test_admin_cannot_demote_or_deactivate_self_via_update_or_status_routes(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $original = $admin->fresh()->getRawOriginal();
        $base = ['name' => 'Must not be saved', 'email' => $admin->email, 'role' => 'admin'];

        foreach ([['role' => 'student'], ['is_active' => false]] as $change) {
            $this->actingAs($admin)->put(route('admin.users.update', $admin), array_replace($base, $change))
                ->assertSessionHasErrors('account');
            $this->assertSame($original, $admin->fresh()->getRawOriginal());
        }
        $this->post(route('admin.users.toggle', $admin))->assertSessionHasErrors('account');
        $this->assertSame(UserStatus::Active, $admin->fresh()->status);
        $this->assertSame(0, AuditLog::where('action', 'admin.user.updated')->count());
    }

    public function test_editing_a_sponsor_login_preserves_existing_organization_contacts(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $account = User::factory()->create(['role' => UserRole::Sponsor]);
        $profile = Sponsor::create(['user_id' => $account->id, 'company_organization_name' => 'Recorded Organization',
            'contact_person' => 'Recorded Contact', 'contact_email' => 'contact@example.test']);
        $original = $profile->fresh()->getRawOriginal();

        $this->actingAs($admin)->put(route('admin.users.update', $account), [
            'name' => 'New Login Name', 'email' => 'new-login@example.test', 'role' => 'sponsor',
            'password' => 'new-password123', 'password_confirmation' => 'new-password123',
        ])->assertSessionHasNoErrors();

        $this->assertSame($original, $profile->fresh()->getRawOriginal());
        $details = AuditLog::where('action', 'admin.user.updated')->latest('id')->value('details');
        $this->assertStringNotContainsString('new-password123', $details);
        $this->assertStringNotContainsString($account->fresh()->password, $details);
        $this->assertTrue(json_decode($details, true)['password_changed']);
    }

    public function test_settings_reject_invalid_or_unknown_values_without_partial_updates(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        foreach (['maintenance_mode' => 'false', 'max_login_attempts' => '5'] as $key => $value) {
            SystemSetting::updateOrCreate(['setting_key' => $key], ['setting_value' => $value]);
        }
        $this->actingAs($admin)->post(route('admin.settings.update'), ['settings' => [
            'maintenance_mode' => 'true', 'max_login_attempts' => 'invalid', 'unknown' => 'injected',
        ]])->assertSessionHasErrors();
        $this->assertSame('false', SystemSetting::get('maintenance_mode'));
        $this->assertSame('5', SystemSetting::get('max_login_attempts'));
        $this->assertSame(0, AuditLog::where('action', 'admin.settings.updated')->count());
    }

    public function test_supported_settings_save_and_audit_together(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        SystemSetting::updateOrCreate(['setting_key' => 'max_login_attempts'], ['setting_value' => '5']);
        $this->actingAs($admin)->post(route('admin.settings.update'), ['settings' => ['max_login_attempts' => '7']])
            ->assertSessionHasNoErrors()->assertSessionHas('status');
        $this->assertSame('7', SystemSetting::get('max_login_attempts'));
        $this->assertSame(1, AuditLog::where('action', 'admin.settings.updated')->count());
    }

    public function test_single_login_creates_one_audit_record(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $before = AuditLog::where('action', 'admin.user.login')->count();
        Auth::guard('web')->login($admin);
        $this->assertSame($before + 1, AuditLog::where('action', 'admin.user.login')->count());
    }

    public function test_restore_is_blocked_and_does_not_execute_snapshot_sql(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Storage::disk('local')->put('backups/fixture.sql', 'DELETE FROM users;');
        $backup = DatabaseBackup::create(['file_name' => 'fixture.sql', 'file_path' => 'backups/fixture.sql',
            'file_size' => 18, 'created_by_user_id' => $admin->id, 'status' => 'completed', 'created_at' => now()]);
        $before = User::count();
        $this->actingAs($admin)->post(route('admin.backups.restore', $backup))->assertSessionHasErrors('backup');
        $this->assertSame($before, User::count());
        Storage::disk('local')->assertExists('backups/fixture.sql');
    }

    public function test_non_admin_cannot_access_admin_operations(): void
    {
        $student = User::factory()->create(['role' => UserRole::Student]);
        $this->actingAs($student)->get(route('admin.users.index'))->assertForbidden();
        $this->post(route('admin.settings.update'), ['settings' => ['maintenance_mode' => 'true']])->assertForbidden();
        $this->post(route('admin.backups.store'))->assertForbidden();
    }

    public function test_invalid_audit_dates_return_validation_errors(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin)->get(route('admin.audit-logs.index', ['from' => 'invalid']))->assertSessionHasErrors('from');
        $this->get(route('admin.audit-logs.index', ['from' => '2026-11-13', 'to' => '2026-11-01']))->assertSessionHasErrors('to');
    }

    public function test_user_model_exposes_is_active_contractor_and_settings_lookup(): void
    {
        $user = User::make(['status' => UserStatus::Active]);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->isActive());

        SystemSetting::query()->updateOrCreate(
            ['setting_key' => 'maintenance_mode'],
            ['setting_value' => 'true', 'description' => 'Maintenance mode'],
        );

        $this->assertSame('true', SystemSetting::get('maintenance_mode'));
        $this->assertSame('maintenance_mode', SystemSetting::query()->where('setting_key', 'maintenance_mode')->value('setting_key'));
    }
}
