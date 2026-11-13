<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\ConfirmationStatus;
use App\Enums\FixedListItemStatus;
use App\Enums\FixedListStatus;
use App\Enums\GeneratedBatchStatus;
use App\Enums\ProgramCategory;
use App\Enums\ProgramStatus;
use App\Enums\UserRole;
use App\Models\AcademicProgram;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\BatchCandidate;
use App\Models\FixedList;
use App\Models\FixedListItem;
use App\Models\GeneratedBatch;
use App\Models\Sponsor;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\ApplicationStatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SponsorAccountingReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sponsor_without_profile_can_open_lists_page(): void
    {
        $sponsorUser = User::factory()->create(['role' => UserRole::Sponsor]);

        $this->actingAs($sponsorUser)
            ->get(route('sponsor.lists.index'))
            ->assertOk();

        $this->assertDatabaseHas('sponsors', [
            'user_id' => $sponsorUser->id,
            'contact_email' => $sponsorUser->email,
        ]);
    }

    public function test_sponsor_can_review_forwarded_list_upload_and_confirm(): void
    {
        Storage::fake('local');
        $program = SponsorshipProgram::factory()->create();
        $batch = $this->submittedBatchWithDocument($program, ['ranked_queue']);
        $application = $batch->items->first()->application;
        $originalEvidence = $batch->currentApproval()->getRawOriginal();
        $this->actingAs($program->sponsor->user)->get(route('sponsor.lists.show', $batch))
            ->assertOk()->assertSee($application->studentProfile->student_id_number);
        $this->post(route('sponsor.approvals.store', $batch), [
            'submission_version' => 1,
            'approval_document' => UploadedFile::fake()->create('signed-approval.pdf', 120, 'application/pdf'),
        ])->assertRedirect(route('sponsor.lists.show', $batch))->assertSessionHasNoErrors();
        $this->assertSame(2, $batch->sponsorApprovals()->count());
        $this->assertSame($originalEvidence, $batch->sponsorApprovals()->oldest('id')->first()->getRawOriginal());
        $this->assertSame(ApplicationStatus::Verified, $application->fresh()->status);
        $this->patch(route('sponsor.approvals.confirm', $batch), $this->decisionPayload($batch))->assertSessionHasNoErrors();
        $this->assertSame(ApplicationStatus::Approved, $application->fresh()->status);
        $this->assertSame($application->id, $application->studentProfile->fresh()->active_sponsorship_id);
        $this->assertSame($program->sponsor->user_id, $batch->fresh()->currentApproval()->decided_by_id);
        $this->assertNull($batch->fresh()->fassg_assigned_by_id);
        $this->assertNull($batch->fresh()->currentApproval()->fassg_recorded_at);
        $this->assertNull($batch->fresh()->currentApproval()->accounting_forwarded_at);
    }

    public function test_sponsor_cannot_review_another_sponsors_list(): void
    {
        $sponsor = Sponsor::factory()->create();
        $otherList = GeneratedBatch::factory()->create(['status' => GeneratedBatchStatus::Submitted]);

        $this->actingAs($sponsor->user)
            ->get(route('sponsor.lists.show', $otherList))
            ->assertForbidden();
    }

    public function test_sponsor_can_filter_forwarded_applicants_by_academic_program(): void
    {
        Storage::fake('local');
        $program = SponsorshipProgram::factory()->create();
        $batch = $this->submittedBatchWithDocument($program, ['ranked_queue', 'fixed_list']);
        $it = AcademicProgram::factory()->create(['name' => 'BS Information Technology']);
        $cs = AcademicProgram::factory()->create(['name' => 'BS Computer Science']);
        $itProfile = $batch->items->first()->application->studentProfile;
        $csProfile = $batch->items->last()->application->studentProfile;
        $itProfile->update(['academic_program_id' => $it->program_id]);
        $csProfile->update(['academic_program_id' => $cs->program_id]);

        $this->actingAs($program->sponsor->user)
            ->get(route('sponsor.applicants.index', ['academic_program_id' => $it->program_id]))
            ->assertOk()->assertSee($itProfile->user->name)->assertDontSee($csProfile->user->name);
    }

    public function test_accounting_can_filter_beneficiaries_by_academic_program(): void
    {
        Storage::fake('local');
        $program = SponsorshipProgram::factory()->create();
        $batch = $this->submittedBatchWithDocument($program, ['ranked_queue', 'fixed_list']);
        $it = AcademicProgram::factory()->create(['name' => 'BS Information Technology']);
        $cs = AcademicProgram::factory()->create(['name' => 'BS Computer Science']);
        $itProfile = $batch->items->first()->application->studentProfile;
        $csProfile = $batch->items->last()->application->studentProfile;
        $itProfile->update(['academic_program_id' => $it->program_id]);
        $csProfile->update(['academic_program_id' => $cs->program_id]);
        $this->confirmAndForward($batch);
        $this->actingAs(User::factory()->create(['role' => UserRole::Accounting]))
            ->get(route('accounting.beneficiaries.index', ['academic_program_id' => $it->program_id]))
            ->assertOk()->assertSee($itProfile->user->name)->assertDontSee($csProfile->user->name);
    }

    public function test_standalone_sponsor_decisions_are_blocked_and_historical_documents_are_preserved(): void
    {
        Storage::fake('local');
        $program = SponsorshipProgram::factory()->create();
        $application = Application::factory()->create(['sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Verified, 'sponsor_approval_path' => 'legacy.pdf']);
        Storage::disk('local')->put('legacy.pdf', 'original historical evidence');
        $before = $this->approvalState();
        foreach (['sponsor.applicants.confirm', 'sponsor.applicants.reject'] as $route) {
            $this->actingAs($program->sponsor->user)->post(route($route, $application), [
                'approval_document' => UploadedFile::fake()->create('new.pdf', 10, 'application/pdf'),
                'rejection_reason' => 'Individual decision',
            ])->assertSessionHasErrors('approval');
            $this->assertSame($before, $this->approvalState());
            $this->assertSame(['legacy.pdf'], Storage::disk('local')->allFiles());
        }
        $this->get(route('sponsor.applicants.approval-document', $application))->assertOk();
    }

    public function test_sponsor_cannot_confirm_without_signed_document(): void
    {
        Storage::fake('local');
        $program = SponsorshipProgram::factory()->create();
        $batch = $this->submittedBatchWithDocument($program, ['ranked_queue']);
        Storage::disk('local')->delete($batch->currentApproval()->approval_document_path);
        $before = $this->approvalState();
        $this->actingAs($program->sponsor->user)
            ->patch(route('sponsor.approvals.confirm', $batch), $this->decisionPayload($batch))
            ->assertSessionHasErrors('approval');
        $this->assertSame($before, $this->approvalState());
    }

    public function test_approving_last_slot_preserves_remaining_applications_and_existing_records(): void
    {
        Storage::fake('local');
        Notification::fake();

        $sponsor = Sponsor::factory()->create();
        $program = SponsorshipProgram::factory()->create([
            'sponsor_id' => $sponsor->id,
            'total_slots' => 3,
            'available_slots' => 1,
        ]);
        $batch = $this->submittedBatchWithDocument($program, ['ranked_queue']);
        $application = $batch->items->first()->application;
        $approvedProfile = $application->studentProfile;
        $otherProgram = SponsorshipProgram::factory()->create(['sponsor_id' => $sponsor->id]);
        $otherProgramBefore = $otherProgram->fresh()->getRawOriginal();
        $untouched = [];
        foreach ([ApplicationStatus::Pending, ApplicationStatus::Verified, ApplicationStatus::Approved, ApplicationStatus::Ongoing] as $status) {
            $record = Application::factory()->create([
                'sponsorship_program_id' => $program->id,
                'status' => $status,
                'approved_at' => $status->isActiveSponsorship() ? now()->subDay() : null,
            ]);
            if ($status->isActiveSponsorship()) {
                $record->studentProfile->update(['active_sponsorship_id' => $record->id]);
            }
            $untouched[] = $record;
            $untouched[] = $record->studentProfile->fresh();
        }
        foreach ([ApplicationStatus::Pending, ApplicationStatus::Verified, ApplicationStatus::Rejected, ApplicationStatus::Expired] as $status) {
            $untouched[] = Application::factory()->create([
                'sponsorship_program_id' => $otherProgram->id,
                'status' => $status,
                'approved_at' => $status === ApplicationStatus::Expired ? now()->subMonth() : null,
                'rejection_reason' => $status === ApplicationStatus::Rejected ? 'Explicit Sponsor decision' : null,
            ]);
        }
        $before = array_map(fn ($record) => $record->fresh()->getRawOriginal(), $untouched);
        $membership = $batch->items->first();
        $membershipBefore = $membership->getRawOriginal();
        $this->actingAs($sponsor->user)
            ->patch(route('sponsor.approvals.confirm', $batch), $this->decisionPayload($batch))
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(ApplicationStatus::Approved, $application->fresh()->status);
        $this->assertNotNull($application->fresh()->approved_at);
        $this->assertSame($application->id, $approvedProfile->fresh()->active_sponsorship_id);
        $this->assertSame(0, (int) $program->fresh()->getRawOriginal('available_slots'));
        $this->assertSame(0, $program->fresh()->available_slots);
        $this->assertSame(ProgramStatus::Closed, $program->fresh()->status);
        $this->assertSame($before, array_map(fn ($record) => $record->fresh()->getRawOriginal(), $untouched));
        $this->assertSame($otherProgramBefore, $otherProgram->fresh()->getRawOriginal());
        $this->assertSame($membershipBefore, $membership->fresh()->getRawOriginal());
        $this->assertSame(GeneratedBatchStatus::Approved, $batch->fresh()->status);
        Notification::assertSentTo($approvedProfile->user, ApplicationStatusUpdated::class);
        Notification::assertCount(1);
    }

    #[DataProvider('fullProgramCases')]
    public function test_individual_confirmation_cannot_exceed_capacity_or_change_existing_records(
        int $rawSlots,
        ?ApplicationStatus $existingStatus,
    ): void {
        Storage::fake('local');
        Notification::fake();
        $sponsor = Sponsor::factory()->create();
        $program = SponsorshipProgram::factory()->create([
            'sponsor_id' => $sponsor->id,
            'total_slots' => 1,
            'available_slots' => $rawSlots,
        ]);
        if ($existingStatus !== null) {
            $existing = Application::factory()->create([
                'sponsorship_program_id' => $program->id,
                'status' => $existingStatus,
                'approved_at' => now()->subDay(),
            ]);
            $existing->studentProfile->update(['active_sponsorship_id' => $existing->id]);
        }
        $application = Application::factory()->create([
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Verified,
            'sponsor_approval_path' => 'sponsor-approvals/existing.pdf',
        ]);
        Storage::disk('local')->put($application->sponsor_approval_path, 'existing evidence');
        $before = $this->approvalState();
        $filesBefore = Storage::disk('local')->allFiles();

        $this->actingAs($sponsor->user)
            ->post(route('sponsor.applicants.confirm', $application), [
                'approval_document' => UploadedFile::fake()->create('new.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('approval');

        $this->assertSame($before, $this->approvalState());
        $this->assertSame($filesBefore, Storage::disk('local')->allFiles());
        $this->assertSame('existing evidence', Storage::disk('local')->get($application->sponsor_approval_path));
        Notification::assertNothingSent();
    }

    public static function fullProgramCases(): array
    {
        return [
            'raw counter full' => [0, null],
            'approved beneficiary fills quota despite stale counter' => [10, ApplicationStatus::Approved],
            'ongoing beneficiary fills quota despite open program accessor' => [10, ApplicationStatus::Ongoing],
        ];
    }

    #[DataProvider('batchSources')]
    public function test_explicit_batch_confirmation_fills_capacity_without_changing_other_applicants(array $origins): void
    {
        Storage::fake('local');
        Notification::fake();
        $sponsor = Sponsor::factory()->create();
        $program = SponsorshipProgram::factory()->create([
            'sponsor_id' => $sponsor->id,
            'total_slots' => count($origins),
            'available_slots' => count($origins),
        ]);
        $batch = $this->submittedBatchWithDocument($program, $origins);
        $before = $this->approvalState();
        $otherProgram = SponsorshipProgram::factory()->create(['sponsor_id' => $sponsor->id]);
        $untouched = [$otherProgram];
        foreach ([$program, $otherProgram] as $targetProgram) {
            foreach ([ApplicationStatus::Pending, ApplicationStatus::Verified] as $status) {
                $untouched[] = Application::factory()->create([
                    'sponsorship_program_id' => $targetProgram->id,
                    'status' => $status,
                ]);
            }
        }
        $untouchedBefore = array_map(fn ($record) => $record->fresh()->getRawOriginal(), $untouched);

        $this->actingAs($sponsor->user)->get(route('sponsor.lists.show', $batch))->assertOk();
        foreach ($batch->items as $item) {
            $this->assertSame(ApplicationStatus::Verified, $item->application->fresh()->status);
            $this->assertNull($item->application->fresh()->approved_at);
            $this->assertNull($item->application->studentProfile->fresh()->active_sponsorship_id);
        }
        $this->assertSame(GeneratedBatchStatus::Submitted, $batch->fresh()->status);
        $this->assertSame(ConfirmationStatus::Pending, $batch->latestApproval->confirmation_status);
        Notification::assertNothingSent();

        $this->actingAs($sponsor->user)->patch(route('sponsor.approvals.confirm', $batch), $this->decisionPayload($batch))
            ->assertRedirect()->assertSessionHasNoErrors();

        foreach ($batch->items as $item) {
            $application = $item->application->fresh();
            $this->assertSame(ApplicationStatus::Approved, $application->status);
            $this->assertNotNull($application->approved_at);
            $this->assertSame($application->id, $application->studentProfile->active_sponsorship_id);
            Notification::assertSentTo($application->studentProfile->user, ApplicationStatusUpdated::class);
        }
        $this->assertSame(GeneratedBatchStatus::Approved, $batch->fresh()->status);
        $this->assertTrue($batch->fresh()->latestApproval->isConfirmed());
        $this->assertSame(0, $program->fresh()->available_slots);
        $this->assertSame(0, (int) $program->fresh()->getRawOriginal('available_slots'));
        $this->assertSame(ProgramStatus::Closed, $program->fresh()->status);
        $this->assertSame($untouchedBefore, array_map(fn ($record) => $record->fresh()->getRawOriginal(), $untouched));
        $after = $this->approvalState();
        foreach (['generated_batch_items', 'fixed_lists', 'fixed_list_items'] as $table) {
            $this->assertSame($before[$table], $after[$table]);
        }
        $this->assertSame($before['audit_logs'][0], $after['audit_logs'][0]);
        $this->assertSame('existing signed document', Storage::disk('local')->get($batch->latestApproval->approval_document_path));
        Notification::assertCount(count($origins));
    }

    public static function batchSources(): array
    {
        return [
            'fixed list only' => [['fixed_list', 'fixed_list']],
            'ordinary applicants only' => [['ranked_queue', 'ranked_queue']],
            'mixed sources' => [['fixed_list', 'ranked_queue']],
        ];
    }

    #[DataProvider('batchCapacityFailures')]
    public function test_batch_capacity_failure_preserves_all_approval_records_and_documents(
        int $totalSlots,
        int $rawSlots,
        bool $hasOngoingBeneficiary,
        bool $failAfterFirstApproval,
    ): void {
        Storage::fake('local');
        Notification::fake();
        $sponsor = Sponsor::factory()->create();
        $program = SponsorshipProgram::factory()->create([
            'sponsor_id' => $sponsor->id,
            'total_slots' => $totalSlots,
            'available_slots' => $rawSlots,
        ]);
        if ($hasOngoingBeneficiary) {
            Application::factory()->create([
                'sponsorship_program_id' => $program->id,
                'status' => ApplicationStatus::Ongoing,
                'approved_at' => now()->subDay(),
            ]);
        }
        $batch = $this->submittedBatchWithDocument($program, ['fixed_list', 'ranked_queue']);
        $profile = $batch->items->first()->application->studentProfile;
        $expired = Application::factory()->create([
            'student_profile_id' => $profile->id,
            'status' => ApplicationStatus::Expired,
            'approved_at' => now()->subMonth(),
        ]);
        $profile->update(['active_sponsorship_id' => $expired->id]);
        $before = $this->approvalState();
        $filesBefore = Storage::disk('local')->allFiles();
        $failureInjected = false;
        $approvedBeforeFailure = 0;
        if ($failAfterFirstApproval) {
            // Fault injection after a real approval write: the next decrement must fail.
            Event::listen('eloquent.updated: '.SponsorshipProgram::class, function (SponsorshipProgram $updated) use ($program, &$failureInjected, &$approvedBeforeFailure): void {
                if ($updated->id === $program->id && ! $failureInjected) {
                    $failureInjected = true;
                    $approvedBeforeFailure = $program->applications()->where('status', ApplicationStatus::Approved)->count();
                    DB::table('sponsorship_programs')->where('id', $program->id)->update(['available_slots' => 0]);
                }
            });
        }

        $this->actingAs($sponsor->user)->patch(route('sponsor.approvals.confirm', $batch), $this->decisionPayload($batch))
            ->assertRedirect()->assertSessionHasErrors('approval');

        if ($failAfterFirstApproval) {
            $this->assertTrue($failureInjected);
            $this->assertSame(1, $approvedBeforeFailure);
        }
        $this->assertSame($before, $this->approvalState());
        $this->assertSame($filesBefore, Storage::disk('local')->allFiles());
        $this->assertSame('existing signed document', Storage::disk('local')->get($batch->latestApproval->approval_document_path));
        Notification::assertNothingSent();
    }

    public static function batchCapacityFailures(): array
    {
        return [
            'calculated quota insufficient' => [1, 10, false, false],
            'raw counter insufficient' => [10, 1, false, false],
            'no remaining slots' => [2, 0, false, false],
            'ongoing beneficiary occupies a slot' => [2, 10, true, false],
            'late failure rolls back earlier approvals' => [2, 2, false, true],
        ];
    }

    public function test_whole_batch_rejection_preserves_individual_qualification_and_unrelated_records(): void
    {
        Storage::fake('local');
        $program = SponsorshipProgram::factory()->create(['available_slots' => 0]);
        $batch = $this->submittedBatchWithDocument($program, ['fixed_list', 'ranked_queue']);
        Application::factory()->create(['sponsorship_program_id' => $program->id, 'status' => ApplicationStatus::Verified]);
        $before = $this->approvalState();
        $this->actingAs($program->sponsor->user)->patch(route('sponsor.approvals.reject', $batch), [
            'submission_version' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertSame(GeneratedBatchStatus::Rejected, $batch->fresh()->status);
        $this->assertSame(ConfirmationStatus::Rejected, $batch->fresh()->currentApproval()->confirmation_status);
        foreach (['applications', 'student_profiles', 'sponsorship_programs', 'generated_batch_items', 'fixed_list_items'] as $table) {
            $this->assertSame($before[$table], $this->approvalState()[$table]);
        }
        $this->assertSame(2, $batch->sponsorApprovals()->count());
        $this->assertNull($batch->fresh()->currentApproval()->accounting_forwarded_at);
        $this->get(route('sponsor.approvals.history'))->assertOk()->assertSee('Rejected');
    }

    private function submittedBatchWithDocument(SponsorshipProgram $program, array $origins): GeneratedBatch
    {
        $fixedList = in_array('fixed_list', $origins, true)
            ? FixedList::factory()->create(['sponsorship_program_id' => $program->id, 'status' => FixedListStatus::Finalized])
            : null;
        $batch = GeneratedBatch::factory()->create([
            'sponsorship_program_id' => $program->id, 'fixed_list_id' => $fixedList?->id,
            'total_slots' => count($origins), 'status' => GeneratedBatchStatus::Submitted,
            'submission_version' => 1,
        ]);
        foreach ($origins as $origin) {
            $profile = StudentProfile::factory()->verified()->create();
            $application = Application::factory()->create([
                'sponsorship_program_id' => $program->id, 'student_profile_id' => $profile->id,
                'status' => ApplicationStatus::Verified, 'verified_at' => now(),
                'is_auto_provisioned' => $origin === 'fixed_list',
                'endorsed_by_id' => $origin === 'fixed_list' ? $batch->created_by_fassg_id : null,
                'endorsed_at' => $origin === 'fixed_list' ? now() : null,
                'is_batched' => true, 'batch_id' => $batch->id,
            ]);
            $source = $origin === 'fixed_list' ? FixedListItem::factory()->create([
                'fixed_list_id' => $fixedList->id, 'student_id_number' => $profile->student_id_number,
                'student_name' => $profile->user->name, 'is_fixed_list' => true,
                'is_sle_fhe_verified' => true, 'is_manually_endorsed' => true,
                'endorsed_by_id' => $fixedList->uploaded_by_fassg_id, 'endorsed_at' => now(),
                'status' => FixedListItemStatus::Endorsed,
            ]) : null;
            BatchCandidate::factory()->create([
                'generated_batch_id' => $batch->id, 'application_id' => $application->id,
                'fixed_list_item_id' => $source?->id, 'origin_type' => $origin, 'rank_position' => null,
            ]);
        }
        $batch->update(['submitted_roster' => [
            'program_id' => $program->id, 'sponsor_id' => $program->sponsor_id,
            'submitted_by_id' => $batch->created_by_fassg_id, 'submitted_at' => now()->toIso8601String(),
            'members' => $batch->rosterMembers(),
        ]]);
        $path = "sponsor-approvals/{$batch->id}/signed.pdf";
        Storage::disk('local')->put($path, 'existing signed document');
        $batch->sponsorApprovals()->create([
            'sponsorship_program_id' => $program->id, 'submission_version' => 1,
            'approval_document_path' => $path, 'confirmation_status' => ConfirmationStatus::Pending,
            'uploaded_by_sponsor_id' => $program->sponsor->user_id,
        ]);
        AuditLog::create([
            'user_id' => $program->sponsor->user_id, 'action' => 'sponsor.approval.uploaded',
            'target_module' => 'sponsor_approvals',
        ]);

        return $batch->fresh();
    }

    private function decisionPayload(GeneratedBatch $batch): array
    {
        return ['submission_version' => $batch->fresh()->submission_version,
            'approval_id' => $batch->fresh()->currentApproval()?->id];
    }

    private function confirmAndForward(GeneratedBatch $batch): void
    {
        $this->actingAs($batch->sponsorshipProgram->sponsor->user)
            ->patch(route('sponsor.approvals.confirm', $batch), $this->decisionPayload($batch))
            ->assertSessionHasNoErrors();
        $this->actingAs($batch->createdByFassg)
            ->patch(route('fassg.generated-batches.record-decision', $batch), $this->decisionPayload($batch))
            ->assertSessionHasNoErrors();
        $this->actingAs($batch->createdByFassg)
            ->patch(route('fassg.generated-batches.forward-accounting', $batch), $this->decisionPayload($batch))
            ->assertSessionHasNoErrors();
    }

    private function approvalState(): array
    {
        $state = [];
        foreach (['sponsorship_programs', 'applications', 'student_profiles', 'generated_batches', 'generated_batch_items', 'fixed_lists', 'fixed_list_items', 'sponsor_approvals', 'audit_logs'] as $table) {
            $state[$table] = DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        }

        return $state;
    }

    public function test_accounting_can_export_forwarded_beneficiaries_and_cannot_post(): void
    {
        Storage::fake('local');
        $batch = $this->submittedBatchWithDocument(SponsorshipProgram::factory()->create(), ['ranked_queue', 'fixed_list']);
        $this->confirmAndForward($batch);
        $accounting = User::factory()->create(['role' => UserRole::Accounting]);
        $before = $this->approvalState();
        $this->actingAs($accounting)->get(route('accounting.beneficiaries.index'))->assertOk()
            ->assertSee($batch->items->first()->application->studentProfile->student_id_number);
        $export = $this->get(route('accounting.beneficiaries.export'))->assertOk()->assertHeader('content-disposition');
        foreach ($batch->items as $item) {
            $this->assertStringContainsString($item->application->studentProfile->student_id_number, $export->streamedContent());
        }
        $this->post(route('accounting.beneficiaries.index'))->assertStatus(405);
        foreach (['sponsor.approvals.confirm', 'fassg.generated-batches.record-decision', 'fassg.generated-batches.forward-accounting'] as $route) {
            $this->patch(route($route, $batch), $this->decisionPayload($batch))->assertForbidden();
        }
        $after = $this->approvalState();
        unset($before['audit_logs'], $after['audit_logs']); // Export access auditing is permitted; business data is read-only.
        $this->assertSame($before, $after);
    }

    public function test_sponsor_confirmation_and_fassg_receipt_do_not_independently_grant_accounting_access(): void
    {
        Storage::fake('local');
        $program = SponsorshipProgram::factory()->create(['total_slots' => 1, 'available_slots' => 1]);
        $batch = $this->submittedBatchWithDocument($program, ['fixed_list']);
        $app = $batch->items->first()->application;
        $accounting = User::factory()->create(['role' => UserRole::Accounting]);
        $this->actingAs($program->sponsor->user)->patch(route('sponsor.approvals.confirm', $batch), $this->decisionPayload($batch))
            ->assertSessionHasNoErrors();
        $this->actingAs($accounting)->get(route('accounting.beneficiaries.show', $app))->assertNotFound();
        $this->actingAs($batch->createdByFassg)->patch(route('fassg.generated-batches.forward-accounting', $batch), $this->decisionPayload($batch))
            ->assertSessionHasErrors('approval');
        $this->patch(route('fassg.generated-batches.record-decision', $batch), $this->decisionPayload($batch))
            ->assertSessionHasNoErrors();
        $this->actingAs($accounting)->get(route('accounting.beneficiaries.show', $app))->assertNotFound();
        $approvedBefore = $app->fresh()->getRawOriginal();
        $this->actingAs($batch->createdByFassg)->patch(route('fassg.generated-batches.forward-accounting', $batch), $this->decisionPayload($batch))
            ->assertSessionHasNoErrors(); // Capacity is already zero: forwarding must not consume another slot.
        $this->assertSame($approvedBefore, $app->fresh()->getRawOriginal());
        $this->assertSame(0, $program->fresh()->available_slots);
        $approval = $batch->fresh()->currentApproval();
        $this->assertSame($batch->created_by_fassg_id, $approval->fassg_recorded_by_id);
        $this->assertSame($batch->created_by_fassg_id, $approval->accounting_forwarded_by_id);
        $this->assertNotSame($program->sponsor->user_id, $batch->fresh()->fassg_assigned_by_id);
        $before = $approval->getRawOriginal();
        $this->patch(route('fassg.generated-batches.forward-accounting', $batch), $this->decisionPayload($batch))->assertSessionHasNoErrors();
        $this->assertSame($before, $approval->fresh()->getRawOriginal());
        $this->actingAs($accounting)->get(route('accounting.beneficiaries.show', $app))->assertOk();
    }

    public function test_accounting_can_view_document_only_after_full_handoff(): void
    {
        Storage::fake('local');
        $batch = $this->submittedBatchWithDocument(SponsorshipProgram::factory()->create(), ['fixed_list']);
        $this->confirmAndForward($batch);
        $this->actingAs(User::factory()->create(['role' => UserRole::Accounting]))
            ->get(route('accounting.fixed-lists.document', $batch))->assertOk()
            ->assertHeader('content-disposition', 'inline; filename="signed.pdf"');
        $this->get(route('accounting.beneficiaries.reference', $batch))->assertOk();
    }

    public function test_accounting_dashboard_combines_both_approved_and_forwarded_sources(): void
    {
        Storage::fake('local');
        $batch = $this->submittedBatchWithDocument(SponsorshipProgram::factory()->create(), ['fixed_list', 'ranked_queue']);
        $this->confirmAndForward($batch);
        $this->actingAs(User::factory()->create(['role' => UserRole::Accounting]))
            ->get(route('accounting.dashboard'))->assertOk()->assertViewHas('totalApproved', 2)
            ->assertSee('Sponsor Allocation Summary');
    }

    public function test_legacy_individual_approval_is_retained_but_not_implicitly_certified_for_accounting(): void
    {
        Storage::fake('local');
        $app = Application::factory()->create(['status' => ApplicationStatus::Approved,
            'approved_at' => now()->subMonth(), 'sponsor_approval_path' => 'legacy.pdf']);
        Storage::disk('local')->put('legacy.pdf', 'original legacy evidence');
        $before = $app->getRawOriginal();
        $this->actingAs(User::factory()->create(['role' => UserRole::Accounting]))
            ->get(route('accounting.documents.view', $app))->assertNotFound();
        $this->get(route('accounting.beneficiaries.show', $app))->assertNotFound();
        $this->get(route('accounting.dashboard'))->assertOk()->assertViewHas('totalApproved', 0);
        $this->assertSame($before, $app->fresh()->getRawOriginal());
        $this->assertSame('original legacy evidence', Storage::disk('local')->get('legacy.pdf'));
    }

    public function test_fassg_reports_include_category_and_applicant_totals(): void
    {
        $fassg = User::factory()->create(['role' => UserRole::Fassg]);
        $program = SponsorshipProgram::factory()->create(['category' => ProgramCategory::Individual]);
        Application::factory()->create([
            'sponsorship_program_id' => $program->id,
            'status' => ApplicationStatus::Approved,
            'approved_at' => now(),
        ]);

        $this->actingAs($fassg)
            ->get(route('fassg.reports.index'))
            ->assertOk()
            ->assertSee('Approved/Ongoing applications: 1')
            ->assertSee('Individual');
    }

    #[DataProvider('accountingEvidenceFailures')]
    public function test_every_accounting_access_path_rejects_ineligible_beneficiaries(string $failure): void
    {
        Storage::fake('local');
        $program = SponsorshipProgram::factory()->create();
        $batch = $this->submittedBatchWithDocument($program, ['fixed_list']);
        $this->confirmAndForward($batch);
        $candidate = $batch->items->first();
        $app = $candidate->application;
        $profile = $app->studentProfile;
        $approval = $batch->fresh()->currentApproval();
        match ($failure) {
            'pending' => $app->update(['status' => ApplicationStatus::Pending]),
            'verified' => $app->update(['status' => ApplicationStatus::Verified]),
            'missing approval timestamp' => $app->update(['approved_at' => null]),
            'missing eligibility timestamp' => $app->update(['verified_at' => null]),
            'no SLE officer' => $profile->sleFheVerification->update(['verified_by' => null]),
            'no SLE time' => $profile->sleFheVerification->update(['verified_at' => null]),
            'no sponsor confirmation' => $approval->update(['confirmation_status' => ConfirmationStatus::Pending]),
            'no sponsor actor' => $approval->update(['decided_by_id' => null]),
            'no document' => Storage::disk('local')->delete($approval->approval_document_path),
            'no receipt' => $approval->update(['fassg_recorded_at' => null]),
            'no forwarding' => $approval->update(['accounting_forwarded_at' => null]),
            'no forwarding officer' => $approval->update(['accounting_forwarded_by_id' => null]),
            'wrong submission' => $approval->update(['submission_version' => 2]),
            'wrong program' => $app->update(['sponsorship_program_id' => SponsorshipProgram::factory()->create()->id]),
            'altered roster' => $candidate->update(['origin_type' => 'ranked_queue']),
            'unverified fixed source' => $candidate->fixedListItem->update(['is_sle_fhe_verified' => false]),
            'unattested fixed qualification' => $app->update(['endorsed_by_id' => null]),
            'wrong active owner' => $profile->update(['active_sponsorship_id' => Application::factory()->create(['status' => ApplicationStatus::Approved])->id]),
            'second active award' => Application::factory()->create(['student_profile_id' => $profile->id, 'status' => ApplicationStatus::Ongoing]),
        };
        $accounting = User::factory()->create(['role' => UserRole::Accounting]);
        foreach (['accounting.beneficiaries.index', 'accounting.programs.index', 'accounting.applications.index',
            'accounting.fixed-lists.index', 'accounting.reports.index', 'accounting.audit-logs.index'] as $route) {
            $this->actingAs($accounting)->get(route($route))->assertOk()->assertViewHas('totalApproved', 0);
        }
        $this->get(route('accounting.dashboard'))->assertOk()->assertViewHas('totalApproved', 0);
        foreach (['accounting.beneficiaries.export', 'accounting.export'] as $route) {
            $response = $this->get(route($route))->assertOk();
            $this->assertStringNotContainsString($profile->student_id_number, $response->streamedContent());
        }
        foreach (['accounting.beneficiaries.show', 'accounting.applications.document', 'accounting.documents.view'] as $route) {
            $this->get(route($route, $app))->assertNotFound();
        }
        foreach (['accounting.beneficiaries.reference', 'accounting.fixed-lists.document'] as $route) {
            $this->get(route($route, $batch))->assertNotFound();
        }
    }

    public static function accountingEvidenceFailures(): array
    {
        return array_map(fn ($case) => [$case], ['pending', 'verified', 'missing approval timestamp',
            'missing eligibility timestamp', 'no SLE officer', 'no SLE time', 'no sponsor confirmation',
            'no sponsor actor', 'no document', 'no receipt', 'no forwarding', 'no forwarding officer',
            'wrong submission', 'wrong program', 'altered roster', 'unverified fixed source',
            'unattested fixed qualification', 'wrong active owner', 'second active award']);
    }

    public function test_shared_confirmation_document_cannot_leak_an_ineligible_member(): void
    {
        Storage::fake('local');
        $batch = $this->submittedBatchWithDocument(SponsorshipProgram::factory()->create(), ['fixed_list', 'ranked_queue']);
        $this->confirmAndForward($batch);
        $eligible = $batch->items->first()->application;
        $ineligible = $batch->items->last()->application;
        $ineligible->update(['status' => ApplicationStatus::Verified]);
        $this->actingAs(User::factory()->create(['role' => UserRole::Accounting]))
            ->get(route('accounting.beneficiaries.reference', $batch))->assertOk()
            ->assertSee($eligible->studentProfile->student_id_number)
            ->assertDontSee($ineligible->studentProfile->student_id_number);
        $this->get(route('accounting.applications.document', $eligible))->assertNotFound();
        $this->get(route('accounting.fixed-lists.document', $batch))->assertNotFound();
    }

    public function test_invalid_member_prevents_the_entire_sponsor_approval_without_silent_skipping(): void
    {
        Storage::fake('local');
        $batch = $this->submittedBatchWithDocument(SponsorshipProgram::factory()->create(), ['fixed_list', 'ranked_queue']);
        $batch->items->last()->application->studentProfile->sleFheVerification->update(['verified_by' => null]);
        $before = $this->approvalState();
        $this->actingAs($batch->sponsorshipProgram->sponsor->user)
            ->patch(route('sponsor.approvals.confirm', $batch), $this->decisionPayload($batch))
            ->assertSessionHasErrors('batch');
        $this->assertSame($before, $this->approvalState());
    }

    public function test_submitted_roster_cannot_be_deleted_appended_or_decided_from_stale_page(): void
    {
        Storage::fake('local');
        $program = SponsorshipProgram::factory()->create();
        $batch = $this->submittedBatchWithDocument($program, ['ranked_queue']);
        $extra = Application::factory()->create(['sponsorship_program_id' => $program->id,
            'student_profile_id' => StudentProfile::factory()->verified(), 'status' => ApplicationStatus::Verified, 'verified_at' => now()]);
        $before = $this->approvalState();
        $this->actingAs($batch->createdByFassg)->delete(route('fassg.generated-batches.destroy', $batch))->assertForbidden();
        $this->post(route('fassg.applications.create-batch'), [
            'existing_fixed_list_id' => $batch->id, 'sponsorship_program_id' => $program->id,
            'selected_applications' => [$extra->id],
        ])->assertSessionHasErrors('existing_fixed_list_id');
        $this->actingAs($program->sponsor->user)->patch(route('sponsor.approvals.confirm', $batch), [
            'submission_version' => 2, 'approval_id' => $batch->currentApproval()->id,
        ])->assertSessionHasErrors('batch');
        $this->assertSame($before, $this->approvalState());
    }

    public function test_rejected_resubmission_preserves_roster_and_evidence_but_requires_new_confirmation(): void
    {
        Storage::fake('local');
        $batch = $this->submittedBatchWithDocument(SponsorshipProgram::factory()->create(), ['ranked_queue']);
        $originalRoster = $batch->submitted_roster;
        $this->actingAs($batch->sponsorshipProgram->sponsor->user)
            ->patch(route('sponsor.approvals.reject', $batch), ['submission_version' => 1])->assertSessionHasNoErrors();
        $rejection = $batch->fresh()->currentApproval();
        $this->actingAs($batch->createdByFassg)->patch(route('fassg.generated-batches.submit', $batch))->assertSessionHasErrors('batch');
        $this->patch(route('fassg.generated-batches.record-decision', $batch), $this->decisionPayload($batch))->assertSessionHasNoErrors();
        $rejectionBefore = $rejection->fresh()->getRawOriginal();
        $this->patch(route('fassg.generated-batches.submit', $batch))->assertSessionHasNoErrors();
        $this->assertSame(2, $batch->fresh()->submission_version);
        $this->assertSame($originalRoster, $batch->fresh()->submitted_roster);
        $this->assertSame($rejectionBefore, $rejection->fresh()->getRawOriginal());
        $this->assertNull($batch->fresh()->currentApproval());
        $this->actingAs($batch->sponsorshipProgram->sponsor->user)
            ->patch(route('sponsor.approvals.confirm', $batch), ['submission_version' => 2, 'approval_id' => $rejection->id])
            ->assertSessionHasErrors('approval');
    }


    public function test_competing_program_approval_cannot_replace_an_existing_active_award(): void
    {
        Storage::fake('local');
        $first = $this->submittedBatchWithDocument(SponsorshipProgram::factory()->create(), ['ranked_queue']);
        $second = $this->submittedBatchWithDocument(SponsorshipProgram::factory()->create(), ['ranked_queue']);
        $profile = $first->items->first()->application->studentProfile;
        $second->items->first()->application->update(['student_profile_id' => $profile->id]);
        $roster = $second->submitted_roster;
        $roster['members'] = $second->rosterMembers();
        $second->update(['submitted_roster' => $roster]); // Fixture: same student was submitted to two different programs.
        $this->actingAs($first->sponsorshipProgram->sponsor->user)
            ->patch(route('sponsor.approvals.confirm', $first), $this->decisionPayload($first))->assertSessionHasNoErrors();
        $before = $this->approvalState();
        $this->actingAs($second->sponsorshipProgram->sponsor->user)
            ->patch(route('sponsor.approvals.confirm', $second), $this->decisionPayload($second))->assertSessionHasErrors('batch');
        $this->assertSame($before, $this->approvalState());
        $this->assertSame($first->items->first()->application_id, $profile->fresh()->active_sponsorship_id);
        $this->actingAs($first->sponsorshipProgram->sponsor->user)
            ->patch(route('sponsor.approvals.confirm', $first), $this->decisionPayload($first))->assertForbidden();
        $this->assertSame($before, $this->approvalState());
    }

    public function test_fixed_list_with_no_optional_qualification_requirements_can_reach_accounting_without_invented_values(): void
    {
        Storage::fake('local');
        $program = SponsorshipProgram::factory()->create(['min_gpa' => null]);
        $batch = $this->submittedBatchWithDocument($program, ['fixed_list']);
        $app = $batch->items->first()->application;
        $app->update(['gpa_submitted' => null, 'address_submitted' => null, 'is_rural_submitted' => null, 'submitted_at' => null]);
        $this->confirmAndForward($batch);
        $this->actingAs(User::factory()->create(['role' => UserRole::Accounting]))
            ->get(route('accounting.beneficiaries.show', $app))->assertOk()->assertSee('Not recorded');
        $this->assertNull($app->fresh()->gpa_submitted);
        $this->assertNull($app->fresh()->address_submitted);
        $this->assertNull($app->fresh()->is_rural_submitted);
        $this->assertNull($app->fresh()->submitted_at);
    }

    public function test_accounting_cannot_access_generic_application_document_route(): void
    {
        Storage::fake('local');
        $batch = $this->submittedBatchWithDocument(SponsorshipProgram::factory()->create(), ['ranked_queue']);
        $this->confirmAndForward($batch);
        $doc = $batch->items->first()->application->documents()->create([
            'document_type' => \App\Enums\DocumentType::CertificateOfGrades,
            'file_path' => 'private-grade.pdf', 'file_name' => 'grade.pdf',
        ]);
        Storage::disk('local')->put('private-grade.pdf', 'private supporting evidence');
        $this->actingAs(User::factory()->create(['role' => UserRole::Accounting]))
            ->get(route('documents.show', $doc))->assertForbidden();
    }

}
