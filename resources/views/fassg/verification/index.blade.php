@extends('layouts.app')

@section('title', 'Verification Queue')
@section('eyebrow', 'FASSG Office')
@section('page-title', 'Hybrid Verification Queue')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="text-uppercase small fw-semibold text-success mb-2">Student eligibility</p>
            <h1 class="display-6 fw-bold mb-1">Hybrid Verification Queue</h1>
            <p class="text-secondary mb-0">Review unverified student profiles and pending application submissions.</p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge text-bg-warning text-dark px-3 py-2">{{ $pendingStudents }} student profiles</span>
            <span class="badge text-bg-primary px-3 py-2">{{ $pendingApplications }} applications</span>
        </div>
    </div>

    <div class="card sf-card mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('fassg.verification.index') }}" class="row g-2">
                <div class="col-md-3">
                    <select name="academic_program_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All academic programs</option>
                        @foreach ($academicPrograms as $academicProgram)
                            <option value="{{ $academicProgram->program_id }}" @selected((int) request('academic_program_id') === (int) $academicProgram->program_id)>{{ $academicProgram->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-7">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control border-start-0 ps-0" placeholder="Search by name, student ID, or course" oninput="clearTimeout(window.searchTimer); window.searchTimer = setTimeout(() => this.form.submit(), 600)">
                    </div>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('fassg.verification.index') }}" class="btn btn-outline-secondary w-100">Reset Search</a>
                </div>
            </form>
        </div>
    </div>

    @if ($verificationItems->isEmpty())
        <div class="card sf-card">
            <div class="sf-empty-state">
                <i class="bi bi-inboxes"></i>
                <div class="fw-semibold">Verification queue is empty</div>
                <div class="small">Nothing needs review right now.</div>
            </div>
        </div>
    @else
        <div class="card sf-card">
            <div class="table-responsive">
                <table class="table sf-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Student ID</th>
                            <th>Full Name</th>
                            <th>Course &amp; Year</th>
                            <th>Barangay &amp; Rurality</th>
                            <th>Uploaded Proofs</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($verificationItems as $item)
                            @php($profile = $item['profile'])
                            @php($application = $item['application'])
                            <tr>
                                <td class="ps-4 sf-mono">{{ $profile->student_id_number ?: 'Not provided' }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $profile->user->name }}</div>
                                    <div class="small text-secondary">{{ $profile->user->email }}</div>
                                </td>
                                <td>{{ $profile->course }}<div class="small text-secondary">Year {{ $profile->year_level }}</div></td>
                                <td>
                                    <div>{{ $profile->barangay ?: 'Not provided' }}</div>
                                    @if ($profile->is_rural)
                                        <span class="badge bg-info-subtle text-info-emphasis">Rural</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis">Urban</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($application)
                                        <span class="badge bg-success-subtle text-success-emphasis">{{ $application->documents->count() }} document(s)</span>
                                    @else
                                        <span class="text-secondary small">Profile registration</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($item['type'] === 'student')
                                        <span class="badge text-bg-warning text-dark">Pending verification</span>
                                    @else
                                        <x-status-badge :status="$application->status" />
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @if ($item['type'] === 'student')
                                        <div class="d-flex justify-content-end gap-2">
                                            <form method="POST" action="{{ route('fassg.verification.students.verify', $profile) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check2-circle me-1"></i>Verify &amp; Approve SLE-FHE</button>
                                            </form>
                                            <form method="POST" action="{{ route('fassg.verification.students.reject', $profile) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-arrow-return-left me-1"></i>Request Fix</button>
                                            </form>
                                        </div>
                                    @else
                                        <a href="{{ route('fassg.verification.show', $application) }}" class="btn btn-sm btn-sf-navy">Review Application <i class="bi bi-chevron-right"></i></a>
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