@extends('layouts.app')

@section('title', 'Application Review Queue')
@section('eyebrow', 'FASSG Office')
@section('page-title', 'Application Review Queue')

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

        .filter-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
        }

        .stat-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .bg-indigo-50 {
            background-color: #eef2ff !important;
        }

        .text-indigo-700 {
            color: #4338ca !important;
        }

        .border-indigo-200 {
            border-color: #c7d2fe !important;
        }

        /* Cyan – Verified */
        .bg-cyan-50 {
            background-color: #ecfeff !important;
        }

        .text-cyan-700 {
            color: #0e7490 !important;
        }

        .border-cyan-200 {
            border-color: #a5f3fc !important;
        }

        /* Emerald – Approved / Uploaded */
        .bg-emerald-50 {
            background-color: #ecfdf5 !important;
        }

        .text-emerald-700 {
            color: #047857 !important;
        }

        .border-emerald-200 {
            border-color: #a7f3d0 !important;
        }

        .rounded-full {
            border-radius: 9999px !important;
        }
    </style>
@endpush

@section('content')
    {{-- Page Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="text-uppercase small fw-semibold text-secondary mb-2">FASSG Office · Verification</p>
            <h1 class="display-6 fw-bold mb-1">Application Review Queue</h1>
            <p class="text-secondary mb-0">Review unverified student profiles and submitted applications.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <div class="px-3 py-1.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 text-sm font-medium flex items-center gap-1.5" style="display: inline-flex; align-items: center; gap: 0.375rem; border-radius: 9999px; padding: 0.375rem 0.875rem; font-weight: 500; font-size: 0.875rem; background-color: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-4 h-4" style="width: 1rem; height: 1rem; flex-shrink: 0;" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" />
                </svg>
                <span>{{ $pendingSleFheCount ?? 0 }} Pending SLE-FHE</span>
            </div>
            <span class="stat-pill" style="display: inline-flex; align-items: center; gap: 0.35rem; border-radius: 9999px; padding: 0.375rem 0.875rem; font-weight: 500; font-size: 0.875rem; background-color: #fffbebf5; color: #b45309; border: 1px solid #fde68a;">
                <i class="bi bi-hourglass-split"></i> {{ $statusCounts['pending'] }} pending
            </span>
            <span class="stat-pill bg-cyan-50 text-cyan-700 border border-cyan-200 rounded-full" style="display: inline-flex; align-items: center; gap: 0.35rem; border-radius: 9999px; padding: 0.375rem 0.875rem; font-weight: 500; font-size: 0.875rem; background-color: #ecfeff; color: #0e7490; border: 1px solid #a5f3fc;">
                <i class="bi bi-patch-check"></i> {{ $statusCounts['verified'] }} verified
            </span>
            <span class="stat-pill bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full" style="display: inline-flex; align-items: center; gap: 0.35rem; border-radius: 9999px; padding: 0.375rem 0.875rem; font-weight: 500; font-size: 0.875rem; background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                <i class="bi bi-award"></i> {{ $statusCounts['approved'] }} approved
            </span>
            <span class="stat-pill" style="display: inline-flex; align-items: center; gap: 0.35rem; border-radius: 9999px; padding: 0.375rem 0.875rem; font-weight: 500; font-size: 0.875rem; background-color: #fff1f2; color: #be123c; border: 1px solid #fecdd3;">
                <i class="bi bi-x-circle"></i> {{ $statusCounts['rejected'] }} rejected
            </span>
            <span class="stat-pill" style="display: inline-flex; align-items: center; gap: 0.35rem; border-radius: 9999px; padding: 0.375rem 0.875rem; font-weight: 500; font-size: 0.875rem; background-color: #fff7ed; color: #c2410c; border: 1px solid #ffedd5;">
                <i class="bi bi-arrow-counterclockwise"></i> {{ $statusCounts['resubmission'] }} resubmission
            </span>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filter Bar --}}
    <div class="card filter-card mb-4 rounded-3">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('fassg.verification.index') }}" class="row g-2 align-items-end">
                {{-- Program --}}
                <div class="col-md-3">
                    <label class="form-label small text-secondary fw-semibold mb-1">Program</label>
                    <select name="program_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All programs</option>
                        @foreach ($programs as $prog)
                            <option value="{{ $prog->id }}" @selected((int) request('program_id') === $prog->id)>
                                {{ $prog->program_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Category --}}
                <div class="col-md-2">
                    <label class="form-label small text-secondary fw-semibold mb-1">Category</label>
                    <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->value }}" @selected(request('category') === $cat->value)>
                                {{ $cat->value }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div class="col-md-2">
                    <label class="form-label small text-secondary fw-semibold mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        <option value="pending_sle_fhe" @selected(request('status') === 'pending_sle_fhe')>Pending SLE-FHE</option>
                        <option value="Pending"  @selected(request('status') === 'Pending')>Pending</option>
                        <option value="Verified" @selected(request('status') === 'Verified')>Verified</option>
                        <option value="Approved" @selected(request('status') === 'Approved')>Approved</option>
                        <option value="Rejected" @selected(request('status') === 'Rejected')>Rejected</option>
                        <option value="Resubmission Requested" @selected(request('status') === 'Resubmission Requested')>Resubmission Requested</option>
                    </select>
                </div>

                {{-- Search --}}
                <div class="col-md-4">
                    <label class="form-label small text-secondary fw-semibold mb-1">Search</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-secondary"></i>
                        </span>
                        <input type="text" name="q" value="{{ request('q') }}"
                            class="form-control border-start-0 ps-0"
                            placeholder="Name, student ID…"
                            oninput="clearTimeout(window.searchTimer); window.searchTimer = setTimeout(() => this.form.submit(), 600)">
                    </div>
                </div>

                {{-- Reset --}}
                <div class="col-md-1">
                    <a href="{{ route('fassg.verification.index') }}"
                        class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Queue Table --}}
    @if ($verificationItems->isEmpty())
        <div class="card sf-card">
            <div class="sf-empty-state">
                <i class="bi bi-inboxes"></i>
                <div class="fw-semibold">Queue is empty</div>
                <div class="small">No items match the current filters.</div>
            </div>
        </div>
    @else
        <div class="card sf-card">
            <div class="table-responsive">
                <table class="table sf-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Student</th>
                            <th>Course &amp; Year</th>
                            <th>Program</th>
                            <th>Category</th>
                            <th>Documents</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($verificationItems as $item)
                            @php
                                $profile     = $item['profile'];
                                $application = $item['application'];
                            @endphp
                            <tr>
                                {{-- Student --}}
                                <td class="ps-4">
                                    <div class="fw-semibold">{{ $profile->user->name }}</div>
                                    <div class="small text-secondary sf-mono">{{ $profile->student_id_number ?: '—' }}</div>
                                </td>

                                {{-- Course & Year --}}
                                <td>
                                    <div>{{ $profile->course ?: '—' }}</div>
                                    <div class="small text-secondary">
                                        @if ($profile->year_level)
                                            Year {{ $profile->year_level }}
                                        @else
                                            <span class="text-muted">Year N/A</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Program --}}
                                <td>
                                    @if ($application)
                                        <div class="small fw-semibold text-break" style="max-width:180px;">
                                            {{ $application->sponsorshipProgram->program_name }}
                                        </div>
                                        <div class="small text-secondary">
                                            {{ $application->sponsorshipProgram->sponsor->company_organization_name }}
                                        </div>
                                    @else
                                        <span class="text-secondary small">Profile only</span>
                                    @endif
                                </td>

                                {{-- Category --}}
                                <td>
                                    @if ($application)
                                        <span class="badge rounded-2 fw-medium"
                                            style="background-color: rgba(15,41,66,0.08); color:#0F2942;">
                                            {{ $application->sponsorshipProgram->category->value }}
                                        </span>
                                    @else
                                        <span class="text-secondary">—</span>
                                    @endif
                                </td>

                                {{-- Documents --}}
                                <td>
                                    @if ($application)
                                        @php $docCounts = $application->documentStatusCounts(); @endphp
                                        <span class="badge {{ $docCounts['uploaded'] >= $docCounts['required'] ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis' }}">
                                            {{ $docCounts['uploaded'] }}/{{ $docCounts['required'] }} docs
                                        </span>
                                    @else
                                        <span class="text-secondary small">Profile registration</span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td>
                                    @if ($item['type'] === 'student')
                                        <span class="px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 text-xs font-medium flex items-center gap-1.5 d-inline-flex align-items-center"
                                            style="display: inline-flex; align-items: center; gap: 0.35rem; border-radius: 9999px; padding: 0.25rem 0.65rem; font-weight: 500; font-size: 0.8rem; background-color: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe;">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-3.5 h-3.5" style="width: 0.875rem; height: 0.875rem; flex-shrink: 0;" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" />
                                            </svg>
                                            Pending SLE-FHE
                                        </span>
                                    @else
                                        <x-status-badge :status="$application->status" />
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="text-end pe-4">
                                    @if ($item['type'] === 'student')
                                        <div class="d-flex justify-content-end gap-2">
                                            <form method="POST"
                                                action="{{ route('fassg.verification.students.verify', $profile) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-navy-primary">
                                                    <i class="bi bi-check2-circle me-1"></i>Verify SLE-FHE
                                                </button>
                                            </form>
                                            <form method="POST"
                                                action="{{ route('fassg.verification.students.reject', $profile) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-arrow-return-left me-1"></i>Request Fix
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <a href="{{ route('fassg.verification.show', $application) }}"
                                            class="btn btn-sm btn-navy-primary">
                                            Review <i class="bi bi-chevron-right"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
