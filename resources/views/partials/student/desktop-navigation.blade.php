@php
    $studentUser = auth()->user();
    // Defensive access to notifications
    $studentUnreadCount = $studentUser?->unreadNotifications?->count() ?? 0;

    // Calculate user initials for the header avatar
    $nameParts = explode(' ', trim($studentUser?->name ?? 'Student'));
    $initials = count($nameParts) >= 2
        ? mb_substr($nameParts[0], 0, 1) . mb_substr(end($nameParts), 0, 1)
        : mb_substr($nameParts[0] ?? 'S', 0, 2);
    $initials = strtoupper($initials);

    // Exact outline icon mapping matching reference
    $studentNavigation = [
        [
            'label' => 'Home / Dashboard',
            'route' => 'student.dashboard',
            'icon' => 'grid', // Four-square grid outline
            'active' => request()->routeIs('student.dashboard'),
        ],
        [
            'label' => 'SLE-FHE Verification',
            'route' => 'student.sle-fhe',
            'icon' => 'shield-check', // Shield with check outline
            'active' => request()->routeIs('student.sle-fhe', 'student.sle-fhe.*', 'student.verification', 'student.verification.*', 'student.verify.*'),
        ],
        [
            'label' => 'Sponsorship Opportunities',
            'route' => 'student.programs.index',
            'icon' => 'briefcase', // Briefcase outline
            'active' => request()->routeIs('student.programs.*'),
        ],
        [
            'label' => 'My Applications',
            'route' => 'student.applications.index',
            'icon' => 'file-earmark-text', // Document/file text outline
            'active' => request()->routeIs('student.applications.*', 'student.status', 'student.apply'),
        ],
        [
            'label' => 'Notifications',
            'route' => 'notifications.index',
            'icon' => 'bell', // Bell outline
            'active' => request()->routeIs('notifications.*', 'student.notifications.*'),
        ],
        [
            'label' => 'My Profile',
            'route' => 'student.profile',
            'icon' => 'person-circle', // User-circle outline
            'active' => request()->routeIs('student.profile', 'student.profile.*'),
        ],
    ];
@endphp

<a href="#main-content" class="sf-student-skip-link">Skip to main content</a>

{{-- Clean Vertical Left Sidebar (Desktop ≥992px) with Expanded & Collapsed States --}}
<aside class="sf-student-sidebar no-print" id="studentDesktopSidebar" aria-label="Student portal sidebar">
    <div class="sf-student-brand-container">
        <a href="{{ route('student.dashboard') }}" class="sf-student-brand" aria-label="SponsorFlow student home">
            <span class="sf-student-brand-icon" aria-hidden="true"><i class="fa-solid fa-hand-holding-dollar"></i></span>
            <span class="sf-student-brand-text">SponsorFlow</span>
        </a>
    </div>

    <div class="sf-student-sidebar-body">
        <p class="sf-student-nav-caption" id="student-navigation-label">Student Portal</p>
        <nav aria-labelledby="student-navigation-label">
            <ul class="sf-student-nav-list">
                @foreach ($studentNavigation as $item)
                    <li>
                        {{-- Tooltips are only shown in collapsed mode via JS; no title/data-bs attrs in expanded --}}
                        <a href="{{ route($item['route']) }}"
                            @class(['sf-student-nav-link', 'is-active' => $item['active']])
                            @if ($item['active']) aria-current="page" @endif
                            aria-label="{{ $item['label'] }}"
                            data-sf-tooltip-label="{{ $item['label'] }}">
                            <i class="bi bi-{{ $item['icon'] }}" aria-hidden="true"></i>
                            <span class="sf-student-nav-label">{{ $item['label'] }}</span>
                            @if ($item['route'] === 'notifications.index' && $studentUnreadCount > 0)
                                <span class="sf-student-unread-count">
                                    {{ $studentUnreadCount > 99 ? '99+' : $studentUnreadCount }}<span class="visually-hidden"> unread notifications</span>
                                </span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    </div>

    <div class="sf-student-sidebar-footer">
        <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button type="submit"
                class="sf-student-nav-link sf-student-sign-out"
                aria-label="Sign Out"
                data-sf-tooltip-label="Sign Out">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                <span class="sf-student-nav-label">Sign Out</span>
            </button>
        </form>
    </div>
