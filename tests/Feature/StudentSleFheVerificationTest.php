<?php

namespace Tests\Feature;

use App\Enums\SleFheStatus;
use App\Models\SleFheRejection;
use App\Models\SleFheRequest;
use App\Models\SleFheVerification;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentSleFheVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsStudent(User $user)
    {
        return $this->actingAs($user)->withSession([
            'data_privacy_consented' => true,
            'privacy_consented_session' => true,
        ]);
    }

    public function test_student_can_submit_sle_fhe_address_request(): void
    {
        $profile = StudentProfile::factory()->create();

        $response = $this->actingAsStudent($profile->user)->post(route('student.sle-fhe.request'), [
            'province' => 'Davao Oriental',
            'municipality_city' => 'Mati City',
            'barangay' => 'San Isidro',
            'street_purok' => 'Purok 2',
        ]);

        $response->assertRedirect(route('student.sle-fhe'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('sle_fhe_requests', [
            'student_profile_id' => $profile->id,
            'province' => 'Davao Oriental',
            'municipality_city' => 'Mati City',
            'barangay' => 'San Isidro',
            'street_purok' => 'Purok 2',
            'status' => 'pending',
        ]);

        $request = SleFheRequest::query()->where('student_profile_id', $profile->id)->firstOrFail();
        $this->assertNotNull($request->submitted_at, 'submitted_at must be stamped with the current time');
        $this->assertSame(SleFheStatus::PendingReview->value, $profile->fresh()->sle_fhe_status);
    }

    public function test_student_can_resubmit_and_update_sle_fhe_address_request(): void
    {
        $profile = StudentProfile::factory()->create();
        $existing = SleFheRequest::create([
            'student_profile_id' => $profile->id,
            'province' => 'Davao del Sur',
            'municipality_city' => 'Davao City',
            'barangay' => 'Buhangin',
            'street_purok' => 'Old Street',
            'status' => 'pending',
            'submitted_at' => now()->subDay(),
        ]);

        $this->actingAsStudent($profile->user)->post(route('student.sle-fhe.request'), [
            'province' => 'Davao Oriental',
            'municipality_city' => 'Mati City',
            'barangay' => 'Central',
            'street_purok' => 'Purok 5',
        ])->assertRedirect(route('student.sle-fhe'));

        $this->assertDatabaseCount('sle_fhe_requests', 1);
        $this->assertDatabaseHas('sle_fhe_requests', [
            'id' => $existing->id,
            'province' => 'Davao Oriental',
            'municipality_city' => 'Mati City',
            'barangay' => 'Central',
            'street_purok' => 'Purok 5',
            'status' => 'pending',
        ]);

        $this->assertTrue(
            $existing->fresh()->submitted_at->gt($existing->submitted_at),
            'Resubmission must refresh submitted_at',
        );
    }

    public function test_student_cannot_submit_address_request_when_verification_exists(): void
    {
        $profile = StudentProfile::factory()->create();
        SleFheVerification::create([
            'student_profile_id' => $profile->id,
            'verified_at' => now(),
        ]);

        $this->actingAsStudent($profile->user)
            ->post(route('student.sle-fhe.request'), [
                'province' => 'Davao Oriental',
                'municipality_city' => 'Mati City',
                'barangay' => 'San Isidro',
                'street_purok' => 'Purok 2',
            ])
            ->assertRedirect(route('student.sle-fhe'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('sle_fhe_requests', ['student_profile_id' => $profile->id]);
    }

    public function test_sle_fhe_address_request_requires_address_fields(): void
    {
        $profile = StudentProfile::factory()->create();

        $this->actingAsStudent($profile->user)
            ->post(route('student.sle-fhe.request'), [
                'province' => '',
                'municipality_city' => '',
                'barangay' => '',
                'street_purok' => '',
            ])
            ->assertSessionHasErrors(['province', 'municipality_city', 'barangay']);

        $this->assertDatabaseCount('sle_fhe_requests', 0);
    }

    public function test_sle_fhe_page_shows_editable_address_form_to_unverified_student(): void
    {
        $profile = StudentProfile::factory()->create();

        $response = $this->actingAsStudent($profile->user)->get(route('student.sle-fhe'));

        $response->assertOk()
            ->assertSee('Request Verification')
            ->assertSee('name="province"', false)
            ->assertSee('name="municipality_city"', false)
            ->assertSee('name="barangay"', false)
            ->assertSee('name="street_purok"', false)
            ->assertDontSee('Verification Request Pending Review')
            ->assertDontSee('Verification Status: Verified - Profile Locked');
    }

    public function test_sle_fhe_page_shows_editable_address_form_to_rejected_student(): void
    {
        $profile = StudentProfile::factory()->create();
        SleFheRejection::create(['student_profile_id' => $profile->id]);

        $response = $this->actingAsStudent($profile->user)->get(route('student.sle-fhe'));

        $response->assertOk()
            ->assertSee('Request Verification')
            ->assertSee('name="province"', false)
            ->assertDontSee('Verification Request Pending Review')
            ->assertDontSee('Verification Status: Verified - Profile Locked');
    }

    public function test_sle_fhe_page_locks_address_form_when_verification_request_is_pending(): void
    {
        $profile = StudentProfile::factory()->create();
        SleFheRequest::create([
            'student_profile_id' => $profile->id,
            'province' => 'Davao de Oro',
            'municipality_city' => 'Nabunturan',
            'barangay' => 'Poblacion',
            'street_purok' => 'Purok 3',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAsStudent($profile->user)->get(route('student.sle-fhe'));

        $response->assertOk()
            ->assertSee('Verification Request Pending Review')
            ->assertSee('disabled', false)
            ->assertSee('Davao de Oro', false)
            ->assertSee('Nabunturan', false)
            ->assertSee('Purok 3', false)
            ->assertSee('Awaiting FASSG Review')
            ->assertDontSee('Verification Status: Verified - Profile Locked');
    }

    public function test_sle_fhe_page_locks_address_form_when_profile_is_verified(): void
    {
        $profile = StudentProfile::factory()->create();
        SleFheVerification::create([
            'student_profile_id' => $profile->id,
            'verified_address' => 'Purok 2, San Isidro, Mati City, Davao Oriental',
            'verified_at' => now(),
        ]);

        $response = $this->actingAsStudent($profile->user)->get(route('student.sle-fhe'));

        $response->assertOk()
            ->assertSee('Verification Status: Verified - Profile Locked')
            ->assertSee('disabled', false)
            ->assertSee('Profile Locked')
            ->assertDontSee('Verification Request Pending Review');

        $this->actingAsStudent($profile->user)
            ->post(route('student.sle-fhe.request'), [
                'province' => 'Davao Oriental',
                'municipality_city' => 'Mati City',
                'barangay' => 'San Isidro',
                'street_purok' => 'Purok 2',
            ])
            ->assertRedirect(route('student.sle-fhe'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('sle_fhe_requests', ['student_profile_id' => $profile->id]);
    }
}
