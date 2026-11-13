<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\SleFheRegistry;
use App\Models\SleFheRejection;
use App\Models\SleFheRequest;
use App\Models\SleFheVerification;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
            'student_id_number' => '2026-90001',
        ]);
        $this->createPendingRequest($queuedProfile);

        $excludedProfile = StudentProfile::factory()->create([
            'student_id_number' => '2026-90002',
        ]);
        $this->createPendingRequest($excludedProfile);
        SleFheVerification::create(['student_profile_id' => $excludedProfile->id, 'verified_at' => now()]);

        $noRequestProfile = StudentProfile::factory()->create([
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
            'student_id_number' => '2026-90011',
        ]);
        $this->createPendingRequest($pending);

        $superseded = StudentProfile::factory()->create([
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
            ->assertSee('viewStudentModal-' . $request->id, false)
            ->assertSee($profile->student_id_number)
            ->assertSee('Davao de Oro')
            ->assertSee('Nabunturan')
            ->assertSee('Poblacion')
            ->assertSee('Purok 3');
    }

    public function test_fassg_can_approve_pending_request(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $profile = StudentProfile::factory()->create();
        $request = $this->createPendingRequest($profile);
        SleFheRegistry::create(['student_id' => $profile->student_id_number]);

        $this->actingAs($fassg)
            ->post(route('fassg.sle-fhe.verify', $profile), ['verification_request_id' => $request->id, 'confirm_identity' => 1])
            ->assertSessionHas('success', 'Student SLE-FHE status verified successfully.');

        $this->assertTrue($profile->fresh()->sleFheVerification()->exists());
        $this->assertDatabaseHas('sle_fhe_requests', ['student_profile_id' => $profile->id, 'status' => 'verified']);
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
        $profile = StudentProfile::factory()->create();
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
        $profile = StudentProfile::factory()->create();
        $this->createPendingRequest($profile);

        $this->actingAs($fassg)
            ->post(route('fassg.sle-fhe.reject', $profile), ['reason' => 'Student not in the official SLE-FHE masterlist'])
            ->assertSessionHas('status', 'Student verification was returned for correction. Original request evidence was preserved.');

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

    public function test_queue_flags_master_registry_match_without_auto_approving(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);

        $matched = StudentProfile::factory()->create([
            'student_id_number' => '2025-0001',
        ]);
        $this->createPendingRequest($matched);

        $unmatched = StudentProfile::factory()->create([
            'student_id_number' => '2025-9999',
        ]);
        $this->createPendingRequest($unmatched);

        SleFheRegistry::create([
            'student_id' => '2025-0001',
            'sex' => 'Female',
            'civil_status' => 'Single',
            'purok' => 'Purok 2',
            'barangay' => 'San Isidro',
            'municipality' => 'Mati City',
            'course' => 'BSIT',
            'year_level' => '3',
            'campus' => 'Main Campus (City of Mati)',
            'district' => 1,
        ]);

        $this->actingAs($fassg)
            ->get(route('fassg.sle-fhe.index'))
            ->assertOk()
            ->assertSee('Master Registry Match Found')
            ->assertSee('Mati City');

        $this->assertNull($matched->fresh()->sleFheVerification);
        $this->assertNull($unmatched->fresh()->sleFheVerification);
        $this->assertSame('pending', $matched->fresh()->sleFheRequest?->status);
    }

    public function test_registry_scope_by_student_id_matches_exactly(): void
    {
        $record = SleFheRegistry::create([
            'student_id' => '2025-4242',
            'municipality' => 'Baganga',
            'district' => 2,
        ]);

        $this->assertTrue(SleFheRegistry::query()->byStudentId(' 2025-4242 ')->exists());
        $this->assertTrue(SleFheRegistry::query()->byStudentId('2025-4242')->first()->is($record));
        $this->assertFalse(SleFheRegistry::query()->byStudentId('2025-424')->exists());
        $this->assertFalse(SleFheRegistry::query()->byStudentId('')->exists());
        $this->assertTrue($record->fresh()->is_verified);
        $this->assertSame(2, $record->district);
    }

    public function test_verify_modal_shows_registry_details_when_the_student_id_matches(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);

        $profile = StudentProfile::factory()->create(['student_id_number' => '2025-4404']);
        $this->createPendingRequest($profile, [
            'province' => 'Davao Oriental',
            'municipality_city' => 'Baganga',
            'barangay' => 'Mabini',
            'street_purok' => 'Purok 3',
        ]);

        SleFheRegistry::create([
            'student_id' => '2025-4404',
            'sex' => 'Female',
            'civil_status' => 'Single',
            'course' => 'BS Information Technology',
            'year_level' => '2',
            'campus' => 'DOrSU Baganga Extension Campus',
            'municipality' => 'Baganga',
            'district' => 1,
        ]);

        $response = $this->actingAs($fassg)->get(route('fassg.sle-fhe.index'));

        $response->assertOk()
            ->assertSee('Verify SLE-FHE Eligibility')
            ->assertSee('Student Name:')
            ->assertSee('Submitted Student ID:')
            ->assertSee('Submitted Address:')
            ->assertSee('Purok 3, Mabini, Baganga, Davao Oriental')
            ->assertSee('Master Registry Match Result:')
            ->assertSee('MATCH FOUND IN SLE-FHE REGISTRY')
            ->assertSee('Registry Campus:')
            ->assertSee('DOrSU Baganga Extension Campus')
            ->assertSee('Registry Course:')
            ->assertSee('BS Information Technology')
            ->assertSee('Registry Address:')
            ->assertDontSee('NO MATCH FOUND IN SLE-FHE REGISTRY')
            ->assertDontSee('override_registry_mismatch');

        $content = $response->getContent();

        // Applicant details and the registry result are separated by a horizontal rule.
        $this->assertStringContainsString('<hr>', $content);

        // All three footer actions are present, and confirmation is enabled on a match.
        $this->assertStringContainsString('>Cancel</button>', $content);
        $this->assertStringContainsString('Reject Application', $content);
        $this->assertStringContainsString('Confirm Verification', $content);
        $this->assertMatchesRegularExpression('/data-confirm-verify\s*>/', $content);
    }

    public function test_verify_modal_blocks_confirmation_when_the_student_id_is_absent(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);

        $profile = StudentProfile::factory()->create(['student_id_number' => '2025-5555']);
        $this->createPendingRequest($profile);

        // A populated registry proves the miss is a genuine lookup failure, not an empty table.
        SleFheRegistry::create(['student_id' => '2025-0001']);

        $response = $this->actingAs($fassg)->get(route('fassg.sle-fhe.index'));

        $response->assertOk()
            ->assertSee('Verify SLE-FHE Eligibility')
            ->assertSee('Master Registry Match Result:')
            ->assertSee('NO MATCH FOUND IN SLE-FHE REGISTRY')
            ->assertSee('override_registry_mismatch')
            ->assertSee('Pre-filled rejection reason')
            ->assertSee('Student ID not found in official SLE-FHE Master Registry')
            // The registry detail labels only render on a match, so they must be absent here.
            ->assertDontSee('Registry Campus:')
            ->assertDontSee('Registry Course:')
            ->assertDontSee('Registry Address:');

        $content = $response->getContent();

        // The subtext is wrapped across source lines, so match it whitespace-tolerantly.
        $this->assertMatchesRegularExpression(
            '/2025-5555\s+does not match any of the\s+1\s+records in the official master list\./',
            $content
        );

        // Confirmation is gated behind the manual override checkbox.
        $this->assertMatchesRegularExpression('/data-confirm-verify\s+disabled/', $content);

        // The rejection textarea is pre-filled with the guidance reason.
        $this->assertStringContainsString(
            'value="Student ID not found in official SLE-FHE Master Registry"',
            $content
        );

        // All three footer actions remain available.
        $this->assertStringContainsString('>Cancel</button>', $content);
        $this->assertStringContainsString('Reject Application', $content);
        $this->assertStringContainsString('Confirm Verification', $content);

        // Rendering the queue must not verify or reject anything.
        $this->assertNull($profile->fresh()->sleFheVerification);
        $this->assertSame(0, SleFheRejection::query()->count());
    }

    public function test_registry_override_is_audited_when_verifying_a_missing_student_id(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);

        $matched = StudentProfile::factory()->create(['student_id_number' => '2025-6606']);
        $matchedRequest = $this->createPendingRequest($matched);
        SleFheRegistry::create(['student_id' => '2025-6606']);

        $this->actingAs($fassg)
            ->post(route('fassg.sle-fhe.verify', $matched), ['verification_request_id' => $matchedRequest->id, 'confirm_identity' => 1, 'override_registry_mismatch' => '1'])->assertSessionHasNoErrors();

        // A matching student is never treated as an override, even if the box is ticked.
        $this->assertDatabaseMissing('audit_logs', ['action' => 'fassg.student.sle_fhe_verified_registry_override']);
        $this->assertNotNull($matched->fresh()->sleFheVerification);

        $unmatched = StudentProfile::factory()->create(['student_id_number' => '2025-7707']);
        $unmatchedRequest = $this->createPendingRequest($unmatched);

        $this->actingAs($fassg)
            ->post(route('fassg.sle-fhe.verify', $unmatched), ['override_registry_mismatch' => '1']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'fassg.student.sle_fhe_verified_registry_override',
            'target_module' => 'student_profiles',
            'user_id' => $fassg->id,
        ]);
        $this->assertNotNull($unmatched->fresh()->sleFheVerification);
    }

    public function test_registry_lookup_is_batched_and_never_issues_one_query_per_row(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);

        for ($i = 0; $i < 5; $i++) {
            $profile = StudentProfile::factory()->create(['student_id_number' => sprintf('2025-880%d', $i)]);
            $this->createPendingRequest($profile);
        }

        SleFheRegistry::create(['student_id' => '2025-8800']);
        SleFheRegistry::create(['student_id' => '2025-8803']);

        DB::enableQueryLog();
        $this->actingAs($fassg)->get(route('fassg.sle-fhe.index'))->assertOk();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $registryQueries = array_values(array_filter(
            $queries,
            fn(array $query): bool => str_contains($query['query'], 'sle_fhe_registries')
        ));

        // One batched whereIn for the page + one count for the "of N records" hint.
        $this->assertLessThanOrEqual(
            2,
            count($registryQueries),
            'Registry lookups must be batched into a single query, not issued per pending request.'
        );
    }

    public function test_verified_list_reads_from_sle_fhe_verifications(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);

        $verifiedProfile = StudentProfile::factory()->create([
            'student_id_number' => '2026-91001',
        ]);
        SleFheVerification::create([
            'student_profile_id' => $verifiedProfile->id,
            'verified_address' => 'Purok 2, San Isidro, Mati City, Davao Oriental',
            'verified_by' => $fassg->id,
            'verified_at' => now(),
        ]);

        $legacyProfile = StudentProfile::factory()->create([
            'student_id_number' => '2026-91002',
        ]);

        $this->actingAs($fassg)
            ->get(route('fassg.sle-fhe.verified'))
            ->assertOk()
            ->assertSee($verifiedProfile->student_id_number)
            ->assertSee('Purok 2, San Isidro, Mati City, Davao Oriental')
            ->assertDontSee($legacyProfile->student_id_number);
    }

    public function test_verification_requires_pending_request_identity_attestation_and_server_side_override_evidence(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $profile = StudentProfile::factory()->create();
        $request = $this->createPendingRequest($profile);
        $base = ['verification_request_id' => $request->id, 'confirm_identity' => 1];
        foreach ([[], ['verification_request_id' => $request->id], $base,
            $base + ['override_registry_mismatch' => 1],
            ['verification_request_id' => $request->id + 100, 'confirm_identity' => 1],
        ] as $payload) {
            $this->actingAs($fassg)->post(route('fassg.sle-fhe.verify', $profile), $payload)->assertSessionHasErrors();
            $this->assertDatabaseMissing('sle_fhe_verifications', ['student_profile_id' => $profile->id]);
            $this->assertSame('pending', $request->fresh()->status);
        }
        $this->actingAs($profile->user)->post(route('fassg.sle-fhe.verify', $profile), $base)->assertForbidden();
    }

    public function test_existing_verification_and_original_request_evidence_cannot_be_overwritten(): void
    {
        $profile = StudentProfile::factory()->verified()->create();
        $existing = $profile->sleFheVerification->getRawOriginal();
        $request = $this->createPendingRequest($profile, ['barangay' => 'Different barangay']);
        $requestBefore = $request->getRawOriginal();
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        SleFheRegistry::create(['student_id' => $profile->student_id_number]);
        $this->actingAs($fassg)->post(route('fassg.sle-fhe.verify', $profile), [
            'verification_request_id' => $request->id, 'confirm_identity' => 1,
        ])->assertSessionHasErrors('verification');
        $this->assertSame($existing, $profile->fresh()->sleFheVerification->getRawOriginal());
        $this->assertSame($requestBefore, $request->fresh()->getRawOriginal());
    }

    public function test_profile_save_and_fixed_list_matching_never_create_verification(): void
    {
        $profile = StudentProfile::factory()->create();
        \App\Models\FixedListItem::factory()->create([
            'student_id_number' => $profile->student_id_number,
            'is_sle_fhe_verified' => true,
        ]);
        $this->assertFalse($profile->syncSleFheFromFixedLists());
        $this->actingAs($profile->user)->put(route('student.verification.update'), [
            'student_id_number' => $profile->student_id_number,
            'academic_program_id' => $profile->academic_program_id,
            'year_level' => $profile->year_level,
            'course' => $profile->course,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('sle_fhe_verifications', ['student_profile_id' => $profile->id]);
        $this->assertSame('Unverified', $profile->fresh()->sle_fhe_status);
    }

    public function test_actorless_legacy_verification_is_preserved_but_not_certified(): void
    {
        $profile = StudentProfile::factory()->create();
        $verification = SleFheVerification::create(['student_profile_id' => $profile->id, 'verified_at' => now()]);
        $before = $verification->getRawOriginal();
        $this->assertFalse($profile->hasDocumentedSleFheVerification());
        $this->assertFalse($profile->syncSleFheFromFixedLists());
        $this->assertSame($before, $verification->fresh()->getRawOriginal());
        $this->assertSame('Unverified', $profile->fresh()->sle_fhe_status);
    }


    public function test_inactive_registry_entry_cannot_bypass_the_documented_override_requirement(): void
    {
        $profile = StudentProfile::factory()->create();
        $request = $this->createPendingRequest($profile);
        SleFheRegistry::create(['student_id' => $profile->student_id_number, 'is_verified' => false]);
        $this->actingAs(User::factory()->create(['role' => UserRole::Fassg]))
            ->post(route('fassg.sle-fhe.verify', $profile), ['verification_request_id' => $request->id, 'confirm_identity' => 1])
            ->assertSessionHasErrors('override_registry_mismatch');
        $this->assertDatabaseMissing('sle_fhe_verifications', ['student_profile_id' => $profile->id]);
        $this->assertSame('pending', $request->fresh()->status);
    }

}
