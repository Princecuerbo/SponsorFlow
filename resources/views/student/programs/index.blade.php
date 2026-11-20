@extends('layouts.app')

@section('title', 'Browse Programs')
@section('eyebrow', 'Student Portal · Programs')
@section('page-title', 'Sponsorship Opportunities')
@section('subtitle', 'Browse and apply for available university sponsorship programs.')

@push('styles')
    <style>
        /* ===== Standardized Programs Filter Toolbar ===== */
        .sf-programs-toolbar .sf-toolbar-input-group {
            height: 38px;
        }

        .sf-programs-toolbar .sf-toolbar-addon {
            height: 38px;
            border-radius: 8px 0 0 8px !important;
            border-color: #cbd5e1;
            padding-left: 0.85rem;
            padding-right: 0.5rem;
        }

        .sf-programs-toolbar .sf-toolbar-input {
            height: 38px;
            border-radius: 0 8px 8px 0 !important;
            border-color: #cbd5e1;
            font-size: 0.875rem;
            color: #0f172a;
        }

        .sf-programs-toolbar .sf-toolbar-input:focus,
        .sf-programs-toolbar .sf-toolbar-select:focus {
            border-color: #0F2942 !important;
            box-shadow: 0 0 0 3px rgba(15, 41, 66, 0.15) !important;
        }

        .sf-programs-toolbar .sf-toolbar-select {
            height: 38px;
            border-radius: 8px !important;
            border-color: #cbd5e1;
            font-size: 0.875rem;
            color: #334155;
            padding-left: 0.75rem;
        }

        .sf-programs-toolbar .sf-btn-reset-filters {
            height: 38px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 7px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            padding: 0 16px !important;
            border-radius: 8px !important;
            background-color: #FFFFFF !important;
            border: 1px solid #D5E0E8 !important;
            color: #173B57 !important;
            text-decoration: none !important;
            white-space: nowrap !important;
            transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease !important;
        }

        .sf-programs-toolbar .sf-btn-reset-filters i {
            font-size: 14px !important;
            line-height: 1 !important;
            color: #173B57 !important;
            transition: color 0.15s ease !important;
        }

        .sf-programs-toolbar .sf-btn-reset-filters:hover {
            background-color: #EEF3F7 !important;
            color: #0F2942 !important;
            border-color: #AFC3D3 !important;
        }

        .sf-programs-toolbar .sf-btn-reset-filters:hover i {
            color: #0F2942 !important;
        }

        .sf-programs-toolbar .sf-btn-reset-filters:focus-visible {
            outline: none !important;
            border-color: #173B57 !important;
            box-shadow: 0 0 0 3px rgba(23, 59, 87, 0.2) !important;
        }

        /* ===== Program Card Improvements ===== */
        .sf-program-title {
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.35;
            min-height: 2.7rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .sf-program-card-btn {
            height: 38px;
            border-radius: 8px !important;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            transition: all 0.2s ease-in-out;
        }

        .btn-sf-navy.sf-program-card-btn {
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
            color: #ffffff !important;
        }

        .btn-sf-navy.sf-program-card-btn:hover {
            background-color: #0A1E31 !important;
            border-color: #0A1E31 !important;
            color: #ffffff !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(15, 41, 66, 0.2) !important;
        }

        .btn-sf-navy.sf-program-card-btn:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(15, 41, 66, 0.25) !important;
        }
    </style>
@endpush

@section('content')
    @php
        $isVerified = ($profile?->sle_fhe_status ?? null) === 'Verified';
    @endphp

    @if ($isVerified)
        <form method="GET" action="{{ route('student.programs.index') }}" id="filter-form" class="card sf-card mb-4">
            <div class="card-body p-3">
                <div class="row g-3 align-items-center sf-programs-toolbar">
                    <div class="col-12 col-md-6">
                        <div class="input-group sf-toolbar-input-group">
                            <span class="input-group-text bg-white border-end-0 sf-toolbar-addon"><i
                                    class="bi bi-search text-secondary" aria-hidden="true"></i></span>
                            <input type="text" name="q" value="{{ request('q') }}"
                                class="form-control border-start-0 ps-0 sf-toolbar-input" placeholder="Search program or sponsor name…"
                                aria-label="Search program or sponsor name"
                                oninput="clearTimeout(window.searchTimer); window.searchTimer = setTimeout(() => this.form.submit(), 600)">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <select name="category" onchange="this.form.submit()" class="form-select sf-toolbar-select" aria-label="Filter by category">
                            <option value="">All Categories</option>
                            @foreach (['Group', 'Individual', 'Employee-Based'] as $cat)
                                <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <a href="{{ route('student.programs.index') }}" class="sf-btn-reset-filters w-100">
                            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                            <span>Reset Filters</span>
                        </a>
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
                <h3 class="h6 sf-heading mb-3">Sponsorship Opportunities Locked</h3>
                <p class="text-secondary small mb-4 mx-auto" style="max-width: 480px;">
                    Programs will become available after FASSG verifies your Student ID against the institutional
                    masterlist.
                </p>
                <div>
                    <a href="{{ route('student.sle-fhe') }}" class="btn btn-sf-navy px-4 py-2 fw-semibold rounded-3">
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

    <div class="modal fade" id="eligibilityModal" tabindex="-1" aria-labelledby="eligibilityModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-danger" id="eligibilityModalLabel">
                        <i class="bi bi-exclamation-octagon-fill me-2"></i>Ineligible for Sponsorship
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="small text-secondary mb-3">This program does not match your current student profile. Please
                        review the following requirements before applying:</p>
                    <ul id="eligibility-reasons-list" class="list-unstyled mb-4"></ul>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="{{ route('student.programs.index') }}" class="btn btn-sf-navy">
                        <i class="bi bi-grid-fill me-1"></i>View Other Opportunities
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            'use strict';

            const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (ch) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
            }[ch]));

            const reasonsList = document.getElementById('eligibility-reasons-list');

            document.addEventListener('click', function (event) {
                const button = event.target.closest('.apply-now-btn');
                if (!button) return;

                event.preventDefault();

                const applyUrl = button.dataset.applyUrl;
                const checkUrl = button.dataset.checkUrl;

                if (!checkUrl) {
                    window.location.href = applyUrl;
                    return;
                }

                fetch(checkUrl, { headers: { 'Accept': 'application/json' } })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.is_eligible) {
                            window.location.href = applyUrl;
                            return;
                        }

                        const reasons = Array.isArray(data.reasons) ? data.reasons : [];
                        reasonsList.innerHTML = reasons
                            .map((reason) => `
                                <li class="d-flex align-items-start gap-2 mb-2">
                                    <i class="bi bi-x-octagon-fill text-danger fs-6 mt-1"></i>
                                    <span class="small">${escapeHtml(reason)}</span>
                                </li>
                            `)
                            .join('');

                        const modalEl = document.getElementById('eligibilityModal');
                        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                        modal.show();
                    })
                    .catch(() => {
                        window.location.href = applyUrl;
                    });
            });
        })();
    </script>
@endpush
