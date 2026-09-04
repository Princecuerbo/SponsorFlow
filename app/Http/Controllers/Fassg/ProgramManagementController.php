<?php

namespace App\Http\Controllers\Fassg;

use App\Enums\ApplicationStatus;
use App\Enums\ProgramStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Concerns\ResolvesModuleContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fassg\StoreSponsorshipProgramRequest;
use App\Http\Requests\Fassg\UpdateSponsorshipProgramRequest;
use App\Models\AcademicProgram;
use App\Models\Sponsor;
use App\Models\SponsorshipProgram;
use App\Models\StudentProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ProgramManagementController extends Controller
{
    use ResolvesModuleContext;

    public function index(Request $request): View
    {
        if (Schema::hasColumn('sponsorship_programs', 'end_date')) {
            SponsorshipProgram::query()
                ->where(function ($query): void {
                    $query->where('status', ProgramStatus::Expired)
                        ->orWhere(function ($query): void {
                            $query->where('status', '!=', ProgramStatus::Expired)
                                ->whereNotNull('end_date')
                                ->where('end_date', '<', now()->startOfDay());
                        });
                })
                ->get()
                ->each(function (SponsorshipProgram $program): void {
                    if ($program->status !== ProgramStatus::Expired) {
                        $program->update(['status' => ProgramStatus::Expired]);
                    }

                    $program->cascadeExpiredApplications();
                });
        }

        $programs = SponsorshipProgram::query()
            ->with('sponsor')
            ->withCount('applications')
            ->when(
                $request->filled('status'),
                fn($query) => $query->where(
                    'status',
                    ProgramStatus::tryFrom(ucfirst(strtolower($request->string('status')->toString()))),
                ),
            )
            ->latest()
            ->get();

        return view('fassg.programs.index', [
            'user' => $this->actor($request),
            'programs' => $programs,
        ]);
    }

    public function create(Request $request): View
    {
        return view('fassg.programs.create', [
            'user' => $this->actor($request),
            'program' => new SponsorshipProgram,
            'sponsors' => $this->availableSponsors(),
            'academicPrograms' => $this->availableAcademicPrograms(),
        ]);
    }

    public function store(StoreSponsorshipProgramRequest $request): RedirectResponse
    {
        $program = SponsorshipProgram::query()->create([
            ...$request->validated(),
            'status' => ProgramStatus::Open,
        ]);

        $program->academicPrograms()->sync($request->input('academic_program_ids', []));

        $this->audit($request, 'fassg.program.created', 'sponsorship_programs');

        return redirect()
            ->route('fassg.programs.index')
            ->with('status', "Program {$program->program_name} was created and opened.");
    }

    public function edit(Request $request, SponsorshipProgram $sponsorshipProgram): View
    {
        return view('fassg.programs.edit', [
            'user' => $this->actor($request),
            'program' => $sponsorshipProgram,
            'sponsors' => $this->availableSponsors(),
            'academicPrograms' => $this->availableAcademicPrograms(),
        ]);
    }

    public function update(UpdateSponsorshipProgramRequest $request, SponsorshipProgram $sponsorshipProgram): RedirectResponse
    {
        $requestedStatus = $request->enum('status', ProgramStatus::class);
        $shouldOpen = $requestedStatus === ProgramStatus::Open
            && (int) $request->input('available_slots') > 0;
        $shouldExpire = ! $shouldOpen && ($requestedStatus === ProgramStatus::Expired
            || ($request->filled('end_date') && Carbon::parse($request->input('end_date'))->isPast()));

        DB::transaction(function () use ($request, $sponsorshipProgram, $shouldExpire, $shouldOpen): void {
            $attributes = $request->validated();

            if ($shouldOpen) {
                $attributes['status'] = ProgramStatus::Open;
            } elseif ($shouldExpire) {
                $attributes['status'] = ProgramStatus::Expired;
            }

            $sponsorshipProgram->update($attributes);

            $sponsorshipProgram->academicPrograms()->sync($request->input('academic_program_ids', []));

            if ($shouldExpire) {
                $applicationIds = $sponsorshipProgram->applications()
                    ->whereIn('status', [
                        ApplicationStatus::Approved,
                        ApplicationStatus::Ongoing,
                    ])
                    ->pluck('id');

                if ($applicationIds->isNotEmpty()) {
                    $sponsorshipProgram->applications()
                        ->whereKey($applicationIds)
                        ->update(['status' => ApplicationStatus::Expired]);

                    StudentProfile::query()
                        ->whereIn('active_sponsorship_id', $applicationIds)
                        ->update(['active_sponsorship_id' => null]);
                }
            }
        });

        $this->audit($request, 'fassg.program.updated', 'sponsorship_programs');

        return redirect()
            ->route('fassg.programs.index')
            ->with('status', "Program {$sponsorshipProgram->program_name} was updated.");
    }

    private function availableSponsors()
    {        User::query()
            ->where('role', UserRole::Sponsor)
            ->each(function (User $user): void {
                Sponsor::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'company_organization_name' => $user->name,
                        'contact_person' => $user->name,
                        'contact_email' => $user->email,
                    ],
                );
            });

        return Sponsor::query()
            ->whereNotIn('company_organization_name', ['A', 'B'])
            ->whereNotNull('company_organization_name')
            ->whereHas('user', fn($query) => $query
                ->where('role', UserRole::Sponsor)
                ->where('status', UserStatus::Active))
            ->orderBy('company_organization_name')
            ->get();
    }

    private function availableAcademicPrograms()
    {
        return AcademicProgram::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    public function open(Request $request, SponsorshipProgram $sponsorshipProgram): RedirectResponse
    {
        $sponsorshipProgram->update(['status' => ProgramStatus::Open]);

        $this->audit($request, 'fassg.program.opened', 'sponsorship_programs');

        return back()->with('status', "Program {$sponsorshipProgram->program_name} is now open.");
    }

    public function reopen(Request $request, SponsorshipProgram $sponsorshipProgram): RedirectResponse
    {
        if ($sponsorshipProgram->available_slots <= 0) {
            return back()->with('error', 'Cannot reopen a program with 0 available slots. Please edit available slots first.');
        }

        $sponsorshipProgram->update(['status' => ProgramStatus::Open]);

        $this->audit($request, 'fassg.program.reopened', 'sponsorship_programs');

        return back()->with('success', 'Program successfully reopened for student applications.');
    }

    public function close(Request $request, SponsorshipProgram $sponsorshipProgram): RedirectResponse
    {
        $sponsorshipProgram->update(['status' => ProgramStatus::Closed]);

        $this->audit($request, 'fassg.program.closed', 'sponsorship_programs');

        return back()->with('status', "Program {$sponsorshipProgram->program_name} is now closed.");
    }

    public function expire(Request $request, SponsorshipProgram $sponsorshipProgram): RedirectResponse
    {
        DB::transaction(function () use ($sponsorshipProgram): void {
            $sponsorshipProgram->update(['status' => ProgramStatus::Expired]);
        });

        $this->audit($request, 'fassg.program.expired', 'sponsorship_programs');

        return back()->with('status', "Program {$sponsorshipProgram->program_name} has expired.");
    }

    public function toggleStatus(Request $request, SponsorshipProgram $sponsorshipProgram): RedirectResponse
    {
        return $sponsorshipProgram->isOpen()
            ? $this->close($request, $sponsorshipProgram)
            : $this->open($request, $sponsorshipProgram);
    }

    public function destroy(Request $request, SponsorshipProgram $sponsorshipProgram): RedirectResponse
    {
        if ($sponsorshipProgram->applications()->exists()) {
            return back()->withErrors([
                'program' => 'Cannot delete a program that already has applications.',
            ]);
        }

        $name = $sponsorshipProgram->program_name;
        $sponsorshipProgram->delete();

        $this->audit($request, 'fassg.program.deleted', 'sponsorship_programs');

        return redirect()
            ->route('fassg.programs.index')
            ->with('status', "Program {$name} was deleted.");
    }
}
