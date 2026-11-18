@extends('layouts.app')

@section('title', 'SLE-FHE Verification')
@section('eyebrow', 'FASSG Office · SLE-FHE Verification')
@section('page-title', 'SLE-FHE Verification')
@section('subtitle', 'Verify student profiles awaiting secondary board eligibility confirmation.')

@section('header-actions')
    <a href="{{ route('fassg.sle-fhe.index') }}"
        class="sf-slefhe-stat-pill sf-slefhe-stat-pending text-decoration-none">
        <i class="bi bi-clock"></i> {{ $pendingSleFheCount ?? 0 }} Pending SLE-FHE Verification
    </a>
    <a href="{{ route('fassg.sle-fhe.verified') }}"
        class="sf-slefhe-stat-pill sf-slefhe-stat-verified text-decoration-none">
        <i class="bi bi-check-circle"></i> {{ $verifiedSleFheCount ?? 0 }} Verified SLE-FHE
    </a>
@endsection

@push('styles')
    @include('fassg.partials.slefhe-styles')
@endpush

@section('content')
    <div class="sf-slefhe-page">
        {{-- Search and Campus Filter Toolbar --}}
        <div class="card sf-slefhe-filter-card no-print">
            <form method="GET" action="{{ route('fassg.sle-fhe.index') }}" class="sf-slefhe-filter-row">
                {{-- Campus Filter --}}
                <div class="sf-slefhe-filter-campus">
                    <label class="sf-slefhe-filter-label" for="filterCampus">Campus</label>
                    <select id="filterCampus" name="campus" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All campuses</option>
                        @foreach (['Main Campus (City of Mati)', 'Baganga Campus', 'Banaybanay Campus', 'Cateel Campus', 'San Isidro Campus', 'Tarragona Campus'] as $campus)
                            <option value="{{ $campus }}" @selected(request('campus') === $campus)>{{ $campus }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Search Field --}}
                <div class="sf-slefhe-filter-search">
                    <label class="sf-slefhe-filter-label" for="filterSearch">Search</label>
                    <div class="sf-slefhe-search-group">
                        <i class="bi bi-search sf-slefhe-search-icon"></i>
                        <input type="text" id="filterSearch" name="q" value="{{ request('q') }}"
                            class="form-control form-control-sm sf-slefhe-search-input"
                            placeholder="Search by name, student ID, academic program…"
                            oninput="clearTimeout(window.searchTimer); window.searchTimer = setTimeout(() => this.form.submit(), 600)">
                    </div>
                </div>

                {{-- Reset Button --}}
                <div class="sf-slefhe-filter-reset">
                    <a href="{{ route('fassg.sle-fhe.index') }}" class="sf-slefhe-btn-reset text-decoration-none">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset Filters</span>
                    </a>
                </div>
            </form>
        </div>

        {{-- Pending Requests Table Card --}}
        @if ($pendingRequests->isEmpty())
            <div class="card sf-card sf-slefhe-table-card">
                <div class="sf-empty-state py-5">
                    <i class="bi bi-patch-check fs-1 text-muted mb-2 d-block"></i>
                    <div class="fw-semibold text-dark fs-6">No pending verification requests</div>
                    <div class="small text-secondary">No student verification requests match the current filters.</div>
                </div>
            </div>
        @else
            <div class="card sf-card sf-slefhe-table-card">
                <div class="sf-slefhe-table-header-row">
                    <h3 class="sf-slefhe-table-title">Pending Requests</h3>
                </div>
                <div class="table-responsive">
                    <table class="table sf-table sf-slefhe-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap">Student ID</th>
                                <th class="text-nowrap">Student Name</th>
                                <th>Academic Program</th>
                                <th>Year Level</th>
                                <th>Campus</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pendingRequests as $pendingRequest)
                                @php
                                    $profile = $pendingRequest->studentProfile;
                                    $registryMatch = $registryMatches[$profile->student_id_number] ?? null;
                                @endphp
                                <tr>
                                    {{-- Student ID --}}
                                    <td class="text-nowrap">
                                        <span class="sf-slefhe-student-id">{{ $profile->student_id_number ?: '—' }}</span>
                                    </td>

                                    {{-- Student Name --}}
                                    <td>
                                        <div class="sf-slefhe-student-name">
                                            {{ $profile->user->name ?? trim($profile->first_name . ' ' . ($profile->middle_name ?? '') . ' ' . $profile->last_name . ($profile->extension_name ? ' ' . $profile->extension_name : '')) }}
                                        </div>
                                        <div class="sf-slefhe-student-timestamp">
                                            Requested {{ optional($pendingRequest->submitted_at)->format('M d, Y h:i A') }}
                                        </div>
                                    </td>

                                    {{-- Academic Program --}}
                                    <td>
                                        <div class="sf-slefhe-course-name">{{ $profile->display_course }}</div>
                                    </td>

                                    {{-- Year Level --}}
                                    <td>
                                        @if ($profile->year_level)
                                            <span class="fw-medium text-dark">Year {{ $profile->year_level }}</span>
                                        @else
                                            <span class="text-muted">Year N/A</span>
                                        @endif
                                    </td>

                                    {{-- Campus --}}
                                    <td>
                                        <span class="sf-slefhe-campus-badge">
                                            {{ $profile->campus ?: 'Not Assigned' }}
                                        </span>
                                    </td>

                                    {{-- Status --}}
                                    <td class="text-nowrap">
                                        @if ($registryMatch)
                                            <div>
                                                <span class="sf-slefhe-registry-match-pill"
                                                    title="Matched in the master SLE-FHE registry ({{ $registryMatch->municipality ?: 'municipality not recorded' }}{{ $registryMatch->barangay ? ', ' . $registryMatch->barangay : '' }})">
                                                    <i class="bi bi-patch-check-fill me-1"></i>Master Registry Match Found
                                                </span>
                                            </div>
                                        @endif
                                        <x-status-badge :status="'Pending SLE-FHE Verification'" />
                                    </td>

                                    {{-- Actions --}}
                                    <td class="text-end">
                                        <div class="sf-slefhe-actions-group">
                                            <button type="button" class="sf-slefhe-btn-action sf-slefhe-btn-view"
                                                data-bs-toggle="modal"
                                                data-bs-target="#viewStudentModal-{{ $pendingRequest->id }}">
                                                <i class="bi bi-person-vcard me-1"></i>View Student Profile Details
                                            </button>
                                            <button type="button" class="sf-slefhe-btn-action sf-slefhe-btn-verify"
                                                data-bs-toggle="modal"
                                                data-bs-target="#verifyStudentModal-{{ $pendingRequest->id }}">
                                                <i class="bi bi-shield-check me-1"></i>Verify SLE-FHE Student
                                            </button>
                                            <button type="button" class="sf-slefhe-btn-action sf-slefhe-btn-reject"
                                                data-bs-toggle="modal"
                                                data-bs-target="#rejectStudentModal-{{ $pendingRequest->id }}">
                                                <i class="bi bi-x-circle me-1"></i>Reject
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Modals for each Pending Request --}}
            @foreach ($pendingRequests as $pendingRequest)
                @php
                    $profile = $pendingRequest->studentProfile;
                    $registryMatch = $registryMatches[$profile->student_id_number] ?? null;
                    $noMatchReason = 'Student ID not found in official SLE-FHE Master Registry';
                    $studentDisplayName = $profile->user->name ?? trim($profile->first_name . ' ' . ($profile->middle_name ?? '') . ' ' . $profile->last_name . ($profile->extension_name ? ' ' . $profile->extension_name : ''));
                @endphp

                {{-- 1. View Student Profile Details Modal --}}
                <div class="modal fade" id="viewStudentModal-{{ $pendingRequest->id }}" tabindex="-1"
                    aria-labelledby="viewStudentModalLabel-{{ $pendingRequest->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 740px;">
                        <div class="modal-content">
                            <div class="sf-slefhe-modal-header">
                                <div>
                                    <h5 class="sf-slefhe-modal-title" id="viewStudentModalLabel-{{ $pendingRequest->id }}">Student Profile Details</h5>
                                    <div class="sf-slefhe-modal-subtitle">
                                        <span class="sf-slefhe-student-id me-2">{{ $profile->student_id_number ?: 'No Student ID' }}</span>
                                        <span>{{ $studentDisplayName }}</span>
                                    </div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="sf-slefhe-modal-body">
                                {{-- Personal Information --}}
                                <div class="sf-slefhe-section-heading">Personal Information</div>
                                <div class="sf-slefhe-info-panel">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="sf-slefhe-info-label">Full Name</div>
                                            <div class="sf-slefhe-info-val">{{ $studentDisplayName }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="sf-slefhe-info-label">Student ID</div>
                                            <div class="sf-slefhe-info-val mono">{{ $profile->student_id_number ?: '—' }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="sf-slefhe-info-label">Academic Program</div>
                                            <div class="sf-slefhe-info-val">{{ $profile->display_course }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="sf-slefhe-info-label">Year Level</div>
                                            <div class="sf-slefhe-info-val">{{ $profile->year_level ? 'Year ' . $profile->year_level : 'N/A' }}</div>
                                        </div>
                                        <div class="col-12">
                                            <div class="sf-slefhe-info-label">Campus</div>
                                            <div class="sf-slefhe-info-val">{{ $profile->campus ?: 'Not Assigned' }}</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Submitted Address (Verification Request) --}}
                                <div class="sf-slefhe-section-heading">Submitted Address (Verification Request)</div>
                                <div class="sf-slefhe-info-panel mb-0">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="sf-slefhe-info-label">Province</div>
                                            <div class="sf-slefhe-info-val">{{ $pendingRequest->province ?: '—' }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="sf-slefhe-info-label">Municipality / City</div>
                                            <div class="sf-slefhe-info-val">{{ $pendingRequest->municipality_city ?: '—' }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="sf-slefhe-info-label">Barangay</div>
                                            <div class="sf-slefhe-info-val">{{ $pendingRequest->barangay ?: '—' }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="sf-slefhe-info-label">Street / Purok</div>
                                            <div class="sf-slefhe-info-val">{{ $pendingRequest->street_purok ?: '—' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="sf-slefhe-modal-footer">
                                <button type="button" class="btn btn-sf-navy px-4" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. Verify SLE-FHE Eligibility Modal --}}
                <div class="modal fade" id="verifyStudentModal-{{ $pendingRequest->id }}" tabindex="-1"
                    aria-labelledby="verifyStudentModalLabel-{{ $pendingRequest->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 740px;">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('fassg.sle-fhe.verify', $profile) }}">
                                @csrf
                                <input type="hidden" name="verification_request_id" value="{{ $pendingRequest->id }}">
                                <div class="sf-slefhe-modal-header">
                                    <div>
                                        <h5 class="sf-slefhe-modal-title" id="verifyStudentModalLabel-{{ $pendingRequest->id }}">Verify SLE-FHE Eligibility</h5>
                                        <div class="sf-slefhe-modal-subtitle">
                                            <span class="sf-slefhe-student-id me-2">{{ $profile->student_id_number ?: 'No Student ID' }}</span>
                                            <span>{{ $studentDisplayName }}</span>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="sf-slefhe-modal-body">
                                    {{-- Student Summary --}}
                                    <div class="sf-slefhe-info-panel">
                                        <dl class="row mb-0 small">
                                            <dt class="col-sm-4 text-secondary">Student Name:</dt>
                                            <dd class="col-sm-8 fw-semibold text-dark mb-2">{{ $profile->user->name ?? $profile->full_name ?: '—' }}</dd>

                                            <dt class="col-sm-4 text-secondary">Submitted Student ID:</dt>
                                            <dd class="col-sm-8 sf-mono fw-semibold text-dark mb-2">{{ $profile->student_id_number ?: '—' }}</dd>

                                            <dt class="col-sm-4 text-secondary">Submitted Address:</dt>
                                            <dd class="col-sm-8 text-dark mb-0">{{ $pendingRequest->full_address ?: '—' }}</dd>
                                        </dl>
                                    </div>

                                    <hr>

                                    {{-- Verification Confirmation Checkbox --}}
                                    <div class="form-check mb-3 p-2 bg-light rounded-3 border">
                                        <input class="form-check-input ms-1 me-2" type="checkbox" name="confirm_identity" value="1"
                                            id="identity-{{ $pendingRequest->id }}" required>
                                        <label class="form-check-label small fw-semibold text-dark" for="identity-{{ $pendingRequest->id }}">
                                            I have checked this student's identity, submitted address, and SLE-FHE eligibility against the evidence shown or referenced below.
                                        </label>
                                    </div>

                                    {{-- Master Registry Match Result --}}
                                    <div class="fw-semibold small mb-2 text-dark">Master Registry Match Result:</div>
                                    @if ($registryMatch)
                                        <div class="sf-slefhe-registry-box-match" role="alert">
                                            <div class="sf-slefhe-registry-match-badge">
                                                <i class="bi bi-patch-check-fill fs-6"></i> MATCH FOUND IN SLE-FHE REGISTRY
                                            </div>
                                            <ul class="list-unstyled small mb-0 mt-2">
                                                <li class="mb-1"><span class="text-secondary fw-semibold">Registry Campus:</span> {{ $registryMatch->campus ?: '—' }}</li>
                                                <li class="mb-1"><span class="text-secondary fw-semibold">Registry Course:</span> {{ $registryMatch->course ?: '—' }}</li>
                                                <li class="mb-0"><span class="text-secondary fw-semibold">Registry Address:</span> {{ $registryMatch->municipality ?: '—' }}</li>
                                            </ul>
                                        </div>
                                    @else
                                        <div class="sf-slefhe-registry-box-nomatch" role="alert">
                                            <div class="sf-slefhe-registry-nomatch-badge">
                                                <i class="bi bi-exclamation-triangle-fill fs-6"></i> NO MATCH FOUND IN SLE-FHE REGISTRY
                                            </div>
                                            <p class="sf-slefhe-registry-nomatch-desc">
                                                {{ $profile->student_id_number ?: 'This student ID' }} does not match any of the
                                                {{ number_format($registryTotalCount) }} records in the official master list.
                                                This is most likely a data-entry error, so returning the request for correction is usually the right action.
                                            </p>
                                        </div>

                                        {{-- Manual Override Section --}}
                                        <div class="sf-slefhe-override-card">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="override_registry_mismatch" value="1"
                                                    id="override-{{ $pendingRequest->id }}"
                                                    onchange="this.form.querySelector('[data-confirm-verify]').disabled = !this.checked;">
                                                <label class="form-check-label" for="override-{{ $pendingRequest->id }}">
                                                    Manual override: I have confirmed this student's eligibility outside the master registry and wish to verify anyway.
                                                </label>
                                            </div>

                                            <label class="form-label" for="override-reason-{{ $pendingRequest->id }}">Reason for registry mismatch override</label>
                                            <textarea class="form-control" name="override_reason" id="override-reason-{{ $pendingRequest->id }}"
                                                rows="2" required minlength="10" maxlength="1000">{{ old('override_reason') }}</textarea>

                                            <label class="form-label" for="evidence-{{ $pendingRequest->id }}">Official evidence reference</label>
                                            <input class="form-control" name="evidence_reference" id="evidence-{{ $pendingRequest->id }}"
                                                required minlength="5" maxlength="500" value="{{ old('evidence_reference') }}"
                                                placeholder="Office record, document reference, and issuing authority">

                                            <div class="sf-slefhe-override-helper">The reason, reference, officer, and decision time are retained in the verification audit record.</div>

                                            <div class="sf-slefhe-prefill-box">
                                                <strong>Pre-filled rejection reason:</strong> {{ $noMatchReason }}
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="sf-slefhe-modal-footer">
                                    <button type="button" class="btn sf-slefhe-btn-modal-cancel"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn sf-slefhe-btn-modal-reject"
                                        data-bs-toggle="modal"
                                        data-bs-target="#rejectStudentModal-{{ $pendingRequest->id }}">
                                        <i class="bi bi-x-circle me-1"></i> Reject Verification Request
                                    </button>
                                    <button type="submit" class="btn btn-sf-navy" data-confirm-verify
                                        @if (! $registryMatch) disabled @endif>
                                        <i class="bi bi-shield-check me-1"></i> Confirm Verification
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- 3. Reject Verification Request Modal --}}
                <div class="modal fade" id="rejectStudentModal-{{ $pendingRequest->id }}" tabindex="-1"
                    aria-labelledby="rejectStudentModalLabel-{{ $pendingRequest->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" style="max-width: 580px;">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('fassg.sle-fhe.reject', $profile) }}">
                                @csrf
                                <div class="sf-slefhe-modal-header">
                                    <div>
                                        <h5 class="sf-slefhe-modal-title" id="rejectStudentModalLabel-{{ $pendingRequest->id }}">Reject Verification Request</h5>
                                        <div class="sf-slefhe-modal-subtitle">
                                            <span class="sf-slefhe-student-id me-2">{{ $profile->student_id_number ?: 'No Student ID' }}</span>
                                            <span>{{ $studentDisplayName }}</span>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="sf-slefhe-modal-body">
                                    <label for="reason-{{ $pendingRequest->id }}" class="form-label fw-semibold text-dark mb-2">Rejection Reason</label>
                                    <textarea name="reason" id="reason-{{ $pendingRequest->id }}" class="form-control" rows="4"
                                        required placeholder="e.g. Student ID does not match the SLE-FHE masterlist">@if (! $registryMatch){{ $noMatchReason }}@endif</textarea>
                                    @unless ($registryMatch)
                                        <div class="form-text mt-2 text-muted small">
                                            Pre-filled because <strong>{{ $profile->student_id_number ?: 'this student ID' }}</strong>
                                            has no match in the SLE-FHE Master Registry. Edit the text as needed before confirming.
                                        </div>
                                    @endunless
                                </div>
                                <div class="sf-slefhe-modal-footer">
                                    <button type="button" class="btn sf-slefhe-btn-modal-cancel"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-outline-danger">
                                        <i class="bi bi-x-circle me-1"></i> Confirm Rejection
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@endsection
