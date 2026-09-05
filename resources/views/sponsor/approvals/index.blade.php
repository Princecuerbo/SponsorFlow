@extends('layouts.app')

@section('title', 'Approvals Queue')
@section('eyebrow', 'Sponsor Portal')
@section('page-title', 'Approvals Queue')

@push('styles')
    <style>
        .btn-navy-primary,
        a.btn-navy-primary,
        button.btn-navy-primary {
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
            color: #fff !important;
            font-weight: 600;
        }

        .btn-navy-primary:hover,
        .btn-navy-primary:focus {
            background-color: #0A1E31 !important;
            border-color: #0A1E31 !important;
            color: #fff !important;
        }

        /* Custom Navy Styles for Bootstrap Nav Tabs */
        .nav-tabs-navy {
            border-bottom: 1px solid #e5e7eb;
        }

        .nav-tabs-navy .nav-link {
            color: #4b5563 !important;
            font-weight: 500;
            border: 1px solid transparent;
            border-top-left-radius: 0.375rem;
            border-top-right-radius: 0.375rem;
            padding: 0.6rem 1.25rem;
            background-color: transparent;
        }

        .nav-tabs-navy .nav-link:hover {
            color: #0F2942 !important;
            border-color: #e5e7eb #e5e7eb #f3f4f6;
        }

        .nav-tabs-navy .nav-link.active {
            color: #0F2942 !important;
            font-weight: 700;
            background-color: #ffffff !important;
            border-color: #e5e7eb #e5e7eb #ffffff !important;
        }
    </style>
@endpush

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="text-uppercase small fw-semibold text-secondary mb-2">Sponsor review</p>
            <h1 class="h2 sf-heading mb-1 fw-bold">Review Queue</h1>
            <p class="text-secondary mb-0">Review FASSG-verified applicants and submitted beneficiary lists.</p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-primary-subtle text-primary-emphasis px-3 py-2">{{ $applicants->count() }}
                applicants</span>
            <span class="badge bg-info-subtle text-info-emphasis px-3 py-2">{{ $fixedLists->count() }} fixed lists</span>
        </div>
    </div>

    <div class="card sf-card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('sponsor.approvals.index') }}" class="row g-2">
                <div class="col-md-4">
                    <select name="academic_program_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All academic programs</option>
                        @foreach ($academicPrograms as $academicProgram)
                            <option value="{{ $academicProgram->program_id }}" @selected((int) request('academic_program_id') === (int) $academicProgram->program_id)>
                                {{ $academicProgram->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="course" class="form-select" onchange="this.form.submit()">
                        <option value="">All courses</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course }}" @selected(request('course') === $course)>{{ $course }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('sponsor.approvals.index') }}" class="btn btn-outline-secondary w-100">Clear
                        filters</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Functional Bootstrap Nav Tabs --}}
    <ul class="nav nav-tabs nav-tabs-navy mb-4" id="approvalQueueTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="individual-applicants-tab" data-bs-toggle="tab"
                data-bs-target="#individual-applicants-pane" type="button" role="tab"
                aria-controls="individual-applicants-pane" aria-selected="true">
                Individual Applicants
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="fixed-lists-tab" data-bs-toggle="tab" data-bs-target="#fixed-lists-pane"
                type="button" role="tab" aria-controls="fixed-lists-pane" aria-selected="false">
                Fixed Lists
            </button>
        </li>
    </ul>

    {{-- Tab Content Container --}}
    <div class="tab-content" id="approvalQueueTabContent">

        {{-- Pane 1: Individual Applicants --}}
        <div class="tab-pane fade show active" id="individual-applicants-pane" role="tabpanel"
            aria-labelledby="individual-applicants-tab" tabindex="0">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h2 class="h5 sf-heading mb-1 fw-bold">Forwarded Individual Applicants</h2>
                    <p class="small text-secondary mb-0">Students verified by FASSG and awaiting sponsor endorsement.</p>
                </div>
                <i class="bi bi-person-check fs-3" style="color: #0F2942;"></i>
            </div>

            <div class="card sf-card border-0 shadow-sm mb-4">
                <div class="table-responsive">
                    <table class="table sf-table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">Student</th>
                                <th>Program</th>
                                <th>GWA</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($applicants as $application)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-semibold">{{ $application->studentProfile->user->name }}</div>
                                        <div class="small text-secondary sf-mono">
                                            {{ $application->studentProfile->student_id_number }}</div>
                                        <div class="small text-secondary">{{ $application->studentProfile->course }} · Year
                                            {{ $application->studentProfile->year_level }}</div>
                                    </td>
                                    <td>{{ $application->sponsorshipProgram->program_name }}</td>
                                    <td>{{ number_format($application->gpa_submitted, 2) }}</td>
                                    <td><span class="badge bg-success-subtle text-success-emphasis">FASSG Verified</span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="{{ route('sponsor.applicants.show', $application) }}"
                                            class="btn btn-sm btn-navy-primary">
                                            <i class="bi bi-eye me-1"></i>Review &amp; Confirm
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-secondary py-5">No forwarded individual
                                        applicants.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pane 2: Fixed Lists --}}
        <div class="tab-pane fade" id="fixed-lists-pane" role="tabpanel" aria-labelledby="fixed-lists-tab" tabindex="0">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h2 class="h5 sf-heading mb-1 fw-bold">Sponsor-Provided Fixed Lists</h2>
                    <p class="small text-secondary mb-0">Batch lists submitted by FASSG for sponsor document upload and
                        confirmation.</p>
                </div>
                <i class="bi bi-people fs-3" style="color: #0F2942;"></i>
            </div>

            <div class="card sf-card border-0 shadow-sm mb-4">
                <div class="table-responsive">
                    <table class="table sf-table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">Batch / Program</th>
                                <th>Beneficiaries</th>
                                <th>Status</th>
                                <th>Signed Document</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($fixedLists as $list)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-semibold">{{ $list->batch_name }}</div>
                                        <div class="small text-secondary">{{ $list->sponsorshipProgram->program_name }}
                                        </div>
                                    </td>
                                    <td>{{ $list->total_names }} {{ Str::plural('student', $list->total_names) }}</td>
                                    <td><span class="badge bg-info-subtle text-info-emphasis">Submitted</span></td>
                                    <td>
                                        @if (!empty($list->approval_document_path))
                                            <span
                                                class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-2 py-1">
                                                <i class="bi bi-file-check me-1"></i>Uploaded
                                            </span>
                                        @else
                                            <span class="text-secondary small">
                                                <i class="bi bi-dash-circle me-1"></i>Pending Upload
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="{{ route('sponsor.lists.show', $list) }}"
                                            class="btn btn-sm btn-navy-primary">
                                            <i class="bi bi-check2-circle me-1"></i>Review &amp; Confirm
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-secondary py-5">No submitted fixed lists
                                        awaiting approval.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection
