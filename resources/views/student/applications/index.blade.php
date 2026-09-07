@extends('layouts.app')

@section('title', 'My Applications')
@section('eyebrow', 'Student Portal')
@section('page-title', 'My Applications')

@section('content')

    @php $profile = auth()->user()->studentProfile; @endphp

    @if ($profile->hasActiveSponsorship())
        <div class="alert border-0 shadow-sm rounded-3 d-flex align-items-center gap-3 mb-4" style="background:#eefaf0;">
            <div class="sf-stat-icon bg-success-subtle text-success"><i class="bi bi-award-fill"></i></div>
            <div>
                <div class="fw-semibold text-success-emphasis">You have an active sponsorship.</div>
                <div class="small text-secondary">New applications are disabled until this one expires, to keep sponsorships
                    to one active grant at a time.</div>
            </div>
        </div>
    @endif

    @if ($applications->isEmpty())
        <div class="card sf-card">
            <div class="sf-empty-state">
                <i class="bi bi-file-earmark-text"></i>
                <div class="fw-semibold">You haven't applied to any programs yet</div>
                <div class="small mb-3">Browse open sponsorship programs and submit your first application.</div>
                <a href="{{ route('student.programs.index') }}"
                    class="btn btn-sm text-white fw-semibold py-2 px-3 rounded-3 shadow-sm"
                    style="background-color: #0f294a; border: none; font-size: 0.85rem;">Browse Programs</a>
            </div>
        </div>
    @else
        <div class="card sf-card">
            <div class="table-responsive overflow-x-auto w-full -mx-4 px-4 sm:mx-0 sm:px-0">
                <table class="table sf-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4 whitespace-nowrap">Program</th>
                            <th class="whitespace-nowrap">Sponsor</th>
                            <th class="whitespace-nowrap">Submitted</th>
                            <th class="whitespace-nowrap">Status</th>
                            <th class="text-end pe-4 whitespace-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($applications as $application)
                            <tr>
                                <td class="ps-4 fw-semibold whitespace-nowrap">
                                    {{ $application->sponsorshipProgram->program_name }}</td>
                                <td class="text-secondary whitespace-nowrap">
                                    {{ $application->sponsorshipProgram->sponsor->company_organization_name }}</td>
                                <td class="text-secondary whitespace-nowrap">
                                    {{ optional($application->submitted_at)->format('M d, Y') ?? '—' }}</td>
                                <td class="whitespace-nowrap"><x-status-badge :status="$application->status" /></td>
                                <td class="text-end pe-4 whitespace-nowrap">
                                    <a href="{{ route('student.applications.show', $application) }}"
                                        class="btn btn-sm btn-outline-secondary">
                                        View <i class="bi bi-chevron-right"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    @endif

@endsection
