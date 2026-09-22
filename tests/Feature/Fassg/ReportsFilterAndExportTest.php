<?php

namespace Tests\Feature\Fassg;

use App\Enums\ApplicationStatus;
use App\Enums\ConfirmationStatus;
use App\Enums\FixedListStatus;
use App\Enums\ProgramCategory;
use App\Enums\ProgramStatus;
use App\Enums\UserRole;
use App\Models\AcademicProgram;
use App\Models\Application;
use App\Models\FixedList;
use App\Models\FixedListItem;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsFilterAndExportTest extends TestCase
{
    use RefreshDatabase;

    private User $fassg;

    private SponsorshipProgram $filterTestProgram;

    private SponsorshipProgram $otherProgram;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fassg = User::factory()->create(['role' => UserRole::Fassg]);

        // Program 1: Group Category (ID: 24 to match canonical DB)
        $this->filterTestProgram = SponsorshipProgram::factory()->create([
            'id' => 24,
            'program_name' => 'FilterTest Grant',
            'category' => ProgramCategory::Group,
            'total_slots' => 5,
            'available_slots' => 4,
            'status' => ProgramStatus::Open,
        ]);

        // Program 2: Individual Category
        $this->otherProgram = SponsorshipProgram::factory()->create([
            'program_name' => 'Individual Grant',
            'category' => ProgramCategory::Individual,
            'total_slots' => 10,
            'available_slots' => 10,
            'status' => ProgramStatus::Open,
        ]);

        // Student 1: Unassigned Campus (like Maria Santos in canonical DB), Urban, Female, BSIT
        $bsitProgram = AcademicProgram::factory()->create(['name' => 'Bachelor of Science in Information Technology']);
        $polSciProgram = AcademicProgram::factory()->create(['name' => 'Bachelor of Arts in Political Science']);

        $profile1 = StudentProfile::factory()->create([
            'academic_program_id' => $bsitProgram->program_id,
            'campus' => null,
            'gender' => 'Female',
            'course' => 'Bachelor of Science in Information Technology',
            'student_id_number' => '2024-00001',
        ]);

        // Student 2: Tarragona Campus, Rural, Male, BSIT
        $profile2 = StudentProfile::factory()->create([
            'academic_program_id' => $bsitProgram->program_id,
            'campus' => 'Tarragona Campus',
            'gender' => 'Male',
            'course' => 'Bachelor of Science in Information Technology',
            'student_id_number' => '2024-00002',
        ]);

        // Student 3: Mati Campus, Urban, Male, PolSci
        $profile3 = StudentProfile::factory()->create([
            'academic_program_id' => $polSciProgram->program_id,
            'campus' => 'Main Campus (City of Mati)',
            'gender' => 'Male',
            'course' => 'Bachelor of Arts in Political Science',
            'student_id_number' => '2024-00003',
        ]);

        // App 1: Profile 1 -> FilterTest Grant (Approved, in First Semester 2026-09-15)
        $app1 = Application::factory()->create([
            'student_profile_id' => $profile1->id,
            'sponsorship_program_id' => $this->filterTestProgram->id,
            'status' => ApplicationStatus::Approved,
            'is_rural_submitted' => false,
            'submitted_at' => '2026-09-15 10:00:00',
            'approved_at' => '2026-09-16 10:00:00',
        ]);

        // App 2: Profile 2 -> FilterTest Grant (Verified, First Semester)
        Application::factory()->create([
            'student_profile_id' => $profile2->id,
            'sponsorship_program_id' => $this->filterTestProgram->id,
            'status' => ApplicationStatus::Verified,
            'is_rural_submitted' => true,
            'submitted_at' => '2026-09-16 11:00:00',
        ]);

        // App 3: Profile 3 -> FilterTest Grant (Rejected, First Semester)
        $app3 = Application::factory()->create([
            'student_profile_id' => $profile3->id,
            'sponsorship_program_id' => $this->filterTestProgram->id,
            'status' => ApplicationStatus::Rejected,
            'is_rural_submitted' => false,
            'submitted_at' => '2026-09-16 12:00:00',
        ]);

        // App 4: Profile 1 -> Individual Grant (Pending, First Semester)
        Application::factory()->create([
            'student_profile_id' => $profile1->id,
            'sponsorship_program_id' => $this->otherProgram->id,
            'status' => ApplicationStatus::Pending,
            'is_rural_submitted' => false,
            'submitted_at' => '2026-09-16 14:00:00',
        ]);

        // Fixed List for FilterTest Grant, confirmed
        $fixedList = FixedList::factory()->create([
            'sponsorship_program_id' => $this->filterTestProgram->id,
            'status' => FixedListStatus::Approved,
            'fassg_assigned_at' => '2026-09-16 15:00:00',
        ]);

        $fixedList->sponsorApprovals()->create([
            'sponsorship_program_id' => $this->filterTestProgram->id,
            'approval_document_path' => 'sponsor-approvals/test.pdf',
            'confirmation_status' => ConfirmationStatus::Confirmed,
            'uploaded_by_sponsor_id' => $this->filterTestProgram->sponsor->user_id,
        ]);

        // Item 1: Confirmed beneficiary (Maria Santos, linked to app1)
        FixedListItem::factory()->create([
            'fixed_list_id' => $fixedList->id,
            'application_id' => $app1->id,
            'student_id_number' => $profile1->student_id_number,
            'student_name' => $profile1->user->name,
            'campus' => $profile1->campus,
        ]);

        // Item 2: Rejected item (linked to app3) - should be excluded
        FixedListItem::factory()->create([
            'fixed_list_id' => $fixedList->id,
            'application_id' => $app3->id,
            'student_id_number' => $profile3->student_id_number,
            'student_name' => $profile3->user->name,
            'campus' => $profile3->campus,
        ]);
    }

    public function test_default_global_report_view_metrics(): void
    {
        $response = $this->actingAs($this->fassg)
            ->get(route('fassg.reports.index'))
            ->assertOk();

        $report = $response->viewData('report');
        $this->assertEquals(4, $report['total_applicants']);
        $this->assertEquals(1, $report['confirmed_beneficiaries']);
        $this->assertEquals(1, $report['slots_filled']);
        $this->assertEquals(15, $report['slots_total']); // 5 + 10 = 15
        $this->assertEquals(6.7, $report['slot_utilization_pct']); // 1 / 15 * 100

        // Slot Utilization table
        $slotUtilization = $response->viewData('slotUtilization');
        $this->assertCount(2, $slotUtilization);
        $filterTestProg = $slotUtilization->firstWhere('id', $this->filterTestProgram->id);
        $this->assertEquals(1, $filterTestProg->approved_count);
        $this->assertEquals(4, $filterTestProg->available_slots);

        $otherProg = $slotUtilization->firstWhere('id', $this->otherProgram->id);
        $this->assertEquals(0, $otherProg->approved_count);
        $this->assertEquals(10, $otherProg->available_slots);

        // Category breakdown
        $categoryBreakdown = collect($response->viewData('categoryBreakdown'));
        $groupCategory = $categoryBreakdown->firstWhere('category', ProgramCategory::Group->value);
        $this->assertEquals(3, $groupCategory['applicants']); // apps 1, 2, 3

        $indivCategory = $categoryBreakdown->firstWhere('category', ProgramCategory::Individual->value);
        $this->assertEquals(1, $indivCategory['applicants']); // app 4
    }

    public function test_sponsorship_program_filter_isolates_data(): void
    {
        $response = $this->actingAs($this->fassg)
            ->get(route('fassg.reports.index', ['sponsorship_program_id' => $this->filterTestProgram->id]))
            ->assertOk();

        $report = $response->viewData('report');
        $this->assertEquals(3, $report['total_applicants']);
        $this->assertEquals(1, $report['confirmed_beneficiaries']);
        $this->assertEquals(1, $report['slots_filled']);
        $this->assertEquals(5, $report['slots_total']);
        $this->assertEquals(20.0, $report['slot_utilization_pct']);

        $slotUtilization = $response->viewData('slotUtilization');
        $this->assertCount(1, $slotUtilization);
        $this->assertEquals($this->filterTestProgram->id, $slotUtilization->first()->id);
    }

    public function test_academic_year_and_semester_filter(): void
    {
        // First Semester 2026-2027 should match all 4
        $responseMatch = $this->actingAs($this->fassg)
            ->get(route('fassg.reports.index', [
                'academic_year' => '2026-2027',
                'semester' => 'First',
            ]))
            ->assertOk();

        $this->assertEquals(4, $responseMatch->viewData('report')['total_applicants']);
        $this->assertEquals(1, $responseMatch->viewData('report')['confirmed_beneficiaries']);

        // Second Semester 2026-2027 should have 0
        $responseEmpty = $this->actingAs($this->fassg)
            ->get(route('fassg.reports.index', [
                'academic_year' => '2026-2027',
                'semester' => 'Second',
            ]))
            ->assertOk();

        $this->assertEquals(0, $responseEmpty->viewData('report')['total_applicants']);
        $this->assertEquals(0, $responseEmpty->viewData('report')['confirmed_beneficiaries']);
    }

    public function test_campus_filter_dynamically_updates_metrics(): void
    {
        // Filter by Tarragona Campus (Profile 2 only, which is Verified, not confirmed)
        $response = $this->actingAs($this->fassg)
            ->get(route('fassg.reports.index', [
                'campus' => 'Tarragona Campus',
            ]))
            ->assertOk();

        $report = $response->viewData('report');
        $this->assertEquals(1, $report['total_applicants']);
        $this->assertEquals(0, $report['confirmed_beneficiaries']);

        $demographics = $response->viewData('demographics');
        $this->assertEquals(['Tarragona Campus' => 1], $demographics['by_campus']);
    }

    public function test_export_pdf_and_csv(): void
    {
        // PDF Export with filter
        $pdfResponse = $this->actingAs($this->fassg)
            ->get(route('fassg.reports.export-pdf', ['sponsorship_program_id' => $this->filterTestProgram->id]))
            ->assertOk();

        $this->assertEquals('application/pdf', $pdfResponse->headers->get('content-type'));
        $this->assertStringContainsString('attachment;', (string) $pdfResponse->headers->get('content-disposition'));

        // CSV Export with filter
        $csvResponse = $this->actingAs($this->fassg)
            ->get(route('fassg.reports.export-csv', ['sponsorship_program_id' => $this->filterTestProgram->id]))
            ->assertOk();

        $this->assertEquals('text/csv; charset=UTF-8', $csvResponse->headers->get('content-type'));
        $content = $csvResponse->getContent();
        $this->assertStringContainsString('FilterTest Grant', $content);
        $this->assertStringContainsString('20%,1,4,5', $content);
    }

    public function test_single_program_filter_pipeline(): void
    {
        // 1. Single Program Filter: GET /fassg/reports?sponsorship_program_id=24
        $response = $this->actingAs($this->fassg)
            ->get('/fassg/reports?sponsorship_program_id=24')
            ->assertOk();

        $report = $response->viewData('report');
        $this->assertEquals(20.0, $report['utilization_rate']);
        $this->assertEquals(3, $report['total_applicants']);
        $this->assertEquals(1, $report['confirmed_beneficiaries']);
    }

    public function test_cross_filter_program_and_campus_pipeline(): void
    {
        // 2. Cross-Filter (Program + Campus): GET /fassg/reports?sponsorship_program_id=24&campus=Main+Campus+%28City+of+Mati%29
        $response = $this->actingAs($this->fassg)
            ->get('/fassg/reports?sponsorship_program_id=24&campus=Main+Campus+%28City+of+Mati%29')
            ->assertOk();

        $report = $response->viewData('report');
        $this->assertEquals(0.0, $report['utilization_rate']);
        $this->assertEquals(1, $report['total_applicants']);
        $this->assertEquals(0, $report['confirmed_beneficiaries']);
        $this->assertEquals(0.0, $report['rural_rate']);
    }

    public function test_cross_filter_program_academic_year_and_semester_pipeline(): void
    {
        // 3. Cross-Filter (Program + Academic Year + Semester): GET /fassg/reports?sponsorship_program_id=24&academic_year=2026-2027&semester=First+Semester
        $response = $this->actingAs($this->fassg)
            ->get('/fassg/reports?sponsorship_program_id=24&academic_year=2026-2027&semester=First+Semester')
            ->assertOk();

        $report = $response->viewData('report');
        $this->assertEquals(3, $report['total_applicants']);
        $this->assertEquals(1, $report['confirmed_beneficiaries']);
    }

    public function test_empty_multi_filter_out_of_range_pipeline(): void
    {
        // 4. Empty Multi-Filter (Out of Range): GET /fassg/reports?sponsorship_program_id=24&campus=NonExistentCampus
        $response = $this->actingAs($this->fassg)
            ->get('/fassg/reports?sponsorship_program_id=24&campus=NonExistentCampus')
            ->assertOk();

        $report = $response->viewData('report');
        $this->assertEquals(0, $report['total_applicants']);
        $this->assertEquals(0, $report['confirmed_beneficiaries']);
        $this->assertEquals(0.0, $report['utilization_rate']);
        $this->assertEquals(0.0, $report['rural_rate']);
        $this->assertEquals(0, $report['slots_filled']);
    }

    public function test_all_four_filter_dropdowns_evaluate_correctly_in_combination(): void
    {
        // All 4 filter dropdowns combined: Program, Academic Year, Semester, Campus
        $response = $this->actingAs($this->fassg)
            ->get('/fassg/reports?sponsorship_program_id=24&academic_year=2026-2027&semester=First&campus=Main+Campus+%28City+of+Mati%29')
            ->assertOk();

        $report = $response->viewData('report');
        $this->assertEquals(1, $report['total_applicants']);
        $this->assertEquals(0, $report['confirmed_beneficiaries']);
        $this->assertEquals(0.0, $report['utilization_rate']);
    }
}
