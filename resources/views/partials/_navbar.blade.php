@php
    $user = auth()->user();
    $role = $user->role;
@endphp

<nav class="sf-navbar navbar bg-white border-bottom py-0 sticky-top" style="min-height:60px;">
    <div class="container-fluid px-3 d-flex align-items-center justify-content-between flex-nowrap">

        {{-- Brand --}}
        <a class="navbar-brand d-flex align-items-center gap-2 m-0 text-nowrap flex-shrink-0"
            href="{{ route($user->homeRoute()) }}">
            <div class="d-flex align-items-center justify-content-center rounded-3"
                style="background-color: #0f294a; width: 34px; height: 34px;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="16" height="16" fill="#ffffff">
                    <path d="M312 96c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 8c-30.9 0-56 25.1-56 56s25.1 56 56 56l32 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-48 0c-13.3 0-24-10.7-24-24c0-13.3-10.7-24-24-24s-24 10.7-24 24c0 30.9 25.1 56 56 56l0 8c0 13.3 10.7 24 24 24s24-10.7 24-24l0-8c30.9 0 56-25.1 56-56s-25.1-56-56-56l-32 0c-13.3 0-24-10.7-24-24s10.7-24 24-24l48 0c13.3 0 24 10.7 24 24c0 13.3 10.7 24 24 24s24-10.7 24-24c0-30.9-25.1-56-56-56l0-8zM0 384c0-35.3 28.7-64 64-64l119.7 0c15.6 0 30.3 5.7 41.7 16l103.8 93.4c22.1 19.9 51.1 30.6 80.8 30.6l102 0c35.3 0 64-28.7 64-64s-28.7-64-64-64l-96 0c-13.3 0-24-10.7-24-24s10.7-24 24-24l96 0c61.9 0 112 50.1 112 112s-50.1 112-112 112l-102 0c-42.5 0-83.9-15.3-115.5-43.7L186.3 368 64 368c-8.8 0-16 7.2-16 16s7.2 16 16 16l112 0c13.3 0 24 10.7 24 24s-10.7 24-24 24L64 448c-35.3 0-64-28.7-64-64z"/>
                </svg>
            </div>
            <span class="fw-bold tracking-tight" style="font-size: 1.1rem; color: #0f294a;">SponsorFlow</span>
        </a>

        {{-- Center Nav Links --}}
        <div class="d-none d-md-flex align-items-center justify-content-center mx-2 flex-nowrap">
            <ul class="navbar-nav d-flex flex-row gap-0.5 flex-nowrap mb-0">

                {{-- Student --}}
                @if ($user->isStudent())
                    <li class="nav-item">
                        <a class="nav-link sf-topnav-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}"
                            href="{{ route('student.dashboard') }}">
                            <i class="bi bi-house-door"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sf-topnav-link {{ request()->routeIs('student.sle-fhe') || request()->routeIs('student.verification.*') ? 'active' : '' }}"
                            href="{{ route('student.sle-fhe') }}">
                            <i class="bi bi-patch-check"></i> SLE-FHE Verification
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sf-topnav-link {{ request()->routeIs('student.applications*') ? 'active' : '' }}"
                            href="{{ route('student.applications.index') }}">
                            <i class="bi bi-file-earmark-text"></i> Applications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sf-topnav-link {{ request()->routeIs('student.programs*') ? 'active' : '' }}"
                            href="{{ route('student.programs.index') }}">
                            <i class="bi bi-briefcase"></i> Sponsorship Opportunities
                        </a>
                    </li>
                @endif

                {{-- FASSG Officer --}}
                @if ($user->isFassg())
                    <li class="nav-item">
                        <a class="nav-link sf-topnav-link {{ request()->routeIs('fassg.dashboard') ? 'active' : '' }}"
                            href="{{ route('fassg.dashboard') }}">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sf-topnav-link {{ request()->routeIs('fassg.programs*') ? 'active' : '' }}"
                            href="{{ route('fassg.programs.index') }}">
                            <i class="bi bi-briefcase"></i> Programs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sf-topnav-link {{ request()->routeIs('fassg.verification*') ? 'active' : '' }}"
                            href="{{ route('fassg.verification.index') }}">
                            <i class="bi bi-patch-check"></i> Verification Queue
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sf-topnav-link {{ request()->routeIs('fassg.fixed*') ? 'active' : '' }}"
                            href="{{ route('fassg.fixed-lists.index') }}">
                            <i class="bi bi-list-check"></i> Fixed Lists
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sf-topnav-link {{ request()->routeIs('fassg.reports*') ? 'active' : '' }}"
                            href="{{ route('fassg.reports.index') }}">
                            <i class="bi bi-bar-chart-line"></i> Reports
                        </a>
                    </li>
                @endif

                {{-- Accounting Clerk --}}
                @if ($user->isAccounting())
                    <li class="nav-item">
                        <a class="nav-link sf-topnav-link {{ request()->routeIs('accounting.dashboard') ? 'active' : '' }}"
                            href="{{ route('accounting.dashboard') }}">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sf-topnav-link {{ request()->routeIs('accounting.beneficiaries*') ? 'active' : '' }}"
                            href="{{ route('accounting.beneficiaries.index') }}">
                            <i class="bi bi-cash-stack"></i> Confirmed Beneficiaries
                        </a>
                    </li>
                @endif

                {{-- External Sponsor --}}
                @if ($user->isSponsor())
                    <li class="nav-item">
                        <a class="nav-link sf-topnav-link {{ request()->routeIs('sponsor.dashboard') ? 'active' : '' }}"
                            href="{{ route('sponsor.dashboard') }}">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sf-topnav-link {{ request()->routeIs('sponsor.lists*') ? 'active' : '' }}"
                            href="{{ route('sponsor.lists.index') }}">
                            <i class="bi bi-clipboard-check"></i> Approval Lists
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sf-topnav-link {{ request()->routeIs('sponsor.applicants*') ? 'active' : '' }}"
                            href="{{ route('sponsor.applicants.index') }}">
                            <i class="bi bi-people"></i> Forwarded Applicants
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sf-topnav-link {{ request()->routeIs('sponsor.approvals*') ? 'active' : '' }}"
                            href="{{ route('sponsor.approvals.index') }}">
                            <i class="bi bi-clock-history"></i> Approval History
                        </a>
                    </li>
                @endif

                {{-- System Admin --}}
                @if ($user->isAdmin())
                    <li class="nav-item">
                        <a class="nav-link sf-topnav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                            href="{{ route('admin.dashboard') }}">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sf-topnav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                            href="{{ route('admin.users.index') }}">
                            <i class="bi bi-people"></i> User Accounts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sf-topnav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}"
                            href="{{ route('admin.audit-logs.index') }}">
                            <i class="bi bi-list-check"></i> Audit Logs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sf-topnav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"
                            href="{{ route('admin.settings.index') }}">
                            <i class="bi bi-sliders"></i> Security Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sf-topnav-link {{ request()->routeIs('admin.backups.*') ? 'active' : '' }}"
                            href="{{ route('admin.backups.index') }}">
                            <i class="bi bi-database-check"></i> Backups
                        </a>
                    </li>
                @endif

            </ul>
        </div>

        {{-- Right: Notifications + User Pill --}}
        <div class="d-flex align-items-center gap-2 flex-shrink-0">

            <a href="#" class="position-relative p-1.5 text-secondary d-none d-md-flex align-items-center">
                <i class="bi bi-bell fs-5"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-circle bg-danger"
                    style="font-size: 0.6rem; padding: 0.25em 0.45em;">0</span>
            </a>

            {{-- Desktop User Dropdown --}}
            <div class="dropdown d-none d-md-block">
                <button class="btn sf-user-pill d-flex align-items-center gap-2 border-0 bg-light rounded-pill px-2.5 py-1"
                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                        style="width: 30px; height: 30px; background-color: #0f294a; font-size: 0.8rem;">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <span class="fw-semibold text-dark small d-none d-lg-inline">{{ $user->name }}</span>
                    <span class="badge bg-primary-subtle text-primary-emphasis d-none d-lg-inline fw-semibold"
                        style="font-size: 0.65rem;">{{ $role->label() }}</span>
                    <i class="bi bi-chevron-down text-secondary" style="font-size: 0.7rem;"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-4 mt-2 p-1.5" style="min-width: 180px;">
                    @if ($user->isStudent())
                        <li>
                            <a class="dropdown-menu-item sf-dropdown-hover rounded-3 px-3 py-2 d-flex align-items-center gap-2 text-decoration-none small"
                                href="{{ route('student.profile') }}">
                                <i class="bi bi-person"></i> My Profile
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1 border-light"></li>
                    @endif
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="dropdown-menu-item sf-dropdown-hover rounded-3 px-3 py-2 w-100 border-0 bg-transparent text-start d-flex align-items-center gap-2 small">
                                <i class="bi bi-box-arrow-right"></i> Sign Out
                            </button>
                        </form>
                    </li>
                </ul>
            </div>

            {{-- Mobile Profile Circle --}}
            <a href="{{ $user->isStudent() ? route('student.profile') : route($user->homeRoute()) }}"
                class="rounded-circle d-md-none d-flex align-items-center justify-content-center text-white text-decoration-none shadow-sm"
                style="width: 34px; height: 34px; background-color: #0f294a;">
                <i class="bi bi-person-fill fs-6"></i>
            </a>

            {{-- Mobile Menu Toggle --}}
            <div class="dropdown d-md-none position-static">
                <button class="btn btn-light border-0 p-2 text-dark rounded-3 shadow-none" type="button"
                    id="mobileMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-list fs-4"></i>
                </button>

                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-4 p-3 mt-2 start-0 end-0 mx-3"
                    aria-labelledby="mobileMenuDropdown" style="width: auto; position: absolute; top: 100%;">

                    {{-- Student Mobile --}}
                    @if ($user->isStudent())
                        <li>
                            <a class="dropdown-item rounded-3 py-2.5 px-3 d-flex align-items-center gap-3 fw-semibold {{ request()->routeIs('student.dashboard') ? 'sf-mobile-active' : '' }}"
                                href="{{ route('student.dashboard') }}" style="font-size: 0.925rem;">
                                <i class="bi bi-house-door fs-5 text-secondary"></i> Home
                            </a>
                        </li>
                        <li class="mt-1">
                            <a class="dropdown-item rounded-3 py-2.5 px-3 d-flex align-items-center gap-3 text-secondary {{ request()->routeIs('student.sle-fhe') || request()->routeIs('student.verification.*') ? 'sf-mobile-active' : '' }}"
                                href="{{ route('student.sle-fhe') }}" style="font-size: 0.925rem;">
                                <i class="bi bi-patch-check fs-5 text-secondary"></i> SLE-FHE Verification
                            </a>
                        </li>
                        <li class="mt-1">
                            <a class="dropdown-item rounded-3 py-2.5 px-3 d-flex align-items-center gap-3 text-secondary {{ request()->routeIs('student.applications*') ? 'sf-mobile-active' : '' }}"
                                href="{{ route('student.applications.index') }}" style="font-size: 0.925rem;">
                                <i class="bi bi-file-earmark-text fs-5 text-secondary"></i> Applications
                            </a>
                        </li>
                        <li class="mt-1">
                            <a class="dropdown-item rounded-3 py-2.5 px-3 d-flex align-items-center gap-3 text-secondary {{ request()->routeIs('student.programs*') ? 'sf-mobile-active' : '' }}"
                                href="{{ route('student.programs.index') }}" style="font-size: 0.925rem;">
                                <i class="bi bi-briefcase fs-5 text-secondary"></i> Sponsorship Opportunities
                            </a>
                        </li>
                    @endif

                    {{-- FASSG Mobile --}}
                    @if ($user->isFassg())
                        <li>
                            <a class="dropdown-item rounded-3 py-2.5 px-3 d-flex align-items-center gap-3 fw-semibold {{ request()->routeIs('fassg.dashboard') ? 'sf-mobile-active' : '' }}"
                                href="{{ route('fassg.dashboard') }}" style="font-size: 0.925rem;">
                                <i class="bi bi-speedometer2 fs-5 text-secondary"></i> Dashboard
                            </a>
                        </li>
                        <li class="mt-1">
                            <a class="dropdown-item rounded-3 py-2.5 px-3 d-flex align-items-center gap-3 text-secondary {{ request()->routeIs('fassg.programs*') ? 'sf-mobile-active' : '' }}"
                                href="{{ route('fassg.programs.index') }}" style="font-size: 0.925rem;">
                                <i class="bi bi-briefcase fs-5 text-secondary"></i> Programs
                            </a>
                        </li>
                        <li class="mt-1">
                            <a class="dropdown-item rounded-3 py-2.5 px-3 d-flex align-items-center gap-3 text-secondary {{ request()->routeIs('fassg.verification*') ? 'sf-mobile-active' : '' }}"
                                href="{{ route('fassg.verification.index') }}" style="font-size: 0.925rem;">
                                <i class="bi bi-patch-check fs-5 text-secondary"></i> Verification Queue
                            </a>
                        </li>
                        <li class="mt-1">
                            <a class="dropdown-item rounded-3 py-2.5 px-3 d-flex align-items-center gap-3 text-secondary {{ request()->routeIs('fassg.fixed*') ? 'sf-mobile-active' : '' }}"
                                href="{{ route('fassg.fixed-lists.index') }}" style="font-size: 0.925rem;">
                                <i class="bi bi-list-check fs-5 text-secondary"></i> Fixed Lists
                            </a>
                        </li>
                        <li class="mt-1">
                            <a class="dropdown-item rounded-3 py-2.5 px-3 d-flex align-items-center gap-3 text-secondary {{ request()->routeIs('fassg.reports*') ? 'sf-mobile-active' : '' }}"
                                href="{{ route('fassg.reports.index') }}" style="font-size: 0.925rem;">
                                <i class="bi bi-bar-chart-line fs-5 text-secondary"></i> Reports
                            </a>
                        </li>
                    @endif

                    {{-- Accounting Mobile --}}
                    @if ($user->isAccounting())
                        <li>
                            <a class="dropdown-item rounded-3 py-2.5 px-3 d-flex align-items-center gap-3 fw-semibold {{ request()->routeIs('accounting.dashboard') ? 'sf-mobile-active' : '' }}"
                                href="{{ route('accounting.dashboard') }}" style="font-size: 0.925rem;">
                                <i class="bi bi-speedometer2 fs-5 text-secondary"></i> Dashboard
                            </a>
                        </li>
                        <li class="mt-1">
                            <a class="dropdown-item rounded-3 py-2.5 px-3 d-flex align-items-center gap-3 text-secondary {{ request()->routeIs('accounting.beneficiaries*') ? 'sf-mobile-active' : '' }}"
                                href="{{ route('accounting.beneficiaries.index') }}" style="font-size: 0.925rem;">
                                <i class="bi bi-cash-stack fs-5 text-secondary"></i> Confirmed Beneficiaries
                            </a>
                        </li>
                    @endif

                    {{-- Sponsor Mobile --}}
                    @if ($user->isSponsor())
                        <li>
                            <a class="dropdown-item rounded-3 py-2.5 px-3 d-flex align-items-center gap-3 fw-semibold {{ request()->routeIs('sponsor.dashboard') ? 'sf-mobile-active' : '' }}"
                                href="{{ route('sponsor.dashboard') }}" style="font-size: 0.925rem;">
                                <i class="bi bi-speedometer2 fs-5 text-secondary"></i> Dashboard
                            </a>
                        </li>
                        <li class="mt-1">
                            <a class="dropdown-item rounded-3 py-2.5 px-3 d-flex align-items-center gap-3 text-secondary {{ request()->routeIs('sponsor.lists*') ? 'sf-mobile-active' : '' }}"
                                href="{{ route('sponsor.lists.index') }}" style="font-size: 0.925rem;">
                                <i class="bi bi-clipboard-check fs-5 text-secondary"></i> Approval Lists
                            </a>
                        </li>
                        <li class="mt-1">
                            <a class="dropdown-item rounded-3 py-2.5 px-3 d-flex align-items-center gap-3 text-secondary {{ request()->routeIs('sponsor.applicants*') ? 'sf-mobile-active' : '' }}"
                                href="{{ route('sponsor.applicants.index') }}" style="font-size: 0.925rem;">
                                <i class="bi bi-people fs-5 text-secondary"></i> Forwarded Applicants
                            </a>
                        </li>
                        <li class="mt-1">
                            <a class="dropdown-item rounded-3 py-2.5 px-3 d-flex align-items-center gap-3 text-secondary {{ request()->routeIs('sponsor.approvals*') ? 'sf-mobile-active' : '' }}"
                                href="{{ route('sponsor.approvals.index') }}" style="font-size: 0.925rem;">
                                <i class="bi bi-clock-history fs-5 text-secondary"></i> Approval History
                            </a>
                        </li>
                    @endif

                    {{-- Admin Mobile --}}
                    @if ($user->isAdmin())
                        <li>
                            <a class="dropdown-item rounded-3 py-2.5 px-3 d-flex align-items-center gap-3 fw-semibold {{ request()->routeIs('admin.dashboard') ? 'sf-mobile-active' : '' }}"
                                href="{{ route('admin.dashboard') }}" style="font-size: 0.925rem;">
                                <i class="bi bi-house-door fs-5 text-secondary"></i> Dashboard
                            </a>
                        </li>
                        <li class="mt-1">
                            <a class="dropdown-item rounded-3 py-2.5 px-3 d-flex align-items-center gap-3 text-secondary {{ request()->routeIs('admin.users.*') ? 'sf-mobile-active' : '' }}"
                                href="{{ route('admin.users.index') }}" style="font-size: 0.925rem;">
                                <i class="bi bi-people fs-5 text-secondary"></i> User Accounts
                            </a>
                        </li>
                        <li class="mt-1">
                            <a class="dropdown-item rounded-3 py-2.5 px-3 d-flex align-items-center gap-3 text-secondary {{ request()->routeIs('admin.audit-logs.*') ? 'sf-mobile-active' : '' }}"
                                href="{{ route('admin.audit-logs.index') }}" style="font-size: 0.925rem;">
                                <i class="bi bi-list-check fs-5 text-secondary"></i> Audit Logs
                            </a>
                        </li>
                        <li class="mt-1">
                            <a class="dropdown-item rounded-3 py-2.5 px-3 d-flex align-items-center gap-3 text-secondary {{ request()->routeIs('admin.settings.*') ? 'sf-mobile-active' : '' }}"
                                href="{{ route('admin.settings.index') }}" style="font-size: 0.925rem;">
                                <i class="bi bi-sliders fs-5 text-secondary"></i> Security Settings
                            </a>
                        </li>
                        <li class="mt-1">
                            <a class="dropdown-item rounded-3 py-2.5 px-3 d-flex align-items-center gap-3 text-secondary {{ request()->routeIs('admin.backups.*') ? 'sf-mobile-active' : '' }}"
                                href="{{ route('admin.backups.index') }}" style="font-size: 0.925rem;">
                                <i class="bi bi-database-check fs-5 text-secondary"></i> Backups
                            </a>
                        </li>
                    @endif

                    <li><hr class="dropdown-divider my-2.5 border-light"></li>

                    @if ($user->isStudent())
                        <li>
                            <a class="dropdown-item rounded-3 py-2.5 px-3 d-flex align-items-center gap-3 text-dark fw-medium"
                                href="{{ route('student.profile') }}" style="font-size: 0.925rem;">
                                <i class="bi bi-person fs-5 text-dark"></i> My Profile
                            </a>
                        </li>
                        <li class="mt-1">
                    @else
                        <li>
                    @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="dropdown-item rounded-3 py-2.5 px-3 d-flex align-items-center gap-3 text-danger fw-medium border-0 bg-transparent w-100 text-start"
                                style="font-size: 0.925rem;">
                                <i class="bi bi-box-arrow-right fs-5 text-danger"></i> Sign Out
                            </button>
                        </form>
                    </li>
                </ul>
            </div>

        </div>

    </div>
</nav>

<style>
    .sf-topnav-link {
        color: #475569 !important;
        font-weight: 500;
        font-size: 0.825rem;
        transition: all 0.15s ease-in-out;
        white-space: nowrap;
        padding: 0.5rem 0.65rem;
        border-radius: 6px;
    }
    .sf-topnav-link:hover {
        background-color: #eef2f6 !important;
        color: #0f294a !important;
        font-weight: 600;
    }
    .sf-topnav-link.active {
        background-color: #eef2f6 !important;
        color: #0f294a !important;
        font-weight: 600;
    }
    .sf-user-pill {
        background-color: #f8fafc !important;
        border-radius: 30px !important;
        transition: all 0.15s ease-in-out;
    }
    .sf-user-pill:hover {
        background-color: #eef2f6 !important;
    }
    .sf-dropdown-hover {
        color: #334155;
        transition: all 0.15s ease-in-out;
    }
    .sf-dropdown-hover:hover {
        background-color: #fef2f2 !important;
        color: #dc2626 !important;
    }
    .sf-mobile-active {
        background-color: #ebf3fe !important;
        color: #0f294a !important;
    }
</style>
