<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\FixedListItemStatus;
use App\Enums\FixedListStatus;
use App\Enums\GeneratedBatchStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\FixedList;
use App\Models\FixedListItem;
use App\Models\GeneratedBatch;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FixedListTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsFassg(): User
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $this->actingAs($fassg);

        return $fassg;
    }

    public function test_fassg_can_endorse_and_explicitly_revoke_a_verified_fixed_list_item(): void
    {
        $fassg = $this->actingAsFassg();
        $list = FixedList::factory()->create(['status' => FixedListStatus::Draft]);
        $profile = StudentProfile::factory()->verified()->create();
        $item = FixedListItem::factory()->create([
            'fixed_list_id' => $list->id,
            'student_id_number' => $profile->student_id_number,
            'is_manually_endorsed' => false,
            'endorsed_by_id' => null,
            'endorsed_at' => null,
            'status' => FixedListItemStatus::Pending,
        ]);

        $this->patch(route('fassg.fixed-lists.items.endorse', [$list, $item]))
            ->assertRedirect();

        $item->refresh();
        $this->assertTrue($item->is_manually_endorsed);
        $this->assertSame($fassg->id, $item->endorsed_by_id);
        $this->assertNotNull($item->endorsed_at);
        $this->assertTrue($item->status === FixedListItemStatus::Endorsed);

        $this->patch(route('fassg.fixed-lists.items.endorse', [$list, $item]), ['endorse' => false])
            ->assertRedirect();

        $item->refresh();
        $this->assertFalse($item->is_manually_endorsed);
        $this->assertNull($item->endorsed_by_id);
        $this->assertNull($item->endorsed_at);
        $this->assertTrue($item->status === FixedListItemStatus::Pending);
    }

    public function test_fixed_list_verification_preserves_original_canonical_verification(): void
    {
        $fassg = $this->actingAsFassg();
        $profile = StudentProfile::factory()->verified()->create([
            'student_id_number' => '2024-00111',
        ]);
        $originalVerification = $profile->sleFheVerification->getRawOriginal();
        $list = FixedList::factory()->create(['status' => FixedListStatus::Draft]);
        $item = FixedListItem::factory()->create([
            'fixed_list_id' => $list->id,
            'student_id_number' => $profile->student_id_number,
            'is_sle_fhe_verified' => false,
            'status' => FixedListItemStatus::Pending,
        ]);

        $this->patch(route('fassg.fixed-lists.items.verify', [$list, $item]))
            ->assertRedirect();

        $item->refresh();
        $this->assertTrue($item->is_sle_fhe_verified);
        $this->assertTrue($item->status === FixedListItemStatus::Verified);

        $this->assertSame($originalVerification, $profile->fresh()->sleFheVerification->getRawOriginal());
    }

    public function test_fassg_can_finalize_fixed_list_without_sponsor_workflow(): void
    {
        Notification::fake();

        $fassg = $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create();
        $programBefore = $program->fresh()->getRawOriginal();
        $list = FixedList::factory()->create([
            'sponsorship_program_id' => $program->id,
            'status' => FixedListStatus::Draft,
            'fassg_assigned_at' => null,
            'fassg_assigned_by_id' => null,
        ]);
        $profile = StudentProfile::factory()->verified()->create();
        FixedListItem::factory()->create([
            'student_id_number' => $profile->student_id_number,
            'fixed_list_id' => $list->id,
            'is_sle_fhe_verified' => true,
            'is_manually_endorsed' => true,
            'status' => FixedListItemStatus::Endorsed,
            'endorsed_by_id' => $fassg->id,
            'endorsed_at' => now(),
        ]);

        $this->patch(route('fassg.fixed-lists.finalize', $list))
            ->assertRedirect()
            ->assertSessionHas('status', 'Fixed List has been finalized and is ready for batch generation.');

        $list->refresh();
        $this->assertTrue($list->status === FixedListStatus::Finalized);
        $this->assertNotNull($list->fassg_assigned_at);
        $this->assertSame($fassg->id, $list->fassg_assigned_by_id);
        $this->assertSame($programBefore, $program->fresh()->getRawOriginal());

        Notification::assertNothingSent();
    }

    public function test_finalize_is_blocked_until_all_items_verified_and_endorsed(): void
    {
        Notification::fake();

        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create();

        $scenarios = [
            'unverified only' => ['is_sle_fhe_verified' => false, 'is_manually_endorsed' => true, 'status' => FixedListItemStatus::Endorsed],
            'unendorsed only' => ['is_sle_fhe_verified' => true, 'is_manually_endorsed' => false, 'status' => FixedListItemStatus::Verified],
            'neither' => ['is_sle_fhe_verified' => false, 'is_manually_endorsed' => false, 'status' => FixedListItemStatus::Pending],
        ];

        foreach ($scenarios as $label => $attributes) {
            $list = FixedList::factory()->create([
                'sponsorship_program_id' => $program->id,
                'status' => FixedListStatus::Draft,
            ]);
            FixedListItem::factory()->create(['fixed_list_id' => $list->id] + $attributes);

            $this->patch(route('fassg.fixed-lists.finalize', $list))
                ->assertRedirect()
                ->assertSessionHasErrors('list');

            $list->refresh();
            $this->assertTrue($list->status === FixedListStatus::Draft, "{$label}: list should stay Draft");
            $this->assertNull($list->fassg_assigned_at, "{$label}: list should not record assignment");
            $this->assertNull($list->fassg_assigned_by_id, "{$label}: list should not record assignee");
        }

        Notification::assertNothingSent();
    }

    public function test_batch_contains_only_manually_selected_ordinary_applicants_without_ranking(): void
    {
        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create(['total_slots' => 1]);
        $chosen = $this->makeVerifiedApplication($program, StudentProfile::factory()->verified()->create(), 2.50);
        $unselected = $this->makeVerifiedApplication($program, StudentProfile::factory()->verified()->create(), 1.25);
        $unselected->update(['submitted_at' => now()->subDays(2)]);
        $unselectedBefore = $unselected->fresh()->getRawOriginal();

        $this->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'Manual applicants',
            'sponsorship_program_id' => $program->id,
            'selected_applications' => [$chosen->id],
        ])->assertSessionDoesntHaveErrors()->assertRedirect();

        $batch = GeneratedBatch::query()->sole();
        $this->assertSame($program->id, $batch->sponsorship_program_id);
        $this->assertSame([$chosen->id], $batch->items()->pluck('application_id')->all());
        $this->assertNull($batch->items()->sole()->rank_position);
        $this->assertFalse($batch->items()->sole()->is_fixed_list);
        $this->assertSame(ApplicationStatus::Verified, $chosen->fresh()->status);
        $this->assertNull($chosen->fresh()->approved_at);
        $this->assertSame($unselectedBefore, $unselected->fresh()->getRawOriginal());
    }

    public function test_fixed_list_only_batch_loads_the_entire_chosen_roster_without_student_submissions(): void
    {
        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create();
        $chosen = $this->makeFixedCandidate($program);
        $second = $this->makeFixedCandidate($program, $chosen->fixedList);
        $unselected = $this->makeFixedCandidate($program);
        $originalEntry = $chosen->fresh()->getRawOriginal();
        $programBefore = $program->fresh()->getRawOriginal();

        // The selection dialog remains available even with no ordinary applications.
        $response = $this->get(route('fassg.applications.index', ['program_id' => $program->id,
            'fixed_list_id' => $chosen->fixed_list_id, 'prepare_batch' => 1]))
            ->assertOk()->assertSee('Select Candidates / Create Batch')
            ->assertViewHas('selectedFixedListId', $chosen->fixed_list_id)->assertViewHas('openBatchModal', true)
            ->assertSee('name="fixed_list_source"', false)->assertDontSee('fixed-item-checkbox', false);
        $this->assertDoesNotMatchRegularExpression('/<input\\b(?=[^>]*class="[^"]*(?:app-checkbox|fixed-item-checkbox))[^>]*\\schecked\\b/s', $response->getContent());

        $this->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'Manual fixed list',
            'sponsorship_program_id' => $program->id,
            'selected_applications' => [],
            'fixed_list_source' => $chosen->fixed_list_id,
        ])->assertSessionDoesntHaveErrors()->assertRedirect();

        $batch = GeneratedBatch::query()->sole();
        $this->assertSame(2, $batch->items()->count());
        $this->assertEqualsCanonicalizing([$chosen->id, $second->id], $batch->items()->pluck('fixed_list_item_id')->all());
        $member = $batch->items()->where('fixed_list_item_id', $chosen->id)->sole();
        $application = $member->application;
        $this->assertTrue($member->is_fixed_list);
        $this->assertNull($member->rank_position);
        $this->assertSame($program->id, $application->sponsorship_program_id);
        $this->assertSame($chosen->studentProfile->id, $application->student_profile_id);
        $this->assertTrue($application->is_auto_provisioned);
        $this->assertSame(ApplicationStatus::Pending, $application->status);
        $this->assertSame($chosen->id, $member->fixed_list_item_id);
        $this->assertNull($application->gpa_submitted);
        $this->assertNull($application->address_submitted);
        $this->assertNull($application->is_rural_submitted);
        $this->assertNull($application->verified_at);
        $this->assertNull($application->submitted_at);
        $this->assertNull($application->approved_at);
        $this->assertNull($chosen->studentProfile->fresh()->active_sponsorship_id);
        $this->assertSame($originalEntry, $chosen->fresh()->getRawOriginal());
        $this->assertFalse($unselected->studentProfile->applications()->exists());
        $this->assertSame(GeneratedBatchStatus::Saved, $batch->status);
        $this->assertSame($programBefore, $program->fresh()->getRawOriginal());
    }

    public function test_mixed_batch_deduplicates_explicit_candidates_without_fixed_list_priority(): void
    {
        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create();
        $fixed = $this->makeFixedCandidate($program);
        $fixed->studentProfile->update(['student_id_number' => '2024-90001']);
        $fixed->update(['student_id_number' => '2024-90001']);
        $ordinaryProfile = StudentProfile::factory()->verified()->create(['student_id_number' => '2024-10001']);
        $ordinary = $this->makeVerifiedApplication($program, $ordinaryProfile, 2.50);
        $fixedApp = $this->makeVerifiedApplication($program, $fixed->studentProfile, 1.00);
        $unselected = $this->makeFixedCandidate($program);

        $this->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'Mixed manual batch',
            'sponsorship_program_id' => $program->id,
            'selected_applications' => [$fixedApp->id, $ordinary->id, $ordinary->id],
            'fixed_list_source' => $fixed->fixed_list_id,
        ])->assertSessionDoesntHaveErrors()->assertRedirect();

        $batch = GeneratedBatch::query()->sole();
        $this->assertSame(2, $batch->total_slots);
        $this->assertSame(2, $batch->items()->distinct('application_id')->count('application_id'));
        $this->assertSame(0, $batch->items()->whereNotNull('rank_position')->count());
        $this->assertTrue($batch->items()->where('application_id', $fixedApp->id)->sole()->is_fixed_list);
        $this->assertFalse($batch->items()->where('application_id', $ordinary->id)->sole()->is_fixed_list);
        $this->assertFalse($unselected->studentProfile->applications()->exists());
        $audit = \App\Models\AuditLog::query()->where('action', 'fassg.generated_batch.generated_from_applications')->sole();
        $this->assertSame($fixed->fixed_list_id, json_decode($audit->details, true)['fixed_list_id']);
        $this->assertSame([$fixed->id], json_decode($audit->details, true)['selected_fixed_list_item_ids']);

        // Historical ranks remain stored, but cannot control the display.
        $batch->items()->where('application_id', $fixedApp->id)->update(['rank_position' => 1]);
        $batch->items()->where('application_id', $ordinary->id)->update(['rank_position' => 9]);
        $response = $this->get(route('fassg.generated-batches.show', $batch))
            ->assertOk()->assertSee('Selected Candidates')->assertDontSee('Ranked by GWA');
        $this->assertSame([$ordinary->id, $fixedApp->id], $response->viewData('list')->items->pluck('application_id')->all());
        $this->assertSame(9, $batch->items()->where('application_id', $ordinary->id)->sole()->rank_position);
    }

    public function test_empty_selection_never_fills_from_applications_or_fixed_lists(): void
    {
        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create();
        $application = $this->makeVerifiedApplication($program, StudentProfile::factory()->verified()->create(), 1.25);
        $fixed = $this->makeFixedCandidate($program);
        $before = $application->fresh()->getRawOriginal();

        foreach ([[], ['selected_applications' => [], 'fixed_list_source' => null]] as $selection) {
            $this->post(route('fassg.applications.create-batch'), $selection + [
                'batch_name' => 'Must not generate',
                'sponsorship_program_id' => $program->id,
            ])->assertSessionHasErrors('selected_applications');
        }
        $this->assertDatabaseCount('generated_batches', 0);
        $this->assertDatabaseCount('generated_batch_items', 0);
        $this->assertFalse($fixed->studentProfile->applications()->exists());
        $this->assertSame($before, $application->fresh()->getRawOriginal());
    }

    public function test_legacy_whole_list_selection_is_rejected(): void
    {
        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create();
        $fixed = $this->makeFixedCandidate($program);
        $this->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'Legacy request',
            'sponsorship_program_id' => $program->id,
            'locked_fixed_list_id' => $fixed->fixed_list_id,
        ])->assertSessionHasErrors('locked_fixed_list_id');
        $this->assertDatabaseCount('generated_batches', 0);
        $this->assertFalse($fixed->studentProfile->applications()->exists());
    }

    public function test_candidates_and_append_target_must_belong_to_the_selected_program(): void
    {
        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create();
        $otherProgram = SponsorshipProgram::factory()->create();
        $otherApp = $this->makeVerifiedApplication($otherProgram, StudentProfile::factory()->verified()->create(), 1.75);
        $fixed = $this->makeFixedCandidate($otherProgram);
        $otherBatch = GeneratedBatch::factory()->create([
            'sponsorship_program_id' => $otherProgram->id,
            'status' => GeneratedBatchStatus::Saved,
        ]);
        $cases = [
            [['selected_applications' => [$otherApp->id]], 'selected_applications'],
            [['fixed_list_source' => $fixed->fixed_list_id], 'fixed_list_source'],
            [['fixed_list_source' => $fixed->fixed_list_id, 'existing_fixed_list_id' => $otherBatch->id], 'existing_fixed_list_id'],
        ];
        foreach ($cases as [$selection, $error]) {
            $this->post(route('fassg.applications.create-batch'), $selection + [
                'batch_name' => 'Wrong program',
                'sponsorship_program_id' => $program->id,
            ])->assertSessionHasErrors($error);
        }
        $this->assertDatabaseCount('generated_batches', 1);
        $this->assertDatabaseCount('generated_batch_items', 0);
        $this->assertNull($otherApp->fresh()->batch_id);
    }

    public function test_fixed_candidates_require_finalization_verification_and_endorsement(): void
    {
        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create();
        foreach (['draft', 'unverified entry', 'unendorsed', 'unverified profile', 'missing attribution', 'missing timestamp', 'ineligible', 'wrong course'] as $case) {
            $fixed = $this->makeFixedCandidate($program);
            if ($case === 'draft') $fixed->fixedList->update(['status' => FixedListStatus::Draft]);
            if ($case === 'unverified entry') $fixed->update(['is_sle_fhe_verified' => false]);
            if ($case === 'unendorsed') $fixed->update(['is_manually_endorsed' => false]);
            if ($case === 'unverified profile') $fixed->studentProfile->sleFheVerification()->delete();
            if ($case === 'missing attribution') $fixed->update(['endorsed_by_id' => null]);
            if ($case === 'missing timestamp') $fixed->update(['endorsed_at' => null]);
            if ($case === 'ineligible') $fixed->update(['status' => FixedListItemStatus::Ineligible]);
            if ($case === 'wrong course') $program->update(['target_course' => 'No matching course']);
            // A valid second member must never be partially provisioned when one fails.
            $otherMember = $this->makeFixedCandidate($program, $fixed->fixedList);

            $this->post(route('fassg.applications.create-batch'), [
                'batch_name' => $case,
                'sponsorship_program_id' => $program->id,
                'fixed_list_source' => $fixed->fixed_list_id,
            ])->assertSessionHasErrors();
            $this->assertFalse($fixed->studentProfile->applications()->exists());
            $this->assertFalse($otherMember->studentProfile->applications()->exists());
        }
        $this->assertDatabaseCount('generated_batches', 0);
    }

    public function test_append_is_idempotent_and_does_not_duplicate_student_membership(): void
    {
        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create();
        $application = $this->makeVerifiedApplication($program, StudentProfile::factory()->verified()->create(), 1.75);
        $payload = ['sponsorship_program_id' => $program->id, 'selected_applications' => [$application->id, $application->id]];
        $this->post(route('fassg.applications.create-batch'), $payload + ['batch_name' => 'Append target'])
            ->assertSessionDoesntHaveErrors();
        $batch = GeneratedBatch::query()->sole();
        $before = $application->fresh()->getRawOriginal();

        $this->post(route('fassg.applications.create-batch'), $payload + ['existing_fixed_list_id' => $batch->id])
            ->assertSessionDoesntHaveErrors()->assertRedirect(route('fassg.generated-batches.show', $batch));
        $this->assertSame(1, $batch->items()->count());
        $this->assertSame(1, $batch->fresh()->total_slots);
        $this->assertSame($before, $application->fresh()->getRawOriginal());

        $this->post(route('fassg.applications.create-batch'), $payload + ['batch_name' => 'Second batch'])
            ->assertSessionHasErrors('selected_applications');
        $this->assertDatabaseCount('generated_batches', 1);
    }

    public function test_manual_selection_preserves_reapplication_after_expiration_and_historical_membership(): void
    {
        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create();
        $profile = StudentProfile::factory()->verified()->create();
        $expired = Application::factory()->create([
            'student_profile_id' => $profile->id,
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Expired,
            'approved_at' => now()->subYear(),
        ]);
        $historicalBatch = GeneratedBatch::factory()->create([
            'sponsorship_program_id' => $program->id,
            'status' => GeneratedBatchStatus::Approved,
        ]);
        $historicalItem = $historicalBatch->items()->create([
            'application_id' => $expired->id,
            'rank_position' => 2,
            'origin_type' => 'ranked_queue',
        ]);
        $profile->update(['active_sponsorship_id' => $expired->id]);
        $application = $this->makeVerifiedApplication($program, $profile, 1.75);
        $expiredBefore = $expired->fresh()->getRawOriginal();
        $itemBefore = $historicalItem->fresh()->getRawOriginal();

        $this->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'Qualified reapplication',
            'sponsorship_program_id' => $program->id,
            'selected_applications' => [$application->id],
        ])->assertSessionDoesntHaveErrors();
        $batch = GeneratedBatch::query()->where('batch_name', 'Qualified reapplication')->sole();
        $this->assertSame([$application->id], $batch->items()->pluck('application_id')->all());
        $this->assertSame($expiredBefore, $expired->fresh()->getRawOriginal());
        $this->assertSame($itemBefore, $historicalItem->fresh()->getRawOriginal());
        $this->assertSame($expired->id, $profile->fresh()->active_sponsorship_id);
    }

    public function test_capacity_overflow_never_truncates_selection_or_provisions_unselected_students(): void
    {
        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create(['total_slots' => 1]);
        $application = $this->makeVerifiedApplication($program, StudentProfile::factory()->verified()->create(), 1.75);
        $fixed = $this->makeFixedCandidate($program);
        $before = $application->fresh()->getRawOriginal();
        $this->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'Too many',
            'sponsorship_program_id' => $program->id,
            'selected_applications' => [$application->id],
            'fixed_list_source' => $fixed->fixed_list_id,
        ])->assertSessionHasErrors('selected_applications');

        $this->assertDatabaseCount('generated_batches', 0);
        $this->assertDatabaseCount('generated_batch_items', 0);
        $this->assertFalse($fixed->studentProfile->applications()->exists());
        $this->assertSame($before, $application->fresh()->getRawOriginal());
    }

    public function test_manual_selection_preserves_active_sponsorship_and_eligibility_safeguards(): void
    {
        $this->actingAsFassg();
        foreach (['rejected', 'resubmission', 'unverified', 'active', 'expired program'] as $case) {
            $program = SponsorshipProgram::factory()->create();
            $profile = StudentProfile::factory()->verified()->create();
            $application = $this->makeVerifiedApplication($program, $profile, 1.75);
            if ($case === 'rejected') $application->update(['status' => ApplicationStatus::Rejected]);
            if ($case === 'resubmission') $application->update(['status' => ApplicationStatus::ResubmissionRequested]);
            if ($case === 'unverified') $profile->sleFheVerification()->delete();
            if ($case === 'active') {
                $active = Application::factory()->create([
                    'student_profile_id' => $profile->id,
                    'status' => ApplicationStatus::Ongoing,
                ]);
                $profile->update(['active_sponsorship_id' => $active->id]);
            }
            if ($case === 'expired program') $program->update(['end_date' => now()->subDay()]);
            $before = $application->fresh()->getRawOriginal();
            $profileBefore = $profile->fresh()->getRawOriginal();

            $this->post(route('fassg.applications.create-batch'), [
                'batch_name' => $case,
                'sponsorship_program_id' => $program->id,
                'selected_applications' => [$application->id],
            ])->assertSessionHasErrors($case === 'expired program' ? 'sponsorship_program_id' : 'selected_applications');
            $this->assertSame($before, $application->fresh()->getRawOriginal());
            $this->assertSame($profileBefore, $profile->fresh()->getRawOriginal());
        }
        $this->assertDatabaseCount('generated_batches', 0);
    }

    public function test_manually_selected_mixed_batch_can_be_submitted_to_sponsor_without_approval(): void
    {
        Notification::fake();
        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create();
        $fixed = $this->makeFixedCandidate($program);
        $application = $this->makeVerifiedApplication($program, StudentProfile::factory()->verified()->create(), 1.75);
        $this->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'Forward manual selections',
            'sponsorship_program_id' => $program->id,
            'selected_applications' => [$application->id],
            'fixed_list_source' => $fixed->fixed_list_id,
        ])->assertSessionDoesntHaveErrors();
        $batch = GeneratedBatch::query()->sole();
        $fixedApplication = $batch->items()->where('origin_type', 'fixed_list')->sole()->application;
        $this->patch(route('fassg.generated-batches.submit', $batch))->assertSessionHasErrors('batch');
        $this->patch(route('fassg.generated-batches.applications.verify', [$batch, $fixedApplication]), [
            'confirm_eligibility' => 1, 'confirm_gwa' => 1, 'gpa_submitted' => 1.75,
        ])->assertSessionHasNoErrors();
        $before = $batch->fresh()->items->mapWithKeys(fn($item) => [$item->application_id => $item->application->getRawOriginal()])->all();

        $this->patch(route('fassg.generated-batches.submit', $batch))
            ->assertSessionDoesntHaveErrors()->assertRedirect();
        $this->assertSame(GeneratedBatchStatus::Submitted, $batch->fresh()->status);
        foreach ($batch->fresh()->items as $item) {
            $this->assertSame($before[$item->application_id], $item->application->getRawOriginal());
            $this->assertSame(ApplicationStatus::Verified, $item->application->status);
            $this->assertNull($item->application->approved_at);
            $this->assertNull($item->application->studentProfile->active_sponsorship_id);
        }
        $this->assertFalse($batch->sponsorApprovals()->exists());
    }

    private function makeFixedCandidate(SponsorshipProgram $program, ?FixedList $list = null): FixedListItem
    {
        $profile = StudentProfile::factory()->verified()->create();
        $list ??= FixedList::factory()->create([
            'sponsorship_program_id' => $program->id,
            'status' => FixedListStatus::Finalized,
        ]);

        return FixedListItem::factory()->create([
            'fixed_list_id' => $list->id,
            'student_id_number' => $profile->student_id_number,
            'student_name' => $profile->full_name,
            'is_sle_fhe_verified' => true,
            'is_manually_endorsed' => true,
            'endorsed_by_id' => $list->uploaded_by_fassg_id,
            'endorsed_at' => now(),
            'status' => FixedListItemStatus::Endorsed,
        ]);
    }

    public function test_empty_lists_and_client_supplied_fixed_member_ids_cannot_create_a_batch(): void
    {
        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create();
        $fixed = $this->makeFixedCandidate($program);
        $empty = FixedList::factory()->create(['sponsorship_program_id' => $program->id, 'status' => FixedListStatus::Finalized]);
        $payload = ['batch_name' => 'Invalid source', 'sponsorship_program_id' => $program->id];
        $this->post(route('fassg.applications.create-batch'), $payload + ['fixed_list_source' => $empty->id])
            ->assertSessionHasErrors('fixed_list_source');
        $this->post(route('fassg.applications.create-batch'), $payload + [
            'fixed_list_source' => $fixed->fixed_list_id, 'selected_fixed_list_items' => [$fixed->id],
        ])->assertSessionHasErrors('selected_fixed_list_items');
        $this->assertDatabaseCount('generated_batches', 0);
        $this->assertDatabaseCount('applications', 0);
    }

    public function test_full_fixed_roster_cannot_be_truncated_to_fit_capacity(): void
    {
        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create(['total_slots' => 1, 'available_slots' => 1]);
        $first = $this->makeFixedCandidate($program);
        $this->makeFixedCandidate($program, $first->fixedList);
        $this->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'Full roster', 'sponsorship_program_id' => $program->id, 'fixed_list_source' => $first->fixed_list_id,
        ])->assertSessionHasErrors('selected_applications');
        $this->assertDatabaseCount('applications', 0);
        $this->assertDatabaseCount('generated_batches', 0);
        $this->assertSame(1, (int) $program->fresh()->getRawOriginal('available_slots'));
    }

    public function test_distinct_application_records_for_the_same_student_are_rejected_without_guessing(): void
    {
        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create();
        $profile = StudentProfile::factory()->verified()->create();
        $first = $this->makeVerifiedApplication($program, $profile, 1.75);
        $second = $this->makeVerifiedApplication($program, $profile, 2.0);
        $this->post(route('fassg.applications.create-batch'), ['batch_name' => 'Duplicate identities',
            'sponsorship_program_id' => $program->id, 'selected_applications' => [$first->id, $second->id]])
            ->assertSessionHasErrors('selected_applications');
        $this->assertDatabaseCount('generated_batches', 0);
        $this->assertNull($first->fresh()->batch_id);
        $this->assertNull($second->fresh()->batch_id);
    }

    public function test_draft_list_student_removal_preserves_student_verification_and_audit_evidence(): void
    {
        $officer = $this->actingAsFassg();
        $item = $this->makeFixedCandidate(SponsorshipProgram::factory()->create());
        $list = $item->fixedList;
        $list->update(['status' => FixedListStatus::Draft, 'total_names' => 1]);
        $profileBefore = $item->studentProfile->fresh()->getRawOriginal();
        $verification = $item->studentProfile->sleFheVerification;
        $verificationBefore = $verification->getRawOriginal();
        $itemBefore = $item->fresh()->getRawOriginal();
        $this->get(route('fassg.fixed-lists.show', $list))->assertOk()->assertSee('Remove Student');
        $this->delete(route('fassg.fixed-lists.items.destroy', [$list, $item]))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('fixed_list_items', ['id' => $item->id]);
        $this->assertSame(0, $list->fresh()->total_names);
        $this->assertSame($profileBefore, $item->studentProfile->fresh()->getRawOriginal());
        $this->assertSame($verificationBefore, $verification->fresh()->getRawOriginal());
        $audit = \App\Models\AuditLog::query()->where('action', 'fassg.fixed_list.student_removed')->sole();
        $this->assertSame($officer->id, $audit->user_id);
        $this->assertEquals($itemBefore, json_decode($audit->details, true)['before']);
    }

    public function test_list_student_removal_requires_fassg_correct_membership_and_draft_status(): void
    {
        $officer = $this->actingAsFassg();
        $item = $this->makeFixedCandidate(SponsorshipProgram::factory()->create());
        $list = $item->fixedList;
        $url = route('fassg.fixed-lists.items.destroy', [$list, $item]);
        $before = $item->fresh()->getRawOriginal();
        $this->get(route('fassg.fixed-lists.show', $list))->assertOk()->assertDontSee('Remove Student');
        foreach ([FixedListStatus::Finalized, FixedListStatus::Submitted, FixedListStatus::Rejected, FixedListStatus::Saved] as $status) {
            $list->update(['status' => $status]);
            $this->delete($url)->assertForbidden();
        }
        $list->update(['status' => FixedListStatus::Draft]);
        $foreign = $this->makeFixedCandidate($list->sponsorshipProgram);
        $this->delete(route('fassg.fixed-lists.items.destroy', [$list, $foreign]))->assertNotFound();
        $this->actingAs(User::factory()->create(['role' => UserRole::Student]))->delete($url)->assertForbidden();
        $this->assertSame($before, $item->fresh()->getRawOriginal());
        $this->actingAs($officer);
    }

    public function test_source_removal_and_archiving_are_blocked_by_membership_or_frozen_history(): void
    {
        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create();
        $item = $this->makeFixedCandidate($program);
        $list = $item->fixedList;
        $this->post(route('fassg.applications.create-batch'), ['batch_name' => 'Referenced source',
            'sponsorship_program_id' => $program->id, 'fixed_list_source' => $list->id])->assertSessionHasNoErrors();
        $batch = GeneratedBatch::query()->sole();
        $roster = ['members' => $batch->rosterMembers()];
        // Even a legacy/inconsistent Draft status must not override an existing reference.
        $list->update(['status' => FixedListStatus::Draft]);
        $remove = route('fassg.fixed-lists.items.destroy', [$list, $item]);
        $archive = route('fassg.fixed-lists.destroy', $list);
        $this->delete($remove)->assertSessionHasErrors('fixed_list');
        $this->delete($archive)->assertSessionHasErrors('fixed_list');
        $batch->update(['submitted_roster' => $roster, 'submission_version' => 1]);
        $batch->items()->delete(); // Simulate broken legacy links; the snapshot still protects the source.
        $batch->delete();
        $this->delete($remove)->assertSessionHasErrors('fixed_list');
        $this->delete($archive)->assertSessionHasErrors('fixed_list');
        $this->assertDatabaseHas('fixed_list_items', ['id' => $item->id]);
        $this->assertNull($list->fresh()->deleted_at);
    }

    public function test_obsolete_unreferenced_list_archives_without_deleting_members(): void
    {
        $this->actingAsFassg();
        $item = $this->makeFixedCandidate(SponsorshipProgram::factory()->create());
        $item->fixedList->update(['status' => FixedListStatus::Draft]);
        $before = $item->fresh()->getRawOriginal();
        $this->delete(route('fassg.fixed-lists.destroy', $item->fixedList))->assertSessionHasNoErrors();
        $this->assertSoftDeleted('fixed_lists', ['id' => $item->fixed_list_id]);
        $this->assertSame($before, $item->fresh()->getRawOriginal());
    }

    public function test_draft_candidate_removal_keeps_original_fixed_roster_and_application_evidence(): void
    {
        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create();
        $fixed = $this->makeFixedCandidate($program);
        $ordinary = $this->makeVerifiedApplication($program, StudentProfile::factory()->verified()->create(), 1.75);
        $this->post(route('fassg.applications.create-batch'), ['batch_name' => 'Draft removal',
            'sponsorship_program_id' => $program->id, 'fixed_list_source' => $fixed->fixed_list_id,
            'selected_applications' => [$ordinary->id]])->assertSessionHasNoErrors();
        $batch = GeneratedBatch::query()->sole();
        $sourceBefore = $fixed->fresh()->getRawOriginal();
        $programBefore = $program->fresh()->getRawOriginal();
        foreach ($batch->items as $member) {
            $application = $member->application;
            $evidence = $application->only(['status', 'verified_at', 'approved_at', 'gpa_submitted', 'is_auto_provisioned']);
            $this->delete(route('fassg.generated-batches.items.destroy', [$batch, $member]))->assertSessionHasNoErrors();
            $this->assertEquals($evidence, $application->fresh()->only(array_keys($evidence)));
            $this->assertNull($application->fresh()->batch_id);
            $this->assertFalse($application->fresh()->is_batched);
        }
        $this->assertSame(0, $batch->fresh()->total_slots);
        $this->assertSame($sourceBefore, $fixed->fresh()->getRawOriginal());
        $this->assertSame($programBefore, $program->fresh()->getRawOriginal());
        $this->assertDatabaseCount('applications', 2);
        $this->patch(route('fassg.generated-batches.submit', $batch))->assertSessionHasErrors('batch');
        $this->assertSame(2, \App\Models\AuditLog::query()->where('action', 'fassg.generated_batch.candidate_removed')->count());
    }

    public function test_batch_member_removal_blocks_foreign_members_unauthorized_users_and_historical_states(): void
    {
        $officer = $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create();
        $fixed = $this->makeFixedCandidate($program);
        $this->post(route('fassg.applications.create-batch'), ['batch_name' => 'Protected batch',
            'sponsorship_program_id' => $program->id, 'fixed_list_source' => $fixed->fixed_list_id])->assertSessionHasNoErrors();
        $batch = GeneratedBatch::query()->sole();
        $item = $batch->items()->sole();
        $url = route('fassg.generated-batches.items.destroy', [$batch, $item]);
        $other = GeneratedBatch::factory()->create(['sponsorship_program_id' => $program->id, 'status' => GeneratedBatchStatus::Saved]);
        $this->delete(route('fassg.generated-batches.items.destroy', [$other, $item]))->assertNotFound();
        $this->actingAs(User::factory()->create(['role' => UserRole::Student]))->delete($url)->assertForbidden();
        $this->actingAs($officer);
        foreach ([GeneratedBatchStatus::Submitted, GeneratedBatchStatus::Approved, GeneratedBatchStatus::Rejected] as $status) {
            $batch->update(['status' => $status]);
            $this->delete($url)->assertForbidden();
        }
        $batch->update(['status' => GeneratedBatchStatus::Saved, 'submission_version' => 1]);
        $this->delete($url)->assertForbidden();
        $batch->update(['submission_version' => 0, 'submitted_roster' => ['members' => $batch->rosterMembers()]]);
        $this->delete($url)->assertForbidden();
        $batch->update(['submitted_roster' => null, 'fassg_assigned_by_id' => $officer->id]);
        $this->delete($url)->assertForbidden();
        $batch->update(['fassg_assigned_by_id' => null]);
        \App\Models\SponsorApproval::query()->create(['sponsorship_program_id' => $program->id,
            'generated_batch_id' => $batch->id, 'confirmation_status' => \App\Enums\ConfirmationStatus::Pending]);
        $this->delete($url)->assertForbidden();
        $this->delete(route('fassg.generated-batches.destroy', $batch))->assertForbidden();
        $this->post(route('fassg.applications.create-batch'), ['existing_fixed_list_id' => $batch->id,
            'sponsorship_program_id' => $program->id, 'fixed_list_source' => $fixed->fixed_list_id])
            ->assertSessionHasErrors('existing_fixed_list_id');
        $this->assertSame(1, $batch->items()->count());
        $this->assertSame(ApplicationStatus::Pending, $item->application->fresh()->status);
    }

    public function test_application_queue_excludes_auto_provisioned_and_shows_endorsed_badge(): void
    {
        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create();

        $mariaProfile = StudentProfile::factory()->verified()->create(['student_id_number' => '2024-00021']);
        $mariaProfile->user->update(['name' => 'Maria Santos']);

        $princeProfile = StudentProfile::factory()->verified()->create(['student_id_number' => '2024-00022']);
        $princeProfile->user->update(['name' => 'Prince Garcia']);

        Application::factory()->create([
            'sponsorship_program_id' => $program->id,
            'student_profile_id' => $mariaProfile->id,
            'gpa_submitted' => 0.00,
            'status' => ApplicationStatus::Verified,
            'is_auto_provisioned' => true,
        ]);

        $princeApp = $this->makeVerifiedApplication($program, $princeProfile, 1.80);

        $finalized = FixedList::factory()->create([
            'sponsorship_program_id' => $program->id,
            'status' => FixedListStatus::Finalized,
        ]);
        FixedListItem::factory()->create([
            'fixed_list_id' => $finalized->id,
            'student_id_number' => $princeProfile->student_id_number,
            'is_sle_fhe_verified' => true,
            'is_manually_endorsed' => true,
        ]);

        $this->get(route('fassg.applications.index', ['program_id' => $program->id]))
            ->assertOk()
            ->assertSee('Prince Garcia')
            ->assertSee('On Finalized Fixed List')
            ->assertDontSee('Maria Santos');
    }

    private function makeVerifiedApplication(SponsorshipProgram $program, StudentProfile $profile, float $gpa): Application
    {
        return Application::factory()->create([
            'sponsorship_program_id' => $program->id,
            'student_profile_id' => $profile->id,
            'gpa_submitted' => $gpa,
            'status' => ApplicationStatus::Verified,
            'verified_at' => now(),
        ]);
    }

    public function test_fixed_list_optional_qualifications_remain_null_after_explicit_fassg_review(): void
    {
        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create(['min_gpa' => null, 'required_documents' => null, 'address_requirement' => null]);
        $fixed = $this->makeFixedCandidate($program);
        $this->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'Optional facts', 'sponsorship_program_id' => $program->id,
            'fixed_list_source' => $fixed->fixed_list_id,
        ])->assertSessionHasNoErrors();
        $batch = GeneratedBatch::query()->sole();
        $app = $batch->items()->sole()->application;
        $this->get(route('fassg.generated-batches.applications.show', [$batch, $app]))->assertOk()
            ->assertSee('Fixed-list qualification evidence')->assertSee('confirm_eligibility', false);
        $this->patch(route('fassg.generated-batches.applications.verify', [$batch, $app]), ['confirm_eligibility' => 1])
            ->assertSessionHasNoErrors()->assertSessionHas('status', 'FASSG verification recorded. Sponsor approval still requires the submitted Generated Batch.');
        $app->refresh();
        $this->assertSame(ApplicationStatus::Verified, $app->status);
        $this->assertNull($app->gpa_submitted);
        $this->assertNull($app->address_submitted);
        $this->assertNull($app->is_rural_submitted);
        $this->assertNull($app->approved_at);
        $this->assertNotNull($app->endorsed_at);
        $this->assertSame(GeneratedBatchStatus::Saved, $batch->fresh()->status);
        $this->assertDatabaseCount('sponsor_approvals', 0);
    }

    public function test_fixed_list_mandatory_qualification_and_document_requirements_cannot_be_bypassed(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create([
            'min_gpa' => 2.5, 'address_requirement' => 'Rural',
            'required_documents' => ['Report Card / Certificate of Grades'],
        ]);
        $fixed = $this->makeFixedCandidate($program);
        $this->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'Required facts', 'sponsorship_program_id' => $program->id,
            'fixed_list_source' => $fixed->fixed_list_id,
        ])->assertSessionHasNoErrors();
        $batch = GeneratedBatch::query()->sole();
        $app = $batch->items()->sole()->application;
        $before = $app->getRawOriginal();
        $this->patch(route('fassg.generated-batches.applications.verify', [$batch, $app]), ['confirm_eligibility' => 1])
            ->assertSessionHasErrors(['gpa_submitted', 'address_submitted', 'is_rural_submitted']);
        $this->assertSame($before, $app->fresh()->getRawOriginal());
        $this->patch(route('fassg.generated-batches.applications.verify', [$batch, $app]), [
            'confirm_eligibility' => 1, 'confirm_gwa' => 1, 'confirm_address' => 1,
            'gpa_submitted' => 1.75, 'address_submitted' => 'Actual documented address', 'is_rural_submitted' => 1,
        ])->assertSessionHasErrors('application');
        $this->assertSame($before, $app->fresh()->getRawOriginal());
    }


    public function test_finalized_fixed_list_entries_and_original_endorsements_cannot_be_changed(): void
    {
        $this->actingAsFassg();
        $fixed = $this->makeFixedCandidate(SponsorshipProgram::factory()->create());
        $before = $fixed->getRawOriginal();
        foreach (['fassg.fixed-lists.items.endorse', 'fassg.fixed-lists.items.verify'] as $route) {
            $this->patch(route($route, [$fixed->fixedList, $fixed]))->assertForbidden();
            $this->assertSame($before, $fixed->fresh()->getRawOriginal());
        }
    }

    public function test_deleted_draft_does_not_strand_or_recreate_pending_fixed_list_processing_records(): void
    {
        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create();
        $fixed = $this->makeFixedCandidate($program);
        $payload = ['batch_name' => 'First draft', 'sponsorship_program_id' => $program->id,
            'fixed_list_source' => $fixed->fixed_list_id];
        $this->post(route('fassg.applications.create-batch'), $payload)->assertSessionHasNoErrors();
        $batch = GeneratedBatch::query()->sole();
        $app = $batch->items()->sole()->application;
        $this->delete(route('fassg.generated-batches.destroy', $batch))->assertSessionHasNoErrors();
        $this->post(route('fassg.applications.create-batch'), array_replace($payload, ['batch_name' => 'Second draft']))
            ->assertSessionHasNoErrors();
        $newBatch = GeneratedBatch::query()->sole();
        $this->assertSame($app->id, $newBatch->items()->sole()->application_id);
        $this->assertSame(ApplicationStatus::Pending, $app->fresh()->status);
        $this->assertNull($app->fresh()->gpa_submitted);
        $this->assertDatabaseCount('applications', 1);
    }

    public function test_stale_fixed_list_flag_without_canonical_verification_cannot_finalize_a_list(): void
    {
        $this->actingAsFassg();
        $list = FixedList::factory()->create(['status' => FixedListStatus::Draft]);
        FixedListItem::factory()->create(['fixed_list_id' => $list->id,
            'is_sle_fhe_verified' => true, 'is_manually_endorsed' => true]);
        $this->patch(route('fassg.fixed-lists.finalize', $list))->assertSessionHasErrors('list');
        $this->assertSame(FixedListStatus::Draft, $list->fresh()->status);
    }

}
