@extends('layouts.app')

@section('title', 'Verified SLE-FHE Students')
@section('eyebrow', 'FASSG Office')
@section('page-title', 'Verified SLE-FHE Students')

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
    </style>
@endpush

@section('content')
    {{-- Page Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="text-uppercase small fw-semibold text-secondary mb-2">FASSG Office · SLE-FHE Verification</p>
            <h1 class="display-6 fw-bold mb-1">Verified SLE-FHE Students</h1>
            <p class="text-secondary mb-0">Masterlist of student profiles confirmed for secondary board eligibility.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('fassg.sle-fhe.verified') }}" class="stat-pill bg-success bg-opacity-10 text-success border border-success-subtle text-decoration-none">
                <i class="bi bi-patch-check"></i> {{ $verifiedCount ?? 0 }} Verified SLE-FHE
            </a>
            <a href="{{ route('fassg.sle-fhe.index') }}" class="stat-pill bg-warning bg-opacity-10 text-dark border border-warning-subtle text-decoration-none">
                <i class="bi bi-hourglass-split"></i> {{ $pendingSleFheCount ?? 0 }} Pending SLE-FHE Verification
            </a>
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
            <form method="GET" action="{{ route('fassg.sle-fhe.verified') }}" class="row g-2 align-items-end">
                {{-- Campus --}}
                <div class="col-md-3">
                    <label class="form-label small text-secondary fw-semibold mb-1">Campus</label>
                    <select name="campus" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All campuses</option>
                        @foreach (['Main Campus (City of Mati)', 'Baganga Campus', 'Banaybanay Campus', 'Cateel Campus', 'San Isidro Campus', 'Tarragona Campus'] as $campusOpt)
                            <option value="{{ $campusOpt }}" @selected(request('campus') === $campusOpt)>{{ $campusOpt }}</option>
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
                    <a href="{{ route('fassg.sle-fhe.verified') }}"
                        class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Masterlist Table --}}
    @if ($verifiedProfiles->isEmpty())
        <div class="card sf-card">
            <div class="sf-empty-state">
                <i class="bi bi-person-check"></i>
                <div class="fw-semibold">No verified students</div>
                <div class="small">No verified student profiles match the current filters.</div>
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
                            <th>Residency</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($verifiedProfiles as $profile)
                            <tr>
                                {{-- Student --}}
                                <td class="ps-4">
                                    <div class="fw-semibold">{{ $profile->user->name ?? trim($profile->first_name . ' ' . ($profile->middle_name ?? '') . ' ' . $profile->last_name . ($profile->extension_name ? ' ' . $profile->extension_name : '')) }}</div>
                                    <div class="small text-secondary sf-mono">{{ $profile->student_id_number ?: '—' }}</div>
                                </td>

                                {{-- Course & Year --}}
                                <td style="min-width:150px;">
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

                                {{-- Residency --}}
                                <td>
                                    @if ($profile->is_rural)
                                        <span class="badge rounded-2 fw-medium bg-success-subtle text-success-emphasis">
                                            <i class="bi bi-tree me-1"></i>Rural
                                        </span>
                                    @else
                                        <span class="badge rounded-2 fw-medium bg-secondary-subtle text-secondary-emphasis">
                                            <i class="bi bi-building me-1"></i>Urban
                                        </span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td>
                                    <span class="px-2.5 py-1 rounded-full bg-success bg-opacity-10 text-success border border-success-subtle d-inline-flex align-items-center"
                                        style="display: inline-flex; align-items: center; gap: 0.35rem; border-radius: 9999px; padding: 0.25rem 0.65rem; font-weight: 500; font-size: 0.8rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5" style="width: 0.875rem; height: 0.875rem; flex-shrink: 0;" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                        Verified SLE-FHE
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection