<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\FixedListItemStatus;
use App\Enums\FixedListStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\FixedList;
use App\Models\FixedListItem;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixedListEndorsementTest extends TestCase
{
    use RefreshDatabase;

    private function candidate(FixedList $list, bool $verified = true): FixedListItem
    {
        $factory = StudentProfile::factory();
        $profile = ($verified ? $factory->verified() : $factory)->create();

        return FixedListItem::factory()->create([
            'fixed_list_id' => $list->id, 'student_id_number' => $profile->student_id_number,
            'is_fixed_list' => true, 'is_sle_fhe_verified' => true,
            'is_manually_endorsed' => false, 'status' => FixedListItemStatus::Verified,
        ]);
    }

    public function test_individual_and_bulk_record_the_same_ordinary_endorsement_without_approval_or_provisioning(): void
    {
        $this->freezeTime();
        $officer = User::factory()->create(['role' => UserRole::Fassg]);
        $this->actingAs($officer);
        $list = FixedList::factory()->create(['status' => FixedListStatus::Draft]);
        $single = $this->candidate($list);
        $bulk = $this->candidate($list);
        $evidence = $bulk->studentProfile->sleFheVerification->getRawOriginal();

        $this->patch(route('fassg.fixed-lists.items.endorse', [$list, $single]))->assertSessionHasNoErrors();
        $this->patch(route('fassg.fixed-lists.items.endorse-selected', $list), ['selected_items' => [$bulk->id]])
            ->assertSessionHasNoErrors();
        $fields = ['status', 'is_manually_endorsed', 'endorsed_by_id', 'endorsed_at'];
        $this->assertEquals($single->fresh()->only($fields), $bulk->fresh()->only($fields));
        $this->assertSame(FixedListItemStatus::Endorsed, $bulk->fresh()->status);
        $this->assertSame($officer->id, $bulk->fresh()->endorsed_by_id);
        $this->assertSame($evidence, $bulk->studentProfile->sleFheVerification->fresh()->getRawOriginal());
        $this->assertDatabaseCount('applications', 0);
        $this->assertDatabaseCount('sponsor_approvals', 0);
        $this->assertNull($bulk->studentProfile->fresh()->active_sponsorship_id);
        foreach (AuditLog::query()->where('action', 'fassg.fixed_list.item_endorsed')->get() as $audit) {
            $details = json_decode($audit->details, true);
            $this->assertSame($list->id, $details['fixed_list_id']);
            $this->assertContains($details['item_id'], [$single->id, $bulk->id]);
            $this->assertFalse($details['before']['is_manually_endorsed']);
            $this->assertTrue($details['after']['is_manually_endorsed']);
        }
        $this->get(route('fassg.fixed-lists.show', $list))->assertOk()
            ->assertSee('Sponsor-proposed Fixed List')->assertSee('Generated Batch review')
            ->assertDontSee('as outside-criteria')->assertDontSee('locked into top slots')
            ->assertDontSee('Endorsed by Sponsor');
    }

    public function test_duplicate_ids_and_retries_never_revoke_or_overwrite_original_endorsement(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Fassg]));
        $list = FixedList::factory()->create(['status' => FixedListStatus::Draft]);
        $item = $this->candidate($list);
        $route = route('fassg.fixed-lists.items.endorse-selected', $list);
        $this->patch($route, ['selected_items' => [$item->id, $item->id]])->assertSessionHasNoErrors();
        $before = $item->fresh()->getRawOriginal();
        $this->actingAs(User::factory()->create(['role' => UserRole::Fassg]));
        $this->patch($route, ['selected_items' => [$item->id]])->assertSessionHasNoErrors();
        $this->patch(route('fassg.fixed-lists.items.endorse', [$list, $item]))->assertSessionHasNoErrors();
        $this->assertSame($before, $item->fresh()->getRawOriginal());
        $this->assertSame(1, AuditLog::query()->where('action', 'fassg.fixed_list.item_endorsed')->count());
    }

    public function test_invalid_candidates_are_rejected_by_both_actions_without_partial_bulk_updates(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Fassg]));
        foreach (['missing profile', 'no verification', 'stale flag', 'ineligible', 'wrong course', 'wrong campus', 'wrong year', 'active sponsorship'] as $case) {
            $list = FixedList::factory()->create(['status' => FixedListStatus::Draft]);
            $valid = $this->candidate($list);
            $invalid = $this->candidate($list, $case !== 'no verification');
            if ($case === 'missing profile') $invalid->update(['student_id_number' => 'missing-profile']);
            if ($case === 'stale flag') $invalid->update(['is_sle_fhe_verified' => false]);
            if ($case === 'ineligible') $invalid->update(['status' => FixedListItemStatus::Ineligible]);
            if ($case === 'wrong course') $invalid->studentProfile->update(['course' => 'Different course']);
            if ($case === 'wrong course') $list->sponsorshipProgram->update(['target_course' => $valid->studentProfile->course]);
            if ($case === 'wrong campus') {
                $valid->studentProfile->update(['campus' => 'Main']);
                $list->sponsorshipProgram->update(['eligible_campuses' => ['Main']]);
            }
            if ($case === 'wrong year') {
                $invalid->studentProfile->update(['year_level' => 1]);
                $list->sponsorshipProgram->update(['eligible_year_levels' => [3]]);
            }
            if ($case === 'active sponsorship') {
                Application::factory()->create(['student_profile_id' => $invalid->studentProfile->id,
                    'status' => ApplicationStatus::Approved, 'approved_at' => now()]);
            }
            $before = [$valid->fresh()->getRawOriginal(), $invalid->fresh()->getRawOriginal()];
            $auditCount = AuditLog::query()->count();
            $this->patch(route('fassg.fixed-lists.items.endorse', [$list, $invalid]))->assertSessionHasErrors('endorsement');
            $this->patch(route('fassg.fixed-lists.items.endorse-selected', $list), ['selected_items' => [$valid->id, $invalid->id]])
                ->assertSessionHasErrors('endorsement');
            $this->assertSame($before, [$valid->fresh()->getRawOriginal(), $invalid->fresh()->getRawOriginal()], $case);
            $this->assertSame($auditCount, AuditLog::query()->count());
        }
    }

    public function test_foreign_or_missing_ids_and_empty_selections_cannot_partially_endorse_a_list(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Fassg]));
        $list = FixedList::factory()->create(['status' => FixedListStatus::Draft]);
        $item = $this->candidate($list);
        $foreign = $this->candidate(FixedList::factory()->create(['status' => FixedListStatus::Draft]));
        $before = $item->fresh()->getRawOriginal();
        foreach ([[], [$item->id, $foreign->id], [$item->id, 999999999]] as $ids) {
            $this->patch(route('fassg.fixed-lists.items.endorse-selected', $list), ['selected_items' => $ids])
                ->assertSessionHasErrors('selected_items');
        }
        $this->patch(route('fassg.fixed-lists.items.endorse', [$list, $foreign]))->assertNotFound();
        $this->assertSame($before, $item->fresh()->getRawOriginal());
        $this->assertFalse($foreign->fresh()->is_manually_endorsed);
    }

    public function test_ordinary_endorsement_rejects_explicit_criteria_override_requests_even_with_a_reason(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Fassg]));
        $list = FixedList::factory()->create(['status' => FixedListStatus::Draft]);
        $item = $this->candidate($list);
        $before = $item->fresh()->getRawOriginal();
        foreach (['outside_criteria', 'is_outside_criteria', 'override_criteria', 'exception_reason'] as $field) {
            $payload = [$field => $field === 'exception_reason' ? 'A claimed exception is not authorization.' : 1];
            $this->patch(route('fassg.fixed-lists.items.endorse', [$list, $item]), $payload)->assertSessionHasErrors($field);
            $this->patch(route('fassg.fixed-lists.items.endorse-selected', $list), $payload + ['selected_items' => [$item->id]])
                ->assertSessionHasErrors($field);
        }
        $this->assertSame($before, $item->fresh()->getRawOriginal());
    }

    public function test_role_and_finalized_list_protection_apply_to_single_and_bulk_requests(): void
    {
        $list = FixedList::factory()->create(['status' => FixedListStatus::Draft]);
        $item = $this->candidate($list);
        foreach ([UserRole::Student, UserRole::Sponsor, UserRole::Accounting, UserRole::Admin] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->patch(route('fassg.fixed-lists.items.endorse', [$list, $item]))->assertForbidden();
            $this->patch(route('fassg.fixed-lists.items.endorse-selected', $list), ['selected_items' => [$item->id]])->assertForbidden();
        }
        $this->actingAs(User::factory()->create(['role' => UserRole::Fassg]));
        $list->update(['status' => FixedListStatus::Finalized]);
        $this->patch(route('fassg.fixed-lists.items.endorse', [$list, $item]))->assertForbidden();
        $this->patch(route('fassg.fixed-lists.items.endorse-selected', $list), ['selected_items' => [$item->id]])->assertForbidden();
        $this->assertFalse($item->fresh()->is_manually_endorsed);
    }

    public function test_finalization_rechecks_eligibility_without_rewriting_previous_endorsements(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Fassg]));
        $list = FixedList::factory()->create(['status' => FixedListStatus::Draft]);
        $item = $this->candidate($list);
        $this->patch(route('fassg.fixed-lists.items.endorse', [$list, $item]))->assertSessionHasNoErrors();
        $before = $item->fresh()->getRawOriginal();
        $list->sponsorshipProgram->update(['target_course' => 'A different required course']);
        $this->patch(route('fassg.fixed-lists.finalize', $list))->assertSessionHasErrors('list');
        $this->assertSame(FixedListStatus::Draft, $list->fresh()->status);
        $this->assertSame($before, $item->fresh()->getRawOriginal());
    }
}
