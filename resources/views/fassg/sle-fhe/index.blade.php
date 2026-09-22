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
                            <th>Residency</th>
                            <th class="text-nowrap">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendingRequests as $pendingRequest)
                            @php $profile = $pendingRequest->studentProfile; @endphp
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
                                <td class="text-nowrap">
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
                                        <form method="POST" action="{{ route('fassg.sle-fhe.verify', $profile) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-sf-navy">
                                                <i class="bi bi-check2-circle me-1"></i>Verify SLE-FHE Student
                                            </button>
                                        </form>
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
            @php $profile = $pendingRequest->studentProfile; @endphp

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
                                                <div class="col-md-6">
                                                    <div class="small text-secondary">Residency</div>
                                                    <div class="fw-semibold">{{ $profile->is_rural ? 'Rural' : 'Urban' }}</div>
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
                                                    required placeholder="e.g. Student ID does not match the SLE-FHE masterlist"></textarea>
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