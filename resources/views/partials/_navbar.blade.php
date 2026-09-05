@php
    $user = auth()->user();
    $role = $user->role;
    $firstName = trim((string) ($user->name ?? ''));
    $firstName = $firstName === '' ? '' : explode(' ', $firstName)[0];
@endphp

<nav class="sf-navbar navbar bg-white border-bottom py-0 sticky-top" style="min-height:60px;">
    <div class="container d-flex align-items-center justify-content-center gap-4 py-2">

        {{-- Brand --}}
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-decoration-none" href="#">
            <div class="rounded-3 p-2 text-white d-flex align-items-center justify-content-center"
                style="background-color: #0f294a; width: 36px; height: 36px;">
                <i class="fa-solid fa-hand-holding-dollar fs-6"></i>
            </div>
            <span style="color: #0f294a;" class="fs-5 fw-bold">SponsorFlow</span>
        </a>

        {{-- Navigation Links --}}
        <div class="d-none d-md-flex align-items-center flex-nowrap">
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
                        <a class="nav-link sf-topnav-link {{ request()->routeIs('sponsor.approvals.index') ? 'active' : '' }}"
                            href="{{ route('sponsor.approvals.index') }}">
                            <i class="bi bi-inboxes"></i> Approvals Queue
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sf-topnav-link {{ request()->routeIs('sponsor.approvals.history') ? 'active' : '' }}"
                            href="{{ route('sponsor.approvals.history') }}">
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

        {{-- Notification Bell + User Profile (inline, left-aligned) --}}
        <div class="d-flex align-items-center gap-3 flex-shrink-0">

            <a href="#" class="position-relative text-secondary d-none d-md-inline-flex align-items-center"
                aria-label="Notifications">
                <i class="bi bi-bell fs-5"></i>
                <span
                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-white fw-bold"
                    style="font-size: 0.6rem; padding: 0.4em 0.7em;">0</span>
            </a>

            {{-- Desktop User Dropdown --}}
            <div class="dropdown d-none d-md-block">
                <button class="bg-transparent border-0 p-0 d-flex align-items-center gap-2 text-decoration-none"
                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                        style="width: 30px; height: 30px; background-color: #0f294a; font-size: 0.8rem;">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <span class="fw-medium text-secondary small d-none d-lg-inline">{{ $firstName }}</span>
                    <i class="bi bi-chevron-down text-secondary" style="font-size: 0.7rem;"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-4 mt-2 p-1.5"
                    style="min-width: 180px;">
                    @if ($user->isStudent())
                        <li>
                            <a class="dropdown-menu-item sf-dropdown-hover rounded-3 px-3 py-2 d-flex align-items-center gap-2 text-decoration-none small"
                                href="{{ route('student.profile') }}">
                                <i class="bi bi-person"></i> My Profile
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider my-1 border-light">
                        </li>
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
                            <a class="dropdown-item rounded-3 py-2.5 px-3 d-flex align-items-center gap-3 text-secondary {{ request()->routeIs('sponsor.approvals.index') ? 'sf-mobile-active' : '' }}"
                                href="{{ route('sponsor.approvals.index') }}" style="font-size: 0.925rem;">
                                <i class="bi bi-inboxes fs-5 text-secondary"></i> Approvals Queue
                            </a>
                        </li>
                        <li class="mt-1">
                            <a class="dropdown-item rounded-3 py-2.5 px-3 d-flex align-items-center gap-3 text-secondary {{ request()->routeIs('sponsor.approvals.history') ? 'sf-mobile-active' : '' }}"
                                href="{{ route('sponsor.approvals.history') }}" style="font-size: 0.925rem;">
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

                    <li>
                        <hr class="dropdown-divider my-2.5 border-light">
                    </li>

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
    .sf-navbar .container {
        justify-content: center;
    }

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
