<?php

namespace Tests\Unit;

use App\Enums\FixedListItemStatus;
use App\Models\FixedListItem;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

/** No Laravel bootstrap or database: any attempted connection fails this test. */
class FixedListEndorsementTest extends TestCase
{
    private ?ConnectionResolverInterface $previousResolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousResolver = Model::getConnectionResolver();
        $resolver = $this->createMock(ConnectionResolverInterface::class);
        $resolver->expects($this->never())->method('connection');
        Model::setConnectionResolver($resolver);
    }

    protected function tearDown(): void
    {
        if ($this->previousResolver !== null) {
            Model::setConnectionResolver($this->previousResolver);
        } else {
            Model::unsetConnectionResolver();
        }
        parent::tearDown();
    }

    private function program(array $attributes = []): SponsorshipProgram
    {
        $program = new SponsorshipProgram;
        $program->setDateFormat('Y-m-d H:i:s');
        $program->setRawAttributes(array_replace(['id' => 7, 'status' => 'Open',
            'total_slots' => 2, 'approved_count' => 0, 'min_gpa' => 1.5], $attributes));
        $program->setRelation('academicPrograms', new Collection);

        return $program;
    }

    private function candidate(bool $verified = true, bool $active = false): FixedListItem
    {
        $profile = $this->getMockBuilder(StudentProfile::class)
            ->onlyMethods(['hasDocumentedSleFheVerification', 'hasConflictingActiveSponsorship'])->getMock();
        $profile->method('hasDocumentedSleFheVerification')->willReturn($verified);
        $profile->method('hasConflictingActiveSponsorship')->willReturn($active);
        $profile->setRawAttributes(['id' => 8, 'course' => 'BSIT', 'campus' => 'Main', 'year_level' => 3]);
        $profile->setRelation('sleFheRequest', null)->setRelation('sleFheVerification', null);
        $item = new FixedListItem(['is_sle_fhe_verified' => true, 'status' => FixedListItemStatus::Verified]);
        $item->setDateFormat('Y-m-d H:i:s');
        $item->setRelation('studentProfile', $profile);

        return $item;
    }

    public function test_preparation_checks_do_not_invent_qualification_evidence_or_mutate_records(): void
    {
        $program = $this->program();
        $item = $this->candidate();
        $before = $item->getAttributes();
        $this->assertSame([], $item->endorsementErrors($program));
        $this->assertSame($before, $item->getAttributes());
        $this->assertNull($item->getAttribute('gpa_submitted'));
        $this->assertNull($item->endorsed_at);
        $this->assertSame(FixedListItemStatus::Verified, $item->status);
    }

    public function test_a_list_flag_cannot_replace_documented_sle_fhe_verification(): void
    {
        $this->assertNotEmpty($this->candidate(false)->endorsementErrors($this->program()));
        $item = $this->candidate();
        $item->is_sle_fhe_verified = false;
        $this->assertNotEmpty($item->endorsementErrors($this->program()));
        $item->setRelation('studentProfile', null);
        $this->assertSame(['A matching registered student profile is required.'], $item->endorsementErrors($this->program()));
    }

    public function test_known_criteria_conflicts_and_expired_programs_are_not_overridden(): void
    {
        foreach ([['target_course' => 'BS Nursing'], ['eligible_campuses' => '["Other"]'],
            ['eligible_year_levels' => '[1]'], ['status' => 'Closed'], ['end_date' => '2000-01-01']] as $criteria) {
            $item = $this->candidate();
            $before = $item->getAttributes();
            $this->assertNotEmpty($item->endorsementErrors($this->program($criteria)));
            $this->assertSame($before, $item->getAttributes());
        }
        $item = $this->candidate();
        $item->studentProfile->year_level = null;
        $this->assertNotEmpty($item->endorsementErrors($this->program(['eligible_year_levels' => '[3]'])));
    }

    public function test_active_or_explicitly_ineligible_students_cannot_be_endorsed(): void
    {
        $this->assertNotEmpty($this->candidate(true, true)->endorsementErrors($this->program()));
        $item = $this->candidate();
        $item->status = FixedListItemStatus::Ineligible;
        $this->assertNotEmpty($item->endorsementErrors($this->program()));
    }

    public function test_finalization_requires_actual_endorsement_attribution(): void
    {
        $item = $this->candidate();
        $item->is_manually_endorsed = true;
        $item->status = FixedListItemStatus::Endorsed;
        $this->assertFalse($item->hasRecordedEndorsement());
        $item->endorsed_by_id = 2;
        $this->assertFalse($item->hasRecordedEndorsement());
        $item->endorsed_at = '2026-10-17 12:00:00';
        $this->assertTrue($item->hasRecordedEndorsement());
    }
}
