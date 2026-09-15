@extends('layouts.app')

@section('title', 'SLE-FHE Verification')
@section('eyebrow', 'FASSG Office')
@section('page-title', 'SLE-FHE Verification')

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
            <h1 class="display-6 fw-bold mb-1">SLE-FHE Verification</h1>
            <p class="text-secondary mb-0">Verify student profiles awaiting secondary board eligibility confirmation.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <span class="stat-pill" style="display: inline-flex; align-items: center; gap: 0.35rem; border-radius: 9999px; padding: 0.375rem 0.875rem; font-weight: 500; font-size: 0.875rem; background-color: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe;">
                <i class="bi bi-hourglass-split"></i> {{ $pendingSleFheCount ?? 0 }} pending profiles
            </span>
            <span class="stat-pill" style="display: inline-flex; align-items: center; gap: 0.35rem; border-radius: 9999px; padding: 0.375rem 0.875rem; font-weight: 500; font-size: 0.875rem; background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                <i class="bi bi-patch-check"></i> {{ $verifiedSleFheCount ?? 0 }} verified
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

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filter Bar --}}
    <div class="card filter-card mb-4 rounded-3">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('fassg.sle-fhe.index') }}" class="row g-2 align-items-end">
                {{-- Campus --}}
                <div class="col-md-3">
                    <label class="form-label small text-secondary fw-semibold mb-1">Campus</label>
                    <select name="campus" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All campuses</option>
                        @foreach (['Main Campus (City of Mati)', 'Baganga Campus', 'Banaybanay Campus', 'Cateel Campus', 'San Isidro Campus', 'Tarragona Campus'] as $campus)
                            <option value="{{ $campus }}" @selected(request('campus') === $campus)>{{ $campus }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Search --}}
                <div class="col-md-8">
                    <label class="form-label small text-secondary fw-semibold mb-1">Search</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-secondary"></i>
                        </span>
                        <input type="text" name="q" value="{{ request('q') }}"
                            class="form-control border-start-0 ps-0"
                            placeholder="Name, student ID, course…"
                            oninput="clearTimeout(window.searchTimer); window.searchTimer = setTimeout(() => this.form.submit(), 600)">
                    </div>
                </div>

                {{-- Reset --}}
                <div class="col-md-1">
                    <a href="{{ route('fassg.sle-fhe.index') }}"
                        class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Queue Table --}}
    @if ($pendingProfiles->isEmpty())
        <div class="card sf-card">
            <div class="sf-empty-state">
                <i class="bi bi-patch-check"></i>
                <div class="fw-semibold">No pending profiles</div>
                <div class="small">No student profiles match the current filters.</div>
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
                            <th>Campus</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendingProfiles as $profile)
                            <tr>
                                {{-- Student --}}
                                <td class="ps-4">
                                    <div class="fw-semibold">{{ $profile->user->name ?? trim($profile->first_name . ' ' . ($profile->middle_name ?? '') . ' ' . $profile->last_name . ($profile->extension_name ? ' ' . $profile->extension_name : '')) }}</div>
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

                                {{-- Campus --}}
                                <td>
                                    <span class="badge rounded-2 fw-medium"
                                        style="background-color: rgba(15,41,66,0.08); color:#0F2942;">
                                        {{ $profile->campus ?: 'Not Assigned' }}
                                    </span>
                                </td>

                                {{-- Status --}}
                                <td>
                                    <span class="px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 text-xs font-medium flex items-center gap-1.5 d-inline-flex align-items-center"
                                        style="display: inline-flex; align-items: center; gap: 0.35rem; border-radius: 9999px; padding: 0.25rem 0.65rem; font-weight: 500; font-size: 0.8rem; background-color: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe;">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" class="w-3.5 h-3.5" style="width: 0.875rem; height: 0.875rem; flex-shrink: 0;" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" />
                                        </svg>
                                        Pending SLE-FHE
                                    </span>
                                </td>

                                {{-- Actions --}}
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <form method="POST"
                                            action="{{ route('fassg.sle-fhe.verify', $profile) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-navy-primary">
                                                <i class="bi bi-check2-circle me-1"></i>Verify SLE-FHE
                                            </button>
                                        </form>
                                        <form method="POST"
                                            action="{{ route('fassg.sle-fhe.reject', $profile) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-arrow-return-left me-1"></i>Request Fix
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection