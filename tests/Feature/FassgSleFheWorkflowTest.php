<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\SleFheRejection;
use App\Models\SleFheRequest;
use App\Models\SleFheVerification;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FassgSleFheWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function createPendingRequest(StudentProfile $profile, array $overrides = []): SleFheRequest
    {
        return SleFheRequest::create(array_merge([
            'student_profile_id' => $profile->id,
            'province' => 'Davao Oriental',
            'municipality_city' => 'Mati City',
            'barangay' => 'San Isidro',
            'street_purok' => 'Purok 2',
            'status' => 'pending',
            'submitted_at' => now(),
        ], $overrides));
    }

    public function test_sle_fhe_queue_lists_only_pending_requests_without_verification(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);

        $queuedProfile = StudentProfile::factory()->create([
            'is_sle_fhe_verified' => false,
            'student_id_number' => '2026-90001',
        ]);
        $this->createPendingRequest($queuedProfile);

        $excludedProfile = StudentProfile::factory()->create([
            'is_sle_fhe_verified' => false,
            'student_id_number' => '2026-90002',
        ]);
        $this->createPendingRequest($excludedProfile);
        SleFheVerification::create(['student_profile_id' => $excludedProfile->id, 'verified_at' => now()]);

        $noRequestProfile = StudentProfile::factory()->create([
            'is_sle_fhe_verified' => false,
            'student_id_number' => '2026-90003',
        ]);

        $this->actingAs($fassg)
            ->get(route('fassg.sle-fhe.index'))
            ->assertOk()
            ->assertSee($queuedProfile->student_id_number)
            ->assertDontSee($excludedProfile->student_id_number)
            ->assertDontSee($noRequestProfile->student_id_number);
    }

    public function test_sle_fhe_queue_excludes_requests_that_are_not_pending(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);

        $pending = StudentProfile::factory()->create([
            'is_sle_fhe_verified' => false,
            'student_id_number' => '2026-90011',
        ]);
        $this->createPendingRequest($pending);

        $superseded = StudentProfile::factory()->create([
            'is_sle_fhe_verified' => false,
            'student_id_number' => '2026-90012',
        ]);
        $this->createPendingRequest($superseded, ['status' => 'rejected']);

        $this->actingAs($fassg)
            ->get(route('fassg.sle-fhe.index'))
            ->assertOk()
            ->assertSee($pending->student_id_number)
            ->assertDontSee($superseded->student_id_number);
    }

    public function test_sle_fhe_queue_exposes_student_details_and_submitted_address(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $profile = StudentProfile::factory()->create([
            'is_sle_fhe_verified' => false,
            'student_id_number' => '2026-90021',
        ]);
        $request = $this->createPendingRequest($profile, [
            'province' => 'Davao de Oro',
            'municipality_city' => 'Nabunturan',
            'barangay' => 'Poblacion',
            'street_purok' => 'Purok 3',
        ]);

        $this->actingAs($fassg)
            ->get(route('fassg.sle-fhe.index'))
            ->assertOk()
            ->assertSee('View Student Profile Details', false)
            ->assertSee('viewStudentModal-'.$request->id, false)
            ->assertSee($profile->student_id_number)
            ->assertSee('Davao de Oro')
            ->assertSee('Nabunturan')
            ->assertSee('Poblacion')
            ->assertSee('Purok 3');
    }

    public function test_fassg_can_approve_pending_request(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $profile = StudentProfile::factory()->create(['is_sle_fhe_verified' => false]);
        $this->createPendingRequest($profile);

        $this->actingAs($fassg)
            ->post(route('fassg.sle-fhe.verify', $profile))
            ->assertSessionHas('success', 'Student SLE-FHE status verified successfully.');

        $this->assertTrue($profile->fresh()->is_sle_fhe_verified);
        $this->assertDatabaseMissing('sle_fhe_requests', ['student_profile_id' => $profile->id]);
        $this->assertDatabaseHas('sle_fhe_verifications', [
            'student_profile_id' => $profile->id,
            'verified_address' => 'Purok 2, San Isidro, Mati City, Davao Oriental',
            'verified_by' => $fassg->id,
        ]);

        $verification = SleFheVerification::where('student_profile_id', $profile->id)->firstOrFail();
        $this->assertNotNull($verification->verified_at);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $fassg->id,
            'action' => 'fassg.student.sle_fhe_verified',
            'target_module' => 'student_profiles',
        ]);

        $this->actingAs($fassg)
            ->get(route('fassg.sle-fhe.index'))
            ->assertOk()
            ->assertDontSee($profile->student_id_number);
    }

    public function test_fassg_reject_requires_a_reason(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $profile = StudentProfile::factory()->create(['is_sle_fhe_verified' => false]);
        $this->createPendingRequest($profile);

        $this->actingAs($fassg)
            ->post(route('fassg.sle-fhe.reject', $profile), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseHas('sle_fhe_requests', ['student_profile_id' => $profile->id]);
        $this->assertDatabaseCount('sle_fhe_rejections', 0);
    }

    public function test_fassg_can_reject_pending_request_with_reason(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $profile = StudentProfile::factory()->create(['is_sle_fhe_verified' => false]);
        $this->createPendingRequest($profile);

        $this->actingAs($fassg)
            ->post(route('fassg.sle-fhe.reject', $profile), ['reason' => 'Student not in the official SLE-FHE masterlist'])
            ->assertSessionHas('status', 'Student verification was returned for correction.');

        $this->assertDatabaseMissing('sle_fhe_requests', ['student_profile_id' => $profile->id]);
        $this->assertDatabaseHas('sle_fhe_rejections', [
            'student_profile_id' => $profile->id,
            'rejection_reason' => 'Student not in the official SLE-FHE masterlist',
            'rejected_by' => $fassg->id,
        ]);

        $rejection = SleFheRejection::where('student_profile_id', $profile->id)->firstOrFail();
        $this->assertNotNull($rejection->rejected_at);
        $this->assertSame('Rejected', $profile->fresh()->sle_fhe_status);
    }

    public function test_verified_list_reads_from_sle_fhe_verifications(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);

        $verifiedProfile = StudentProfile::factory()->create([
            'is_sle_fhe_verified' => true,
            'student_id_number' => '2026-91001',
        ]);
        SleFheVerification::create([
            'student_profile_id' => $verifiedProfile->id,
            'verified_address' => 'Purok 2, San Isidro, Mati City, Davao Oriental',
            'verified_by' => $fassg->id,
            'verified_at' => now(),
        ]);

        $legacyProfile = StudentProfile::factory()->create([
            'is_sle_fhe_verified' => true,
            'student_id_number' => '2026-91002',
        ]);

        $this->actingAs($fassg)
            ->get(route('fassg.sle-fhe.verified'))
            ->assertOk()
            ->assertSee($verifiedProfile->student_id_number)
            ->assertSee('Purok 2, San Isidro, Mati City, Davao Oriental')
            ->assertDontSee($legacyProfile->student_id_number);
    }
}
