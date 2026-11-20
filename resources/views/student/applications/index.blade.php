@extends('layouts.app')

@section('title', 'My Applications')
@section('eyebrow', 'Student Portal · Applications')
@section('page-title', 'My Applications')
@section('subtitle', 'Track the status of your submitted sponsorship applications.')

@push('styles')
    <style>
        /* Scoped to My Applications; preserves Bootstrap table/flex behaviour. */
        .sf-apps-card {
            border-radius: 14px;
            border-color: #E1E8EC;
            box-shadow: 0 1px 3px 0 rgba(15, 37, 55, 0.05);
            overflow: hidden;
        }

        .sf-apps-table thead th {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #64748B;
            vertical-align: middle;
            padding-top: 0.85rem;
            padding-bottom: 0.85rem;
            background-color: #F8FAFC;
        }

        .sf-apps-table tbody td {
            vertical-align: middle;
            padding-top: 0.9rem;
            padding-bottom: 0.9rem;
        }

        .sf-apps-table .sf-apps-empty {
            padding: 3rem 1rem;
        }

        .sf-apps-empty a.btn:focus-visible {
            outline: 3px solid #0F2942;
            outline-offset: 2px;
        }

        @media (max-width: 575.98px) {
            .sf-apps-table .sf-apps-empty {
                padding: 2.25rem 0.75rem;
            }
        }
    </style>
@endpush

@section('content')
    @php $profile = auth()->user()->studentProfile; @endphp

    @if ($profile->hasActiveSponsorship())
        <div class="alert border-0 rounded-3 p-3 mb-4 d-flex align-items-start gap-3"
            style="background-color: #ECFDF5; border: 1px solid #A7F3D0 !important;" role="status">
            <span class="sf-stat-icon flex-shrink-0"
                style="width: 36px; height: 36px; border-radius: 10px; background-color: #D1FAE5; color: #047857;">
                <i class="bi bi-award" aria-hidden="true"></i>
            </span>
            <div>
                <div class="fw-semibold text-dark">Active sponsorship in effect</div>
                <div class="small text-secondary">New applications are disabled until this one expires, to keep sponsorships
                    to one active grant at a time.</div>
            </div>
        </div>
    @endif

    <div class="card sf-card sf-apps-card">
        @if ($applications->isEmpty())
            <div class="sf-empty-state sf-apps-empty">
                <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                <div class="fw-semibold text-dark mb-1">No applications yet</div>
                <div class="small mb-3">Browse open sponsorship programs and submit your first application.</div>
                <a href="{{ route('student.programs.index') }}"
                    class="btn btn-sm text-white fw-semibold py-2 px-3 rounded-3"
                    style="background-color: #0F2942; border: none; font-size: 0.8125rem;">
                    <i class="bi bi-search me-1" aria-hidden="true"></i> Browse Sponsorship Opportunities
                </a>
            </div>
        @else
            <div class="table-responsive overflow-auto w-100">
                <table class="table sf-table sf-apps-table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="ps-4 text-nowrap">Program</th>
                            <th scope="col" class="text-nowrap">Sponsor</th>
                            <th scope="col" class="text-nowrap">Submitted</th>
                            <th scope="col" class="text-nowrap">Status</th>
                            <th scope="col" class="text-end pe-4 text-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($applications as $application)
                            <tr>
                                <td class="ps-4 fw-semibold text-dark">
                                    {{ $application->sponsorshipProgram->program_name }}</td>
                                <td class="text-secondary">
                                    {{ $application->sponsorshipProgram->sponsor->company_organization_name }}</td>
                                <td class="text-secondary text-nowrap">
                                    {{ optional($application->submitted_at)->format('M d, Y, h:i A') ?? '—' }}</td>
                                <td><x-student-application-status-badge :status="$application->status" /></td>
                                <td class="text-end pe-4 text-nowrap">
                                    <a href="{{ route('student.applications.show', $application) }}"
                                        class="sf-btn-student-secondary"
                                        aria-label="View details for application to {{ $application->sponsorshipProgram?->program_name ?? 'program' }}">
                                        <i class="bi bi-eye" aria-hidden="true"></i> View Details
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
