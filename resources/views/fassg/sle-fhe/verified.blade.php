@extends('layouts.app')

@section('title', 'Verified SLE-FHE Students')
@section('eyebrow', 'FASSG Office · SLE-FHE Verification')
@section('page-title', 'Verified SLE-FHE Students')
@section('subtitle', 'Masterlist of student profiles confirmed for secondary board eligibility.')

@section('header-actions')
    <a href="{{ route('fassg.sle-fhe.verified') }}"
        class="sf-verified-stat-pill sf-verified-stat-verified text-decoration-none">
        <i class="bi bi-check-circle"></i>
        <span>{{ $verifiedCount ?? 0 }} Verified SLE-FHE</span>
    </a>
    <a href="{{ route('fassg.sle-fhe.index') }}"
        class="sf-verified-stat-pill sf-verified-stat-pending text-decoration-none">
        <i class="bi bi-clock-history"></i>
        <span>{{ $pendingSleFheCount ?? 0 }} Pending SLE-FHE Verification</span>
    </a>
@endsection

@push('styles')
    @include('fassg.partials.verified-styles')
@endpush

@section('content')
    <div class="sf-verified-page">
        {{-- Filter Bar --}}
        <div class="sf-verified-filter-card">
            <form method="GET" action="{{ route('fassg.sle-fhe.verified') }}" class="sf-verified-filter-row">
                {{-- Campus --}}
                <div class="sf-verified-filter-campus">
                    <label class="sf-verified-filter-label" for="filter-campus">Campus</label>
                    <select id="filter-campus" name="campus" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All campuses</option>
                        @foreach (['Main Campus (City of Mati)', 'Baganga Campus', 'Banaybanay Campus', 'Cateel Campus', 'San Isidro Campus', 'Tarragona Campus'] as $campusOpt)
                            <option value="{{ $campusOpt }}" @selected(request('campus') === $campusOpt)>{{ $campusOpt }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Search --}}
                <div class="sf-verified-filter-search">
                    <label class="sf-verified-filter-label" for="filter-search">Search</label>
                    <div class="sf-verified-search-group">
                        <i class="bi bi-search sf-verified-search-icon"></i>
                        <input type="text" id="filter-search" name="q" value="{{ request('q') }}"
                            class="form-control form-control-sm sf-verified-search-input"
                            placeholder="Name, student ID, academic program…"
                            oninput="clearTimeout(window.searchTimer); window.searchTimer = setTimeout(() => this.form.submit(), 600)">
                    </div>
                </div>

                {{-- Reset --}}
                <div class="sf-verified-filter-reset">
                    <a href="{{ route('fassg.sle-fhe.verified') }}" class="sf-verified-btn-reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset Filters</span>
                    </a>
                </div>
            </form>
        </div>

        {{-- Masterlist Table --}}
        @if ($verifiedVerifications->isEmpty())
            <div class="sf-verified-table-card">
                <div class="sf-verified-empty-state">
                    <div class="sf-verified-empty-icon">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <h5 class="sf-verified-empty-title">No verified students</h5>
                    <p class="sf-verified-empty-desc">No verified student profiles match the current filter criteria.</p>
                </div>
            </div>
        @else
            <div class="sf-verified-table-card">
                <div class="sf-verified-table-header-row">
                    <h2 class="sf-verified-table-title mb-0">
                        {{ $verifiedVerifications->count() }} {{ \Illuminate\Support\Str::plural('verified student', $verifiedVerifications->count()) }}
                    </h2>
                </div>
                <div class="sf-verified-table-container table-responsive">
                    <table class="table sf-verified-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="sf-col-student-id">Student ID</th>
                                <th class="sf-col-student-name">Student Name</th>
                                <th class="sf-col-academic-program">Academic Program</th>
                                <th class="sf-col-year-level">Year Level</th>
                                <th class="sf-col-campus">Campus</th>
                                <th class="sf-col-address">Verified Address</th>
                                <th class="sf-col-verified-at">Verified At</th>
                                <th class="sf-col-status">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($verifiedVerifications as $verification)
                                @php $profile = $verification->studentProfile; @endphp
                                <tr>
                                    {{-- Student ID --}}
                                    <td class="sf-col-student-id">
                                        <span class="sf-verified-student-id">{{ $profile->student_id_number ?: '—' }}</span>
                                    </td>

                                    {{-- Student Name --}}
                                    <td class="sf-col-student-name">
                                        <div class="sf-verified-student-name">
                                            {{ $profile->user->name ?? trim($profile->first_name . ' ' . ($profile->middle_name ?? '') . ' ' . $profile->last_name . ($profile->extension_name ? ' ' . $profile->extension_name : '')) }}
                                        </div>
                                    </td>

                                    {{-- Academic Program --}}
                                    <td class="sf-col-academic-program">
                                        <div class="sf-verified-program-name">{{ $profile->display_course }}</div>
                                    </td>

                                    {{-- Year Level --}}
                                    <td class="sf-col-year-level">
                                        @if ($profile->year_level)
                                            <span class="sf-verified-year-badge">Year {{ $profile->year_level }}</span>
                                        @else
                                            <span class="sf-verified-year-na">Year N/A</span>
                                        @endif
                                    </td>

                                    {{-- Campus --}}
                                    <td class="sf-col-campus">
                                        <span class="sf-verified-campus-badge">
                                            <i class="bi bi-geo-alt"></i>
                                            <span>{{ $profile->campus ?: 'Not Assigned' }}</span>
                                        </span>
                                    </td>

                                    {{-- Verified Address --}}
                                    <td class="sf-col-address">
                                        <div class="sf-verified-address-text">{{ $verification->verified_address ?: '—' }}</div>
                                    </td>

                                    {{-- Verified At --}}
                                    <td class="sf-col-verified-at">
                                        <div class="sf-verified-date">
                                            {{ optional($verification->verified_at)->format('M d, Y h:i A') ?: '—' }}
                                        </div>
                                        @if ($verification->verifiedBy)
                                            <div class="sf-verified-by">
                                                <span class="sf-verified-by-label">by</span> {{ $verification->verifiedBy->name }}
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Status --}}
                                    <td class="sf-col-status">
                                        <span class="sf-verified-status-badge">
                                            <i class="bi bi-patch-check-fill"></i>
                                            <span>Verified SLE-FHE</span>
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
