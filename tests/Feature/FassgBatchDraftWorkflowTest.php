<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
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
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FassgBatchDraftWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function pending(SponsorshipProgram $program, array $attributes = []): Application
    {
        return Application::factory()->create(array_replace([
            'sponsorship_program_id' => $program->id,
            'student_profile_id' => StudentProfile::factory()->verified(),
            'status' => ApplicationStatus::Pending, 'verified_at' => null, 'approved_at' => null,
            'gpa_submitted' => 2.0, 'submitted_at' => now()->subDay(),
        ], $attributes))->fresh();
    }

    private function saveDraft(SponsorshipProgram $program, array $applications, ?int $fixedListId = null): GeneratedBatch
    {
        $this->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'Draft for individual review', 'sponsorship_program_id' => $program->id,
            'selected_applications' => $applications, 'fixed_list_source' => $fixedListId,
        ])->assertSessionHasNoErrors();

        return GeneratedBatch::query()->latest('id')->firstOrFail();
    }

    private function checkboxIds(string $html, bool $checkedOnly = false): array
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $expression = '//input[contains(concat(" ", normalize-space(@class), " "), " app-checkbox ")]';
        if ($checkedOnly) {
            $expression .= '[@checked]';
        }
        $ids = [];
        foreach ($xpath->query($expression) as $element) {
            $ids[] = (int) $element->getAttribute('value');
        }

        return $ids;
    }

    public function test_two_slots_show_all_five_pending_applicants_in_neutral_order_without_preselection_or_writes(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Fassg]));
        $program = SponsorshipProgram::factory()->create(['total_slots' => 2, 'available_slots' => 2]);
        $newest = $this->pending($program, ['submitted_at' => now(), 'gpa_submitted' => 1.0]);
        $oldest = $this->pending($program, ['submitted_at' => now()->subDays(3), 'gpa_submitted' => 2.4]);
        $tieFirst = $this->pending($program, ['submitted_at' => now()->subDays(2)]);
        $tieSecond = $this->pending($program, ['submitted_at' => $tieFirst->submitted_at, 'gpa_submitted' => 1.0]);
        $missingDate = $this->pending($program, ['submitted_at' => null]);
        $before = Application::query()->orderBy('id')->get()->map->getRawOriginal()->all();

        $response = $this->get(route('fassg.applications.index', ['program_id' => $program->id]))
            ->assertOk()->assertSee('Showing all 5 applicant(s)');
        $this->assertSame([$oldest->id, $tieFirst->id, $tieSecond->id, $newest->id, $missingDate->id],
            $this->checkboxIds($response->getContent()));
        $this->assertSame([], $this->checkboxIds($response->getContent(), true));
        $this->assertSame($before, Application::query()->orderBy('id')->get()->map->getRawOriginal()->all());
        $this->assertDatabaseCount('generated_batches', 0);
    }

    public function test_capacity_never_limits_queue_rows_and_zero_capacity_selects_nobody(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Fassg]));
        $program = SponsorshipProgram::factory()->create(['total_slots' => 0, 'available_slots' => 0]);
        for ($i = 0; $i < 17; $i++) {
            $this->pending($program);
        }
        $response = $this->get(route('fassg.applications.index', ['program_id' => $program->id]))->assertOk();
        $this->assertCount(17, $this->checkboxIds($response->getContent()));
        $this->assertSame([], $this->checkboxIds($response->getContent(), true));
    }

    public function test_manual_replacement_is_saved_exactly_and_does_not_verify_or_approve_anyone(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Fassg]));
        $program = SponsorshipProgram::factory()->create(['total_slots' => 2, 'available_slots' => 2]);
        $apps = collect(range(1, 5))->map(fn () => $this->pending($program));
        $unselected = $apps->take(3)->map->getRawOriginal()->all();
        $batch = $this->saveDraft($program, [$apps[3]->id, $apps[4]->id]);

        $this->assertSame([$apps[3]->id, $apps[4]->id], $batch->items()->orderBy('application_id')->pluck('application_id')->all());
        $this->assertSame(GeneratedBatchStatus::Saved, $batch->status);
        $this->assertNull($batch->submitted_roster);
        foreach ($apps as $app) {
            $app->refresh();
            $this->assertSame(ApplicationStatus::Pending, $app->status);
            $this->assertNull($app->verified_at);
            $this->assertNull($app->approved_at);
            $this->assertNull($app->studentProfile->fresh()->active_sponsorship_id);
        }
        $this->assertSame($unselected, $apps->take(3)->map->getRawOriginal()->all());
        $this->assertSame(2, (int) $program->fresh()->getRawOriginal('available_slots'));
        $this->assertDatabaseCount('sponsor_approvals', 0);
    }

    public function test_pending_members_require_explicit_batch_review_before_submission(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['role' => UserRole::Fassg]));
        $program = SponsorshipProgram::factory()->create(['total_slots' => 2, 'available_slots' => 2]);
        $first = $this->pending($program);
        $second = $this->pending($program);
        $this->get(route('fassg.applications.show', $first))->assertOk()
            ->assertDontSee('name="confirm_eligibility"', false)
            ->assertSee('Reject Application')->assertSee('Request Document Resubmission');
        $batch = $this->saveDraft($program, [$first->id, $second->id]);
        $this->get(route('fassg.generated-batches.applications.show', [$batch, $first]))
            ->assertOk()->assertSee('Verify Batch Member')->assertSee('name="confirm_eligibility"', false);
        $payload = ['confirm_eligibility' => 1, 'confirm_gwa' => 1];

        // Legacy direct verification cannot bypass the batch review action.
        $this->patch(route('fassg.applications.verify', $first), $payload)->assertSessionHasErrors('batch');
        $this->patch(route('fassg.generated-batches.submit', $batch))->assertSessionHasErrors('batch');
        $this->patch(route('fassg.generated-batches.applications.verify', [$batch, $first]), $payload)->assertSessionHasNoErrors();
        $this->assertSame(ApplicationStatus::Verified, $first->fresh()->status);
        $this->assertSame(ApplicationStatus::Pending, $second->fresh()->status);
        $this->assertNull($first->fresh()->approved_at);
        $this->patch(route('fassg.generated-batches.submit', $batch))->assertSessionHasErrors('batch');
        $this->patch(route('fassg.generated-batches.applications.verify', [$batch, $second]), $payload)->assertSessionHasNoErrors();
        $this->patch(route('fassg.generated-batches.submit', $batch))->assertSessionHasNoErrors();
        $this->assertSame(GeneratedBatchStatus::Submitted, $batch->fresh()->status);
        $this->assertTrue($batch->fresh()->hasIntactSubmittedRoster());
        $this->assertDatabaseCount('sponsor_approvals', 0);
        $this->assertNull($first->studentProfile->fresh()->active_sponsorship_id);
        $before = $first->fresh()->getRawOriginal();
        $this->patch(route('fassg.generated-batches.applications.verify', [$batch, $first]), $payload)->assertForbidden();
        $this->assertSame($before, $first->fresh()->getRawOriginal());
    }

    public function test_qualification_and_document_failures_are_blocked_during_batch_review_without_status_changes(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['role' => UserRole::Fassg]));
        $program = SponsorshipProgram::factory()->create(['min_gpa' => 2.5,
            'required_documents' => ['Report Card / Certificate of Grades']]);
        $app = $this->pending($program, ['gpa_submitted' => 3.0]);
        $batch = $this->saveDraft($program, [$app->id]);
        $before = $app->fresh()->getRawOriginal();
        $this->patch(route('fassg.generated-batches.applications.verify', [$batch, $app]), [
            'confirm_eligibility' => 1, 'confirm_gwa' => 1,
        ])->assertSessionHasErrors('application');
        $this->assertSame($before, $app->fresh()->getRawOriginal());
        $this->patch(route('fassg.generated-batches.submit', $batch))->assertSessionHasErrors('batch');
    }

    public function test_batch_member_review_enforces_membership_and_fassg_authorization(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Fassg]));
        $program = SponsorshipProgram::factory()->create();
        $member = $this->pending($program);
        $outsider = $this->pending($program);
        $batch = $this->saveDraft($program, [$member->id]);
        $payload = ['confirm_eligibility' => 1, 'confirm_gwa' => 1];
        $this->get(route('fassg.generated-batches.applications.show', [$batch, $outsider]))->assertNotFound();
        $this->patch(route('fassg.generated-batches.applications.verify', [$batch, $outsider]), $payload)->assertNotFound();
        foreach ([UserRole::Student, UserRole::Sponsor, UserRole::Accounting] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->get(route('fassg.generated-batches.applications.show', [$batch, $member]))->assertForbidden();
            $this->patch(route('fassg.generated-batches.applications.verify', [$batch, $member]), $payload)->assertForbidden();
        }
        $this->assertSame(ApplicationStatus::Pending, $member->fresh()->status);
    }

    public function test_fixed_list_available_tag_does_not_require_a_matching_ordinary_applicant(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Fassg]));
        $program = SponsorshipProgram::factory()->create();
        FixedList::factory()->create(['sponsorship_program_id' => $program->id, 'status' => FixedListStatus::Draft]);
        $this->get(route('fassg.applications.index', ['program_id' => $program->id]))
            ->assertOk()->assertSee('Fixed List Available')->assertViewHas('finalizedFixedLists', fn ($lists) => $lists->isEmpty());
        $app = $this->pending($program);
        $this->get(route('fassg.applications.index'))->assertOk()->assertSee('Fixed List Available');
        $this->assertDatabaseCount('applications', 1);
        $this->assertSame(ApplicationStatus::Pending, $app->fresh()->status);
    }

    public function test_mixed_pending_draft_keeps_original_sources_and_deduplicates_students(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $this->actingAs($fassg);
        $program = SponsorshipProgram::factory()->create(['total_slots' => 2, 'available_slots' => 2, 'min_gpa' => null]);
        $ordinary = $this->pending($program);
        $profile = StudentProfile::factory()->verified()->create();
        $list = FixedList::factory()->create(['sponsorship_program_id' => $program->id, 'status' => FixedListStatus::Finalized]);
        $fixed = FixedListItem::factory()->create(['fixed_list_id' => $list->id,
            'student_id_number' => $profile->student_id_number, 'student_name' => $profile->user->name,
            'is_sle_fhe_verified' => true, 'is_manually_endorsed' => true,
            'endorsed_by_id' => $fassg->id, 'endorsed_at' => now(), 'status' => \App\Enums\FixedListItemStatus::Endorsed]);
        $originalSource = $fixed->fresh()->getRawOriginal();
        $batch = $this->saveDraft($program, [$ordinary->id, $ordinary->id], $list->id);
        $this->assertSame(2, $batch->items()->count());
        $this->assertFalse($batch->items()->where('application_id', $ordinary->id)->sole()->is_fixed_list);
        $fixedMember = $batch->items()->where('origin_type', 'fixed_list')->sole();
        $this->assertSame($fixed->id, $fixedMember->fixed_list_item_id);
        $this->assertTrue($fixedMember->application->is_auto_provisioned);
        $this->assertNull($fixedMember->application->submitted_at);
        $this->assertNull($fixedMember->application->gpa_submitted);
        $this->assertSame(ApplicationStatus::Pending, $fixedMember->application->status);
        $this->assertSame($originalSource, $fixed->fresh()->getRawOriginal());
        $this->patch(route('fassg.generated-batches.submit', $batch))->assertSessionHasErrors('batch');
    }

    public function test_pending_overflow_and_stale_capacity_requests_fail_atomically(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Fassg]));
        $program = SponsorshipProgram::factory()->create(['total_slots' => 2, 'available_slots' => 2]);
        $apps = collect(range(1, 5))->map(fn () => $this->pending($program));
        $before = $apps->map->getRawOriginal()->all();
        $payload = ['batch_name' => 'Overflow', 'sponsorship_program_id' => $program->id];
        $this->post(route('fassg.applications.create-batch'), $payload + ['selected_applications' => $apps->pluck('id')->all()])
            ->assertSessionHasErrors('selected_applications');
        $program->update(['available_slots' => 1]);
        $this->post(route('fassg.applications.create-batch'), $payload + ['selected_applications' => $apps->take(2)->pluck('id')->all()])
            ->assertSessionHasErrors('selected_applications');
        $this->assertDatabaseCount('generated_batches', 0);
        $this->assertDatabaseCount('generated_batch_items', 0);
        $this->assertSame($before, $apps->map(fn ($app) => $app->fresh()->getRawOriginal())->all());
    }

    public function test_preparation_links_share_the_modal_and_never_preselect_ordinary_applicants(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Fassg]));
        $program = SponsorshipProgram::factory()->create();
        $ordinary = $this->pending($program);
        $finalized = FixedList::factory()->create(['sponsorship_program_id' => $program->id, 'status' => FixedListStatus::Finalized]);
        $draft = FixedList::factory()->create(['sponsorship_program_id' => $program->id, 'status' => FixedListStatus::Draft]);
        $sourceUrl = route('fassg.applications.index', ['program_id' => $program->id,
            'fixed_list_id' => $finalized->id, 'prepare_batch' => 1]);
        $this->get(route('fassg.fixed-lists.show', $finalized))->assertOk()
            ->assertSee('Create Batch from This List')->assertSee(e($sourceUrl), false);
        $this->get(route('fassg.fixed-lists.show', $draft))->assertOk()->assertDontSee('Create Batch from This List');
        $this->get(route('fassg.programs.index'))->assertOk()->assertSee('Prepare Generated Batch')
            ->assertSee(e(route('fassg.applications.index', ['program_id' => $program->id, 'prepare_batch' => 1])), false);
        $before = $ordinary->getRawOriginal();
        foreach ([$sourceUrl, route('fassg.applications.index', ['program_id' => $program->id, 'prepare_batch' => 1])] as $url) {
            $response = $this->get($url)->assertOk()->assertViewHas('openBatchModal', true)
                ->assertViewHas('selectedProgramId', $program->id);
            $this->assertSame([], $this->checkboxIds($response->getContent(), true));
        }
        $this->assertSame($before, $ordinary->fresh()->getRawOriginal());
        $this->assertDatabaseCount('generated_batches', 0);
        $this->assertDatabaseCount('applications', 1);
    }

    public function test_source_list_context_must_be_finalized_and_match_the_program(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Fassg]));
        $program = SponsorshipProgram::factory()->create();
        $list = FixedList::factory()->create(['sponsorship_program_id' => $program->id, 'status' => FixedListStatus::Finalized]);
        $this->get(route('fassg.applications.index', ['fixed_list_id' => $list->id, 'prepare_batch' => 1]))
            ->assertOk()->assertViewHas('selectedProgramId', $program->id)->assertViewHas('selectedFixedListId', $list->id);
        $other = SponsorshipProgram::factory()->create();
        $this->get(route('fassg.applications.index', ['fixed_list_id' => $list->id, 'program_id' => $other->id]))->assertNotFound();
        $list->update(['status' => FixedListStatus::Draft]);
        $this->get(route('fassg.applications.index', ['fixed_list_id' => $list->id, 'program_id' => $program->id]))->assertNotFound();
        $this->assertDatabaseCount('generated_batches', 0);
    }
}
