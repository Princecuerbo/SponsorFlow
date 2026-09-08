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
                    <a href="{{ route('student.programs.index') }}" class="btn btn-navy-primary">
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
