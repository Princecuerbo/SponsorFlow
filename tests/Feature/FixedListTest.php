<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\FixedListItemStatus;
use App\Enums\FixedListStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\FixedList;
use App\Models\FixedListItem;
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

    public function test_fassg_can_toggle_endorsement_on_fixed_list_item(): void
    {
        $fassg = $this->actingAsFassg();
        $list = FixedList::factory()->create(['status' => FixedListStatus::Draft]);
        $item = FixedListItem::factory()->create([
            'fixed_list_id' => $list->id,
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

        $this->patch(route('fassg.fixed-lists.items.endorse', [$list, $item]))
            ->assertRedirect();

        $item->refresh();
        $this->assertFalse($item->is_manually_endorsed);
        $this->assertNull($item->endorsed_by_id);
        $this->assertNull($item->endorsed_at);
        $this->assertTrue($item->status === FixedListItemStatus::Pending);
    }

    public function test_fassg_can_verify_fixed_list_item_and_record_verifier(): void
    {
        $fassg = $this->actingAsFassg();
        $profile = StudentProfile::factory()->verified()->create([
            'student_id_number' => '2024-00111',
        ]);
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

        $this->assertDatabaseHas('sle_fhe_verifications', [
            'student_profile_id' => $profile->id,
            'verified_by' => $fassg->id,
        ]);
    }

    public function test_fassg_can_finalize_fixed_list_without_sponsor_workflow(): void
    {
        Notification::fake();

        $fassg = $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create();
        $list = FixedList::factory()->create([
            'sponsorship_program_id' => $program->id,
            'status' => FixedListStatus::Draft,
            'fassg_assigned_at' => null,
            'fassg_assigned_by_id' => null,
        ]);
        FixedListItem::factory()->create([
            'fixed_list_id' => $list->id,
            'is_sle_fhe_verified' => true,
            'is_manually_endorsed' => true,
            'status' => FixedListItemStatus::Endorsed,
        ]);

        $this->patch(route('fassg.fixed-lists.finalize', $list))
            ->assertRedirect()
            ->assertSessionHas('status', 'Fixed List has been finalized and is ready for batch generation.');

        $list->refresh();
        $this->assertTrue($list->status === FixedListStatus::Finalized);
        $this->assertNotNull($list->fassg_assigned_at);
        $this->assertSame($fassg->id, $list->fassg_assigned_by_id);

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

    public function test_batch_creation_locks_candidates_from_selected_finalized_list(): void
    {
        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create();

        $lockedStudent = StudentProfile::factory()->verified()->create(['student_id_number' => '2024-00001']);
        $lockedStudent2 = StudentProfile::factory()->verified()->create(['student_id_number' => '2024-00002']);
        $generalStudent = StudentProfile::factory()->verified()->create(['student_id_number' => '2024-00003']);

        $lockedApp = $this->makePendingApplication($program, $lockedStudent, 2.50);
        $lockedApp2 = $this->makePendingApplication($program, $lockedStudent2, 2.20);
        $generalApp = $this->makePendingApplication($program, $generalStudent, 1.50);

        $finalized = FixedList::factory()->create([
            'sponsorship_program_id' => $program->id,
            'status' => FixedListStatus::Finalized,
        ]);
        FixedListItem::factory()->create([
            'fixed_list_id' => $finalized->id,
            'student_id_number' => '2024-00001',
            'is_manually_endorsed' => true,
        ]);
        FixedListItem::factory()->create([
            'fixed_list_id' => $finalized->id,
            'student_id_number' => '2024-00002',
            'is_manually_endorsed' => true,
        ]);

        $this->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'Locked Batch',
            'sponsorship_program_id' => $program->id,
            'selected_applications' => [$lockedApp->id, $lockedApp2->id, $generalApp->id],
            'locked_fixed_list_id' => $finalized->id,
        ])->assertRedirect()
            ->assertSessionHas('status');

        $batch = FixedList::query()->where('status', FixedListStatus::Saved)->first();
        $this->assertNotNull($batch);

        $items = $batch->items()->orderBy('rank_position')->get();
        $this->assertSame([1, 2, 3], $items->pluck('rank_position')->all());
        $this->assertSame(['2024-00002', '2024-00001', '2024-00003'], $items->pluck('student_id_number')->all());
        $this->assertSame([true, true, false], $items->pluck('is_fixed_list')->all());
        $this->assertSame(['fixed_list', 'fixed_list', 'ranked_queue'], $items->pluck('origin_type')->all());
        $this->assertSame([$lockedApp2->id, $lockedApp->id, $generalApp->id], $items->pluck('application_id')->all());
    }

    public function test_batch_creation_auto_creates_verified_application_for_locked_candidate(): void
    {
        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create();

        $lockedStudent = StudentProfile::factory()->verified()->create(['student_id_number' => '2024-00011']);
        $generalStudent = StudentProfile::factory()->verified()->create(['student_id_number' => '2024-00012']);
        $generalApp = $this->makePendingApplication($program, $generalStudent, 1.75);

        $finalized = FixedList::factory()->create([
            'sponsorship_program_id' => $program->id,
            'status' => FixedListStatus::Finalized,
        ]);
        FixedListItem::factory()->create([
            'fixed_list_id' => $finalized->id,
            'student_id_number' => $lockedStudent->student_id_number,
            'is_sle_fhe_verified' => true,
            'is_manually_endorsed' => true,
        ]);

        $this->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'Auto Provisioned Batch',
            'sponsorship_program_id' => $program->id,
            'selected_applications' => [$generalApp->id],
            'locked_fixed_list_id' => $finalized->id,
        ])->assertRedirect()
            ->assertSessionHas('status')
            ->assertSessionDoesntHaveErrors();

        $autoApp = Application::query()
            ->where('sponsorship_program_id', $program->id)
            ->where('student_profile_id', $lockedStudent->id)
            ->first();

        $this->assertNotNull($autoApp);
        $this->assertTrue($autoApp->status === ApplicationStatus::Verified);
        $this->assertNotNull($autoApp->verified_at);
        $this->assertTrue((bool) $autoApp->is_manually_endorsed);

        $batch = FixedList::query()->where('status', FixedListStatus::Saved)->first();
        $this->assertNotNull($batch);

        $items = $batch->items()->orderBy('rank_position')->get();
        $this->assertSame([1, 2], $items->pluck('rank_position')->all());
        $this->assertSame([$lockedStudent->student_id_number, $generalStudent->student_id_number], $items->pluck('student_id_number')->all());
        $this->assertSame([true, false], $items->pluck('is_fixed_list')->all());
        $this->assertSame(['fixed_list', 'ranked_queue'], $items->pluck('origin_type')->all());
        $this->assertSame([$autoApp->id, $generalApp->id], $items->pluck('application_id')->all());
        $this->assertTrue((bool) $autoApp->fresh()->is_batched);
    }

    public function test_batch_creation_without_finalized_list_auto_ranks_all_applicants(): void
    {
        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create();

        $lockedStudent = StudentProfile::factory()->verified()->create(['student_id_number' => '2024-00001']);
        $generalStudent = StudentProfile::factory()->verified()->create(['student_id_number' => '2024-00002']);

        $finalized = FixedList::factory()->create([
            'sponsorship_program_id' => $program->id,
            'status' => FixedListStatus::Finalized,
        ]);
        FixedListItem::factory()->create([
            'fixed_list_id' => $finalized->id,
            'student_id_number' => '2024-00001',
        ]);

        $app = $this->makePendingApplication($program, $lockedStudent, 2.10);
        $app2 = $this->makePendingApplication($program, $generalStudent, 1.60);

        $this->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'General Batch',
            'sponsorship_program_id' => $program->id,
            'selected_applications' => [$app->id, $app2->id],
            'locked_fixed_list_id' => '',
        ])->assertRedirect()
            ->assertSessionHas('status');

        $batch = FixedList::query()->where('status', FixedListStatus::Saved)->first();
        $this->assertNotNull($batch);

        $items = $batch->items()->orderBy('rank_position')->get();
        $this->assertSame([1, 2], $items->pluck('rank_position')->all());
        $this->assertSame(['2024-00002', '2024-00001'], $items->pluck('student_id_number')->all());
        $this->assertSame([false, false], $items->pluck('is_fixed_list')->all());
        $this->assertSame(['ranked_queue', 'ranked_queue'], $items->pluck('origin_type')->all());
    }

    public function test_batch_creation_rejects_non_finalized_lock_source(): void
    {
        $this->actingAsFassg();
        $program = SponsorshipProgram::factory()->create();
        $app = $this->makePendingApplication(
            $program,
            StudentProfile::factory()->verified()->create(),
            1.75,
        );

        $submitted = FixedList::factory()->create([
            'sponsorship_program_id' => $program->id,
            'status' => FixedListStatus::Submitted,
        ]);

        $this->post(route('fassg.applications.create-batch'), [
            'batch_name' => 'Rejected Lock',
            'sponsorship_program_id' => $program->id,
            'selected_applications' => [$app->id],
            'locked_fixed_list_id' => $submitted->id,
        ])->assertRedirect()
            ->assertSessionHasErrors('locked_fixed_list_id');

        $this->assertDatabaseMissing('fixed_lists', ['batch_name' => 'Rejected Lock']);
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

        $princeApp = $this->makePendingApplication($program, $princeProfile, 1.80);

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
            ->assertSee('★ Endorsed by Sponsor')
            ->assertDontSee('Maria Santos');
    }

    private function makePendingApplication(SponsorshipProgram $program, StudentProfile $profile, float $gpa): Application
    {
        return Application::factory()->create([
            'sponsorship_program_id' => $program->id,
            'student_profile_id' => $profile->id,
            'gpa_submitted' => $gpa,
            'status' => ApplicationStatus::Pending,
        ]);
    }
}
