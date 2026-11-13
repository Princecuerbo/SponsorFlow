@extends('layouts.app')

@section('title', 'SLE-FHE Verification')
@section('eyebrow', 'FASSG Office · SLE-FHE Verification')
@section('page-title', 'SLE-FHE Verification')
@section('subtitle', 'Verify student profiles awaiting secondary board eligibility confirmation.')

@section('header-actions')
    <a href="{{ route('fassg.sle-fhe.index') }}" class="stat-pill bg-warning bg-opacity-10 text-dark border border-warning-subtle text-decoration-none">
        <i class="bi bi-hourglass-split"></i> {{ $pendingSleFheCount ?? 0 }} Pending SLE-FHE Verification
    </a>
    <a href="{{ route('fassg.sle-fhe.verified') }}" class="stat-pill bg-success bg-opacity-10 text-success border border-success-subtle text-decoration-none">
        <i class="bi bi-patch-check"></i> {{ $verifiedSleFheCount ?? 0 }} Verified SLE-FHE
    </a>
@endsection

@push('styles')
    <style>

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
                <div class="col-md-7">
                    <label class="form-label small text-secondary fw-semibold mb-1">Search</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-secondary"></i>
                        </span>
                        <input type="text" name="q" value="{{ request('q') }}"
                            class="form-control border-start-0 ps-0"
                            placeholder="Name, student ID, academic program…"
                            oninput="clearTimeout(window.searchTimer); window.searchTimer = setTimeout(() => this.form.submit(), 600)">
                    </div>
                </div>

                {{-- Reset --}}
                <div class="col-md-2">
                    <a href="{{ route('fassg.sle-fhe.index') }}"
                        class="btn btn-outline-secondary btn-sm w-100 d-flex align-items-center justify-content-center gap-1">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Queue Table --}}
    @if ($pendingRequests->isEmpty())
        <div class="card sf-card">
            <div class="sf-empty-state">
                <i class="bi bi-patch-check"></i>
                <div class="fw-semibold">No pending verification requests</div>
                <div class="small">No student verification requests match the current filters.</div>
            </div>
        </div>
    @else
        <div class="card sf-card">
            <div class="table-responsive">
                <table class="table sf-table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4 text-nowrap">Student ID</th>
                            <th class="text-nowrap">Student Name</th>
                            <th>Academic Program</th>
                            <th>Year Level</th>
                            <th>Campus</th>
                            <th class="text-nowrap">Status</th>
                            <th class="text-end pe-4">Actions</th>
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
                                <td class="ps-4 text-nowrap">
                                    <span class="small text-secondary sf-mono fw-semibold">{{ $profile->student_id_number ?: '—' }}</span>
                                </td>

                                {{-- Student Name --}}
                                <td>
                                    <div class="fw-semibold">{{ $profile->user->name ?? trim($profile->first_name . ' ' . ($profile->middle_name ?? '') . ' ' . $profile->last_name . ($profile->extension_name ? ' ' . $profile->extension_name : '')) }}</div>
                                    <div class="small text-secondary">Requested {{ optional($pendingRequest->submitted_at)->format('M d, Y h:i A') }}</div>
                                </td>

                                {{-- Academic Program --}}
                                <td>
                                    <div>{{ $profile->display_course }}</div>
                                </td>

                                {{-- Year Level --}}
                                <td>
                                    @if ($profile->year_level)
                                        <span>Year {{ $profile->year_level }}</span>
                                    @else
                                        <span class="text-muted">Year N/A</span>
                                    @endif
                                </td>

                                {{-- Campus --}}
                                <td>
                                    <span class="badge rounded-2 fw-medium"
                                        style="background-color: rgba(15,41,66,0.08); color:#0F2942;">
                                        {{ $profile->campus ?: 'Not Assigned' }}
                                    </span>
                                </td>

                                {{-- Status --}}
                                <td class="text-nowrap">
                                    @if ($registryMatch)
                                        <span class="badge rounded-2 fw-semibold mb-1 d-inline-block"
                                            style="background-color: rgba(13,148,136,0.12); color:#0f766e;"
                                            title="Matched in the master SLE-FHE registry ({{ $registryMatch->municipality ?: 'municipality not recorded' }}{{ $registryMatch->barangay ? ', ' . $registryMatch->barangay : '' }})">
                                            <i class="bi bi-patch-check-fill me-1"></i>Master Registry Match Found
                                        </span>
                                    @endif
                                    <x-status-badge :status="'Pending SLE-FHE Verification'" />
                                </td>

                                {{-- Actions --}}
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-sf-navy"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewStudentModal-{{ $pendingRequest->id }}">
                                            <i class="bi bi-person-vcard me-1"></i>View Student Profile Details
                                        </button>
                                        <button type="button" class="btn btn-sm btn-sf-navy"
                                            data-bs-toggle="modal"
                                            data-bs-target="#verifyStudentModal-{{ $pendingRequest->id }}">
                                            <i class="bi bi-check2-circle me-1"></i>Verify SLE-FHE Student
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
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

        @foreach ($pendingRequests as $pendingRequest)
            @php
                $profile = $pendingRequest->studentProfile;
                $registryMatch = $registryMatches[$profile->student_id_number] ?? null;
                $noMatchReason = 'Student ID not found in official SLE-FHE Master Registry';
            @endphp

            {{-- Verify Eligibility Modal --}}
            <div class="modal fade" id="verifyStudentModal-{{ $pendingRequest->id }}" tabindex="-1"
                aria-labelledby="verifyStudentModalLabel-{{ $pendingRequest->id }}" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content border-0 shadow rounded-4">
                        <form method="POST" action="{{ route('fassg.sle-fhe.verify', $profile) }}">
                            @csrf
                            <input type="hidden" name="verification_request_id" value="{{ $pendingRequest->id }}">
                            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                                <div>
                                    <h5 class="fw-bold text-dark mb-0" id="verifyStudentModalLabel-{{ $pendingRequest->id }}">Verify SLE-FHE Eligibility</h5>
                                    <span class="small text-secondary">{{ $profile->student_id_number ?: 'No student ID' }}</span>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body px-4 py-3">
                                {{-- Applicant Details --}}
                                <dl class="row mb-0 small">
                                    <dt class="col-sm-3 text-secondary">Student Name:</dt>
                                    <dd class="col-sm-9 fw-semibold">{{ $profile->user->name ?? $profile->full_name ?: '—' }}</dd>
                                    <dt class="col-sm-3 text-secondary">Submitted Student ID:</dt>
                                    <dd class="col-sm-9 sf-mono">{{ $profile->student_id_number ?: '—' }}</dd>
                                    <dt class="col-sm-3 text-secondary">Submitted Address:</dt>
                                    <dd class="col-sm-9">{{ $pendingRequest->full_address ?: '—' }}</dd>
                                </dl>

                                <hr>

                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="confirm_identity" value="1"
                                        id="identity-{{ $pendingRequest->id }}" required>
                                    <label class="form-check-label small" for="identity-{{ $pendingRequest->id }}">
                                        I have checked this student's identity, submitted address, and SLE-FHE eligibility against the evidence shown or referenced below.
                                    </label>
                                </div>

                                {{-- Master Registry Match Result --}}
                                <div class="fw-semibold small mb-2">Master Registry Match Result:</div>
                                @if ($registryMatch)
                                    <div class="alert alert-success py-2 px-3 mb-2" role="alert">
                                        <span class="badge rounded-2 fw-semibold" style="background-color:#0f766e; color:#fff;">
                                            &check; MATCH FOUND IN SLE-FHE REGISTRY
                                        </span>
                                    </div>
                                    <ul class="list-unstyled small mb-0">
                                        <li class="mb-1"><span class="text-secondary">Registry Campus:</span> {{ $registryMatch->campus ?: '—' }}</li>
                                        <li class="mb-1"><span class="text-secondary">Registry Course:</span> {{ $registryMatch->course ?: '—' }}</li>
                                        <li class="mb-0"><span class="text-secondary">Registry Address:</span> {{ $registryMatch->municipality ?: '—' }}</li>
                                    </ul>
                                @else
                                    <div class="alert alert-warning py-2 px-3 mb-2" role="alert">
                                        <span class="badge rounded-2 fw-semibold" style="background-color:#b45309; color:#fff;">
                                            &#9888; NO MATCH FOUND IN SLE-FHE REGISTRY
                                        </span>
                                    </div>
                                    <p class="small mb-2">
                                        {{ $profile->student_id_number ?: 'This student ID' }} does not match any of the
                                        {{ number_format($registryTotalCount) }} records in the official master list.
                                        This is most likely a data-entry error, so returning the request for correction is
                                        usually the right action.
                                    </p>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="override_registry_mismatch" value="1"
                                            id="override-{{ $pendingRequest->id }}"
                                            onchange="this.form.querySelector('[data-confirm-verify]').disabled = !this.checked;">
                                        <label class="form-check-label small" for="override-{{ $pendingRequest->id }}">
                                            Manual override: I have confirmed this student's eligibility outside the master registry and wish to verify anyway.
                                        </label>
                                    </div>
                                    <label class="form-label small" for="override-reason-{{ $pendingRequest->id }}">Reason for registry mismatch override</label>
                                    <textarea class="form-control mb-2" name="override_reason" id="override-reason-{{ $pendingRequest->id }}"
                                        required minlength="10" maxlength="1000">{{ old('override_reason') }}</textarea>
                                    <label class="form-label small" for="evidence-{{ $pendingRequest->id }}">Official evidence reference</label>
                                    <input class="form-control mb-2" name="evidence_reference" id="evidence-{{ $pendingRequest->id }}"
                                        required minlength="5" maxlength="500" value="{{ old('evidence_reference') }}"
                                        placeholder="Office record, document reference, and issuing authority">
                                    <div class="form-text mb-2">The reason, reference, officer, and decision time are retained in the verification audit record.</div>
                                    <div class="alert alert-light border small mb-0">
                                        <strong>Pre-filled rejection reason:</strong> {{ $noMatchReason }}
                                    </div>
                                @endif
                            </div>
                            <div class="modal-footer border-top-0 px-4 pb-4 pt-0">
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                    data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#rejectStudentModal-{{ $pendingRequest->id }}">
                                    &#10005; Reject Application
                                </button>
                                <button type="submit" class="btn btn-sm btn-sf-navy" data-confirm-verify
                                    @if (! $registryMatch) disabled @endif>
                                    &#10003; Confirm Verification
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- View Student Profile Details Modal --}}
                            <div class="modal fade" id="viewStudentModal-{{ $pendingRequest->id }}" tabindex="-1"
                                aria-labelledby="viewStudentModalLabel-{{ $pendingRequest->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                    <div class="modal-content border-0 shadow rounded-4">
                                        <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-3 d-flex align-items-center justify-content-center"
                                                    style="width: 42px; height: 42px; background-color:#ECFEFF; color:#0e7490;">
                                                    <i class="bi bi-person-vcard fs-5"></i>
                                                </div>
                                                <div>
                                                    <h5 class="fw-bold text-dark mb-0" id="viewStudentModalLabel-{{ $pendingRequest->id }}">Student Profile Details</h5>
                                                    <span class="small text-secondary">{{ $profile->student_id_number ?: 'No student ID' }}</span>
                                                </div>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body px-4 py-3">
                                            {{-- Personal Information --}}
                                            <h6 class="small text-uppercase text-secondary fw-bold mb-2" style="letter-spacing:0.05em;">Personal Information</h6>
                                            <div class="row g-3 bg-light rounded-3 p-3 mb-4">
                                                <div class="col-md-6">
                                                    <div class="small text-secondary">Full Name</div>
                                                    <div class="fw-semibold">@if ($profile->user) {{ $profile->user->name }} @else {{ trim($profile->first_name . ' ' . ($profile->middle_name ?? '') . ' ' . $profile->last_name . ($profile->extension_name ? ' ' . $profile->extension_name : '')) }} @endif</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="small text-secondary">Student ID</div>
                                                    <div class="fw-semibold sf-mono">{{ $profile->student_id_number ?: '—' }}</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="small text-secondary">Academic Program</div>
                                                    <div class="fw-semibold">{{ $profile->display_course }}</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="small text-secondary">Year Level</div>
                                                    <div class="fw-semibold">{{ $profile->year_level ? 'Year ' . $profile->year_level : 'N/A' }}</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="small text-secondary">Campus</div>
                                                    <div class="fw-semibold">{{ $profile->campus ?: 'Not Assigned' }}</div>
                                                </div>
                                            </div>

                                            {{-- Submitted Address --}}
                                            <h6 class="small text-uppercase text-secondary fw-bold mb-2" style="letter-spacing:0.05em;">Submitted Address (Verification Request)</h6>
                                            <div class="row g-3 bg-light rounded-3 p-3">
                                                <div class="col-md-6">
                                                    <div class="small text-secondary">Province</div>
                                                    <div class="fw-semibold">{{ $pendingRequest->province ?: '—' }}</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="small text-secondary">Municipality / City</div>
                                                    <div class="fw-semibold">{{ $pendingRequest->municipality_city ?: '—' }}</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="small text-secondary">Barangay</div>
                                                    <div class="fw-semibold">{{ $pendingRequest->barangay ?: '—' }}</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="small text-secondary">Street / Purok</div>
                                                    <div class="fw-semibold">{{ $pendingRequest->street_purok ?: '—' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-top-0 px-4 pb-4 pt-0">
                                            <button type="button" class="btn btn-sm fw-semibold text-white px-3" style="background-color:#0f294a;"
                                                data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Reject Verification Request Modal --}}
                            <div class="modal fade" id="rejectStudentModal-{{ $pendingRequest->id }}" tabindex="-1"
                                aria-labelledby="rejectStudentModalLabel-{{ $pendingRequest->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow rounded-4">
                                        <form method="POST" action="{{ route('fassg.sle-fhe.reject', $profile) }}">
                                            @csrf
                                            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                                                <div>
                                                    <h5 class="fw-bold text-dark mb-0" id="rejectStudentModalLabel-{{ $pendingRequest->id }}">Reject Verification Request</h5>
                                                    <span class="small text-secondary">{{ $profile->user->name ?? $profile->student_id_number }}</span>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body px-4 py-3">
                                                <label for="reason-{{ $pendingRequest->id }}" class="form-label fw-semibold">Rejection Reason</label>
                                                <textarea name="reason" id="reason-{{ $pendingRequest->id }}" class="form-control" rows="3"
                                                    required placeholder="e.g. Student ID does not match the SLE-FHE masterlist"
                                                    @if (! $registryMatch) value="{{ $noMatchReason }}" @endif
                                                ></textarea>
                                                @unless ($registryMatch)
                                                    <div class="form-text">
                                                        Pre-filled because <strong>{{ $profile->student_id_number ?: 'this student ID' }}</strong>
                                                        has no match in the SLE-FHE Master Registry. Edit the text as needed before confirming.
                                                    </div>
                                                @endunless
                                            </div>
                                            <div class="modal-footer border-top-0 px-4 pb-4 pt-0">
                                                <button type="button" class="btn btn-sm fw-semibold" style="background-color:#eef2f6; color:#0F2942;"
                                                    data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-x-circle me-1"></i>Confirm Rejection
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
    @endif
@endsection
