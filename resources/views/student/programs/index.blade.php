@extends('layouts.app')

@section('title', 'Browse Programs')
@section('eyebrow', 'Student Portal')
@section('page-title', 'Open Sponsorship Programs')

@push('styles')
    <style>
        /* Primary Navy Styling for Apply Now Button */
        .btn-navy-primary,
        a.btn-navy-primary {
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
            color: #ffffff !important;
            box-shadow: none !important;
        }

        .btn-navy-primary:hover,
        .btn-navy-primary:focus,
        a.btn-navy-primary:hover,
        a.btn-navy-primary:focus {
            background-color: #0A1E31 !important;
            border-color: #0A1E31 !important;
            color: #ffffff !important;
        }
    </style>
@endpush

@section('content')
    @php
        $isVerified = (bool) ($profile?->is_sle_fhe_verified ?? false);
    @endphp

    <h4 class="fw-bold text-dark mb-1">Sponsorship Opportunities</h4>
    <p class="text-muted small mb-3">Browse and apply for available university sponsorship programs.</p>

    @if ($isVerified)
        <form method="GET" action="{{ route('student.programs.index') }}" id="filter-form" class="card sf-card mb-4">
            <div class="card-body p-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i
                                    class="bi bi-search text-secondary"></i></span>
                            <input type="text" name="q" value="{{ request('q') }}"
                                class="form-control border-start-0 ps-0" placeholder="Search program or sponsor name…"
                                oninput="clearTimeout(window.searchTimer); window.searchTimer = setTimeout(() => this.form.submit(), 600)">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="category" onchange="this.form.submit()" class="form-select">
                            <option value="">All Categories</option>
                            @foreach (['Group', 'Individual', 'Employee-Based'] as $cat)
                                <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('student.programs.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
                    </div>
                </div>
            </div>
        </form>
    @endif

    @if ($programs->isEmpty())
        @if (!$isVerified)
            {{-- Clean Empty State Container --}}
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-4">
                <div class="mb-3">
                    <i class="bi bi-lock-fill text-secondary display-4"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Sponsorship Opportunities Locked</h5>
                <p class="text-secondary small mb-4 mx-auto" style="max-width: 480px;">
                    Programs will become available after FASSG verifies your Student ID against the institutional
                    masterlist.
                </p>
                <div>
                    <a href="{{ route('student.sle-fhe') }}" class="btn btn-navy-primary px-4 py-2 fw-semibold rounded-3">
                        Check Verification Status
                    </a>
                </div>
            </div>
        @else
            <div class="card sf-card">
                <div class="sf-empty-state">
                    <i class="bi bi-inbox"></i>
                    <div class="fw-semibold">No open programs match your search</div>
                    <div class="small">Try clearing filters, or check back later — new programs open regularly.</div>
                </div>
            </div>
        @endif
    @else
        <div class="row g-4">
            @foreach ($programs as $program)
                @include('student.programs._program_card', [
                    'program' => $program,
                    'profile' => $profile,
                    'hasActiveSponsorship' => $hasActiveSponsorship,
                ])
            @endforeach
        </div>
    @endif
@endsection
