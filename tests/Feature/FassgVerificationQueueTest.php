<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AcademicProgram;
use App\Models\AuditLog;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FassgVerificationQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_queue_can_be_filtered_by_academic_program(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $it = AcademicProgram::factory()->create(['name' => 'BS Information Technology']);
        $cs = AcademicProgram::factory()->create(['name' => 'BS Computer Science']);

        $itProfile = StudentProfile::factory()->create([
            'is_sle_fhe_verified' => false,
            'academic_program_id' => $it->program_id,
            'student_id_number' => '2026-00011',
        ]);
        $csProfile = StudentProfile::factory()->create([
            'is_sle_fhe_verified' => false,
            'academic_program_id' => $cs->program_id,
            'student_id_number' => '2026-00022',
        ]);

        $this->actingAs($fassg)
            ->get(route('fassg.verification.index', ['academic_program_id' => $it->program_id]))
            ->assertOk()
            ->assertSee($itProfile->student_id_number)
            ->assertDontSee($csProfile->student_id_number);
    }

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