<?php

namespace Tests\Feature;

use App\Enums\FixedListItemStatus;
use App\Enums\FixedListStatus;
use App\Enums\UserRole;
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
}
