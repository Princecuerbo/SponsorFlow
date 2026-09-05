@extends('layouts.app')

@section('title', 'Forwarded Applicants')
@section('eyebrow', 'Sponsor Portal')
@section('page-title', 'Forwarded Applicants & Lists')

@push('styles')
    <style>
        .btn-navy-primary,
        a.btn-navy-primary,
        button.btn-navy-primary {
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
            color: #ffffff !important;
            font-weight: 600;
            box-shadow: none !important;
            transition: all 0.2s ease-in-out;
        }

        .btn-navy-primary:hover,
        .btn-navy-primary:focus,
        a.btn-navy-primary:hover,
        a.btn-navy-primary:focus,
        button.btn-navy-primary:hover,
        button.btn-navy-primary:focus {
            background-color: #0A1E31 !important;
            border-color: #0A1E31 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(15, 41, 66, 0.15) !important;
        }
    </style>
@endpush

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="text-uppercase small fw-semibold text-secondary mb-2">Sponsor review</p>
            <h1 class="h2 sf-heading mb-1 fw-bold">Forwarded Applicants &amp; Lists</h1>
            <p class="text-secondary mb-0">Review FASSG-verified applications and sponsor-provided beneficiary lists.</p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge px-3 py-2 text-white" style="background-color: #0F2942;">{{ $applicants->count() }}
                applications</span>
            <span class="badge text-bg-light border text-dark px-3 py-2">{{ $fixedLists->count() }} fixed lists</span>
        </div>
    </div>

    <div class="card sf-card mb-4 border-0 shadow-sm">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('sponsor.applicants.index') }}" class="row g-2">
                <div class="col-md-4">
                    <select name="academic_program_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All academic programs</option>
                        @foreach ($academicPrograms as $academicProgram)
                            <option value="{{ $academicProgram->program_id }}" @selected((int) request('academic_program_id') === (int) $academicProgram->program_id)>
                                {{ $academicProgram->name }}</option>
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
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Actionable Applicants</option>
                        <option value="verified" @selected(request('status') === 'verified')>FASSG Verified</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('sponsor.applicants.index') }}" class="btn btn-outline-secondary w-100">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <section class="mb-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h2 class="h5 sf-heading mb-1 fw-bold">Forwarded Applications</h2>
                <p class="small text-secondary mb-0">Individual students verified by FASSG and awaiting sponsor
                    confirmation.</p>
            </div>
            <i class="bi bi-person-check fs-3" style="color: #0F2942;"></i>
        </div>

        @if ($applicants->isEmpty())
            <div class="card sf-card border-0 shadow-sm">
                <div class="sf-empty-state text-center p-5">
                    <i class="bi bi-person-lines-fill text-secondary fs-1 d-block mb-3"></i>
                    <div class="fw-semibold">No forwarded applications</div>
                    <div class="small text-secondary">FASSG-verified applications will appear here.</div>
                </div>
            </div>
        @else
            <div class="card sf-card border-0 shadow-sm">
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
                            @foreach ($applicants as $application)
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
                                    <td><x-status-badge :status="$application->status" /></td>
                                    <td class="text-end pe-4">
                                        <a href="{{ route('sponsor.applicants.show', $application) }}"
                                            class="btn btn-sm btn-navy-primary">
                                            <i class="bi bi-eye me-1"></i>Review &amp; Confirm
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </section>

    <section>
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h2 class="h5 sf-heading mb-1 fw-bold">Sponsor-Provided Fixed Lists</h2>
                <p class="small text-secondary mb-0">Batch lists forwarded by FASSG for sponsor confirmation.</p>
            </div>
            <i class="bi bi-people fs-3" style="color: #0F2942;"></i>
        </div>

        @if ($fixedLists->isEmpty())
            <div class="card sf-card border-0 shadow-sm">
                <div class="sf-empty-state text-center p-5">
                    <i class="bi bi-inboxes text-secondary fs-1 d-block mb-3"></i>
                    <div class="fw-semibold">No fixed lists forwarded yet</div>
                    <div class="small text-secondary">FASSG lists will appear here once submitted.</div>
                </div>
            </div>
        @else
            <div class="card sf-card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table sf-table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">Batch</th>
                                <th>Program</th>
                                <th>Names</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($fixedLists as $list)
                                <tr>
                                    <td class="ps-4 fw-semibold">{{ $list->batch_name }}</td>
                                    <td>{{ $list->sponsorshipProgram->program_name }}</td>
                                    <td>{{ $list->total_names }}</td>
                                    <td><x-status-badge :status="$list->status" /></td>
                                    <td class="text-end pe-4">
                                        <a href="{{ route('sponsor.lists.show', $list) }}"
                                            class="btn btn-sm btn-outline-secondary">
                                            Review List <i class="bi bi-chevron-right ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </section>
@endsection
