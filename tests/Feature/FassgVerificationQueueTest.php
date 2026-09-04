<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FassgVerificationQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_student_profiles_are_visible_in_the_queue(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $profile = StudentProfile::factory()->create(['is_sle_fhe_verified' => false]);

        $this->actingAs($fassg)
            ->get(route('fassg.verification.index'))
            ->assertOk()
            ->assertSee($profile->student_id_number)
            ->assertSee('Verify &amp; Approve SLE-FHE', false);
    }

    public function test_fassg_can_verify_a_student_and_audit_the_action(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $profile = StudentProfile::factory()->create(['is_sle_fhe_verified' => false]);

        $this->actingAs($fassg)
            ->post(route('fassg.verification.students.verify', $profile))
            ->assertSessionHas('success', 'Student SLE-FHE status verified successfully.');

        $this->assertTrue($profile->fresh()->is_sle_fhe_verified);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $fassg->id,
            'action' => 'fassg.student.sle_fhe_verified',
            'target_module' => 'student_profiles',
        ]);
    }
}