</aside>

{{-- Clean White Top Header (Desktop ≥992px) --}}
<header class="sf-student-header no-print">
    <div class="d-flex align-items-center gap-3 min-w-0">
        <button type="button" class="sf-student-sidebar-toggle" id="studentSidebarToggle"
            aria-label="Toggle student sidebar"
            aria-controls="studentDesktopSidebar"
            aria-expanded="true">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>
        <div class="sf-student-header-title">@yield('title', 'Student Portal')</div>
    </div>
    <div class="sf-student-header-actions">
        <div class="dropdown">
            <button type="button" class="sf-student-account-button" id="studentDesktopAccountMenu"
                data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu for {{ $studentUser?->name }}">
                <span class="sf-student-avatar" aria-hidden="true">
                    <span>{{ $initials }}</span>
                </span>
                <span class="sf-student-account-text">
                    <span class="sf-student-account-name">{{ $studentUser?->name }}</span>
                    <span class="sf-student-account-role">Student Applicant</span>
                </span>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="studentDesktopAccountMenu">
                <li><a class="dropdown-item" href="{{ route('student.profile') }}"><i class="bi bi-person me-2" aria-hidden="true"></i>My Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="dropdown-item sf-student-sign-out"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Sign Out</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.getElementById('studentSidebarToggle');
        var sidebar = document.getElementById('studentDesktopSidebar');
        if (!toggle || !sidebar) return;

        // Tooltip instances managed per collapsed/expanded state
        var tooltipInstances = [];

        function syncTooltips() {
            // Destroy existing tooltip instances
            tooltipInstances.forEach(function (t) { t.dispose(); });
            tooltipInstances = [];

            var isCollapsed = document.body.classList.contains('sf-sidebar-collapsed');
            var items = sidebar.querySelectorAll('[data-sf-tooltip-label]');

            items.forEach(function (el) {
                if (isCollapsed) {
                    // In collapsed mode: enable Bootstrap tooltips on icon-only items
                    el.setAttribute('data-bs-toggle', 'tooltip');
                    el.setAttribute('data-bs-placement', 'right');
                    el.setAttribute('data-bs-title', el.getAttribute('data-sf-tooltip-label'));
                    el.removeAttribute('title'); // prevent double tooltip
                    var tip = new bootstrap.Tooltip(el, { trigger: 'hover focus' });
                    tooltipInstances.push(tip);
                } else {
                    // In expanded mode: remove tooltip attributes so labels serve as the visual cue
                    el.removeAttribute('data-bs-toggle');
                    el.removeAttribute('data-bs-placement');
                    el.removeAttribute('data-bs-title');
                    el.removeAttribute('title');
                }
            });
        }

        // Restore saved sidebar collapsed state
        if (localStorage.getItem('sf_student_sidebar_collapsed') === '1') {
            document.body.classList.add('sf-sidebar-collapsed');
            toggle.setAttribute('aria-expanded', 'false');
        } else {
            toggle.setAttribute('aria-expanded', 'true');
        }

        // Initialize tooltips to match initial state
        syncTooltips();

        toggle.addEventListener('click', function () {
            document.body.classList.toggle('sf-sidebar-collapsed');
            var isCollapsed = document.body.classList.contains('sf-sidebar-collapsed');
            localStorage.setItem('sf_student_sidebar_collapsed', isCollapsed ? '1' : '0');
            toggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');

            // Re-sync tooltips after state change
            syncTooltips();

            // Dispatch resize event so tables and charts adjust smoothly
            window.dispatchEvent(new Event('resize'));
        });
    });
</script>
