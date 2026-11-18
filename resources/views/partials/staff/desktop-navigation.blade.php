@php
    $staffUser = auth()->user();

    // ---- Role Configuration ----
    if ($staffUser->isFassg()) {
        $portalLabel = 'FASSG Portal';
        $portalRoleDisplay = 'FASSG Personnel';
        $displayName = $staffUser->name ?? 'FASSG Officer';
        $sidebarKey = 'sf_fassg_sidebar_collapsed';
        $homeRoute = 'fassg.dashboard';
        $staffNavigation = [
            [
                'label' => 'Dashboard',
                'route' => 'fassg.dashboard',
                'icon' => 'grid',
                'active' => request()->routeIs('fassg.dashboard'),
            ],
            [
                'label' => 'Programs',
                'route' => 'fassg.programs.index',
                'icon' => 'briefcase',
                'active' => request()->routeIs('fassg.programs*'),
            ],
            [
                'label' => 'SLE-FHE Verification',
                'route' => 'fassg.sle-fhe.index',
                'icon' => 'shield-check',
                'active' => request()->routeIs('fassg.sle-fhe.index', 'fassg.verification.*') && !request()->routeIs('fassg.sle-fhe.verified'),
            ],
            [
                'label' => 'Verified Students',
                'route' => 'fassg.sle-fhe.verified',
                'icon' => 'people',
                'active' => request()->routeIs('fassg.sle-fhe.verified'),
            ],
            [
                'label' => 'Application Queue',
                'route' => 'fassg.applications.index',
                'icon' => 'file-earmark-text',
                'active' => request()->routeIs('fassg.applications*'),
            ],
            [
                'label' => 'Generated Batches',
                'route' => 'fassg.generated-batches.index',
                'icon' => 'boxes',
                'active' => request()->routeIs('fassg.generated-batches*', 'fassg.batches*'),
            ],
            [
                'label' => 'Fixed Lists',
                'route' => 'fassg.fixed-lists.index',
                'icon' => 'list-check',
                'active' => request()->routeIs('fassg.fixed*'),
            ],
            [
                'label' => 'Reports',
                'route' => 'fassg.reports.index',
                'icon' => 'bar-chart-line',
                'active' => request()->routeIs('fassg.reports*', 'fassg.monitoring*'),
            ],
        ];
    } elseif ($staffUser->isSponsor()) {
        $portalLabel = 'Sponsor Portal';
        $portalRoleDisplay = 'External Sponsor';
        $displayName = $staffUser->sponsor?->company_organization_name ?? $staffUser->name ?? 'Sponsor';
        $sidebarKey = 'sf_sponsor_sidebar_collapsed';
        $homeRoute = 'sponsor.dashboard';
        $staffNavigation = [
            [
                'label' => 'Dashboard',
                'route' => 'sponsor.dashboard',
                'icon' => 'grid',
                'active' => request()->routeIs('sponsor.dashboard'),
            ],
            [
                'label' => 'Approvals Queue',
                'route' => 'sponsor.approvals.index',
                'icon' => 'clipboard-check',
                'active' => request()->routeIs('sponsor.approvals.index', 'sponsor.lists*', 'sponsor.applicants*', 'sponsor.review*', 'sponsor.programs*'),
            ],
            [
                'label' => 'Approval History',
                'route' => 'sponsor.approvals.history',
                'icon' => 'clock-history',
                'active' => request()->routeIs('sponsor.approvals.history'),
            ],
        ];
    } elseif ($staffUser->isAccounting()) {
        $portalLabel = 'Accounting Portal';
        $portalRoleDisplay = 'Accounting Personnel';
        $displayName = $staffUser->name ?? 'Accounting Officer';
        $sidebarKey = 'sf_accounting_sidebar_collapsed';
        $homeRoute = 'accounting.dashboard';
        $staffNavigation = [
            [
                'label' => 'Dashboard',
                'route' => 'accounting.dashboard',
                'icon' => 'grid',
                'active' => request()->routeIs('accounting.dashboard'),
            ],
            [
                'label' => 'Confirmed Beneficiaries',
                'route' => 'accounting.beneficiaries.index',
                'icon' => 'person-check',
                'active' => request()->routeIs('accounting.beneficiaries*', 'accounting.applications*', 'accounting.documents*', 'accounting.fixed-lists*', 'accounting.reports*', 'accounting.programs*', 'accounting.audit-logs*', 'accounting.export'),
            ],
        ];
    } elseif ($staffUser->isAdmin()) {
        $portalLabel = 'Admin Portal';
        $portalRoleDisplay = 'System Administrator';
        $displayName = $staffUser->name ?? 'Administrator';
        $sidebarKey = 'sf_admin_sidebar_collapsed';
        $homeRoute = 'admin.dashboard';
        $staffNavigation = [
            [
                'label' => 'Dashboard',
                'route' => 'admin.dashboard',
                'icon' => 'grid',
                'active' => request()->routeIs('admin.dashboard'),
            ],
            [
                'label' => 'User Accounts',
                'route' => 'admin.users.index',
                'icon' => 'people',
                'active' => request()->routeIs('admin.users.*'),
            ],
            [
                'label' => 'Audit Logs',
                'route' => 'admin.audit-logs.index',
                'icon' => 'journal-text',
                'active' => request()->routeIs('admin.audit-logs.*'),
            ],
            [
                'label' => 'Security Settings',
                'route' => 'admin.settings.index',
                'icon' => 'sliders',
                'active' => request()->routeIs('admin.settings.*'),
            ],
            [
                'label' => 'Backups',
                'route' => 'admin.backups.index',
                'icon' => 'database',
                'active' => request()->routeIs('admin.backups.*', 'admin.backup.*'),
            ],
        ];
    } else {
        $portalLabel = 'Portal';
        $portalRoleDisplay = 'User';
        $displayName = $staffUser->name ?? 'User';
        $sidebarKey = 'sf_sidebar_collapsed';
        $homeRoute = 'login';
        $staffNavigation = [];
    }

    // Calculate user initials for the header avatar
    $staffNameParts = explode(' ', trim($displayName));
    $staffInitials = count($staffNameParts) >= 2
        ? mb_substr($staffNameParts[0], 0, 1) . mb_substr(end($staffNameParts), 0, 1)
        : mb_substr($staffNameParts[0] ?? 'U', 0, 2);
    $staffInitials = strtoupper($staffInitials);
@endphp

<a href="#main-content" class="sf-staff-skip-link">Skip to main content</a>

{{-- Clean Vertical Left Sidebar (Desktop ≥992px) with Expanded & Collapsed States --}}
<aside class="sf-staff-sidebar no-print" id="staffDesktopSidebar" aria-label="{{ $portalLabel }} sidebar">
    <div class="sf-staff-brand-container">
        <a href="{{ route($homeRoute) }}" class="sf-staff-brand" aria-label="SponsorFlow home">
            <span class="sf-staff-brand-icon" aria-hidden="true"><i class="fa-solid fa-hand-holding-dollar"></i></span>
            <span class="sf-staff-brand-text">SponsorFlow</span>
        </a>
    </div>

    <div class="sf-staff-sidebar-body">
        <p class="sf-staff-nav-caption" id="staff-navigation-label">{{ $portalLabel }}</p>
        <nav aria-labelledby="staff-navigation-label">
            <ul class="sf-staff-nav-list">
                @foreach ($staffNavigation as $item)
                    <li>
                        {{-- Tooltips are only shown in collapsed mode via JS; no title/data-bs attrs in expanded --}}
                        <a href="{{ route($item['route']) }}"
                            @class(['sf-staff-nav-link', 'is-active' => $item['active']])
                            @if ($item['active']) aria-current="page" @endif
                            aria-label="{{ $item['label'] }}"
                            data-sf-tooltip-label="{{ $item['label'] }}">
                            <i class="bi bi-{{ $item['icon'] }}" aria-hidden="true"></i>
                            <span class="sf-staff-nav-label">{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    </div>

    <div class="sf-staff-sidebar-footer">
        <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button type="submit"
                class="sf-staff-nav-link sf-staff-sign-out"
                aria-label="Sign Out"
                data-sf-tooltip-label="Sign Out">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                <span class="sf-staff-nav-label">Sign Out</span>
            </button>
        </form>
    </div>
</aside>

{{-- Clean White Top Header (Desktop ≥992px) --}}
<header class="sf-staff-header no-print">
    <div class="d-flex align-items-center gap-3 min-w-0">
        <button type="button" class="sf-staff-sidebar-toggle" id="staffSidebarToggle"
            aria-label="Toggle sidebar"
            aria-controls="staffDesktopSidebar"
            aria-expanded="true">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>
        <div class="sf-staff-header-title">@yield('title', $portalLabel)</div>
    </div>
    <div class="sf-staff-header-actions">
        <div class="dropdown">
            <button type="button" class="sf-staff-account-button" id="staffDesktopAccountMenu"
                data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu for {{ $displayName }}">
                <span class="sf-staff-avatar" aria-hidden="true">
                    <span>{{ $staffInitials }}</span>
                </span>
                <span class="sf-staff-account-text">
                    <span class="sf-staff-account-name">{{ $displayName }}</span>
                    <span class="sf-staff-account-role">{{ $portalRoleDisplay }}</span>
                </span>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="staffDesktopAccountMenu">
                <li>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="dropdown-item sf-staff-sign-out"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Sign Out</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.getElementById('staffSidebarToggle');
        var sidebar = document.getElementById('staffDesktopSidebar');
        if (!toggle || !sidebar) return;

        // Tooltip instances managed per collapsed/expanded state
        var tooltipInstances = [];
        var sidebarKey = @json($sidebarKey);

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
        if (localStorage.getItem(sidebarKey) === '1') {
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
            localStorage.setItem(sidebarKey, isCollapsed ? '1' : '0');
            toggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');

            // Re-sync tooltips after state change
            syncTooltips();

            // Dispatch resize event so tables and charts adjust smoothly
            window.dispatchEvent(new Event('resize'));
        });
    });
</script>
