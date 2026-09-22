<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · SponsorFlow | DORSU</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=Lexend:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <style>
        :root {
            --sf-navy: #0F2537;
            --sf-navy-deep: #0A1E31;
            --sf-navy-ink: #0F2942;
            --sf-slate: #0f172a;
            --sf-gold: #f59e0b;
            --sf-gold-soft: #fef3c7;
            --sf-bg: #f8fafc;
            --sf-white: #ffffff;
            --sf-border: #e2e8f0;
            --sf-muted: #64748b;
            --sf-success: #10b981;
            --sf-warning: #f59e0b;
            --sf-danger: #ef4444;
            --sf-info: #0284c7;
            /* Typography system */
            --font-heading: 'Plus Jakarta Sans', 'Lexend', -apple-system, BlinkMacSystemFont, sans-serif;
            --font-body: 'Inter', -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
            --font-mono: 'JetBrains Mono', ui-monospace, monospace;
        }

        body {
            background-color: var(--sf-bg) !important;
            color: var(--sf-slate) !important;
            font-family: var(--font-body) !important;
            font-size: 0.875rem;
        }

        /* Form controls, buttons, selects, tables inherit body font */
        input,
        textarea,
        select,
        button,
        .btn,
        table,
        .form-control,
        .form-select {
            font-family: var(--font-body) !important;
            font-size: 0.875rem;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        .sf-heading,
        .modal-title,
        .nav-brand {
            font-family: var(--font-heading) !important;
            letter-spacing: -0.01em;
        }

        .sf-mono {
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: -0.02em;
        }

        /* ---- Main content area ---- */
        .sf-content {
            width: 100%;
            background-color: #f8fafc;
            min-height: calc(100vh - 60px);
            padding: 1.75rem;
        }

        /* ---- Reusable pieces ---- */
        .sf-card {
            background-color: #ffffff;
            border: 1px solid var(--sf-border);
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
        }

        .filter-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
        }

        .card {
            background-color: #ffffff;
            border: 1px solid var(--sf-border);
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04);
        }

        .sf-stat-card {
            background: #ffffff;
            border: 1px solid var(--sf-border);
            border-left: 4px solid var(--sf-navy);
            border-radius: 0.5rem;
        }

        .sf-stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
        }

        .sf-eyebrow {
            color: var(--sf-muted);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .sf-heading {
            color: var(--sf-slate);
            font-weight: 700;
        }

        .sf-empty-state {
            text-align: center;
            padding: 3.5rem 1.5rem;
            color: #64748b;
        }

        .sf-empty-state i {
            font-size: 2.25rem;
            color: #cbd5e1;
            margin-bottom: .75rem;
            display: block;
        }

        .btn-sf-gold {
            background: var(--sf-gold);
            border-color: var(--sf-gold);
            color: #1a1300;
            font-weight: 600;
        }

        .btn-sf-gold:hover {
            background: #d97e0a;
            border-color: #d97e0a;
            color: #1a1300;
        }

        .btn-outline-danger {
            color: #dc3545;
            border-color: #dc3545;
            transition: all 0.15s ease-in-out;
        }

        .btn-outline-danger:hover,
        .btn-outline-danger:focus,
        .btn-outline-danger:active,
        .btn-outline-danger.active {
            background-color: #dc3545 !important;
            border-color: #dc3545 !important;
            color: #ffffff !important;
        }

        .btn-outline-danger:hover i,
        .btn-outline-danger:focus i,
        .btn-outline-danger:active i {
            color: #ffffff !important;
        }

        .sf-idle-modal {
            position: fixed;
            inset: 0;
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(15, 23, 42, .6);
            backdrop-filter: blur(4px);
        }

        .sf-idle-modal.d-none {
            display: none;
        }

        .sf-idle-dialog {
            width: 100%;
            max-width: 24rem;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 1.5rem 3rem rgba(15, 23, 42, .2);
            text-align: center;
        }

        .sf-idle-icon {
            width: 3rem;
            height: 3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            border-radius: 50%;
            background: #fef3c7;
            color: #d97706;
        }

        .sf-idle-icon svg {
            width: 1.5rem;
            height: 1.5rem;
        }

        .sf-idle-button {
            width: 100%;
            padding: .625rem 1rem;
            border: 0;
            border-radius: .75rem;
            background: var(--sf-navy);
            color: #fff;
            font-size: .75rem;
            font-weight: 700;
            transition: background .15s ease;
        }

        .sf-idle-button:hover {
            background: var(--sf-navy-deep);
        }

        .sf-readonly-banner {
            background: #eef4ff;
            border: 1px solid #c7d9f7;
            color: var(--sf-navy);
            border-radius: 12px;
            padding: .9rem 1.1rem;
        }

        table.sf-table thead th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #64748b;
            border-bottom-width: 1px;
            font-weight: 700;
            background: #fbfcfd;
        }

        table.sf-table td {
            vertical-align: middle;
            font-size: .875rem;
        }

        @media print {

            .sf-navbar,
            .no-print {
                display: none !important;
            }

            .sf-content {
                padding: 0 !important;
            }
        }

        @media (max-width: 991.98px) {
            .sf-content {
                padding: 1rem;
            }
        }

        .btn-sf-navy {
            background-color: var(--sf-navy, #0F2537) !important;
            border-color: var(--sf-navy, #0F2537) !important;
            color: #ffffff !important;
            font-weight: 600;
        }

        .btn-sf-navy:hover,
        .btn-sf-navy:focus {
            background-color: var(--sf-navy-deep, #0A1E31) !important;
            border-color: var(--sf-navy-deep, #0A1E31) !important;
            color: #ffffff !important;
        }

        .btn-outline-sf-navy {
            background-color: transparent !important;
            border-color: var(--sf-navy, #0F2537) !important;
            color: var(--sf-navy, #0F2537) !important;
            font-weight: 600;
        }

        .btn-outline-sf-navy:hover,
        .btn-outline-sf-navy:focus {
            background-color: var(--sf-navy, #0F2537) !important;
            border-color: var(--sf-navy, #0F2537) !important;
            color: #ffffff !important;
        }
    /* Hide native browser password reveal toggle in Edge/IE */
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear {
            display: none !important;
        }

        /* ---- Palette utilities: Cyan (Verified) / Emerald (Approved) / Indigo (SLE-FHE) ---- */
        .bg-cyan-50    { background-color: #ecfeff !important; }
        .text-cyan-700 { color: #0e7490 !important; }
        .border-cyan-200 { border-color: #a5f3fc !important; }

        .bg-emerald-50    { background-color: #ecfdf5 !important; }
        .text-emerald-700 { color: #047857 !important; }
        .border-emerald-200 { border-color: #a7f3d0 !important; }

        .bg-indigo-50    { background-color: #eef2ff !important; }
        .text-indigo-700 { color: #4338ca !important; }
        .border-indigo-200 { border-color: #c7d2fe !important; }

        .bg-red-50    { background-color: #FEF2F2 !important; }
        .text-red-700 { color: #991B1B !important; }
        .border-red-200 { border-color: #FCA5A5 !important; }

        /* ---- Cream / Gold "Pending Review" palette ---- */
        .bg-cream          { background-color: #FFF8E7 !important; }
        .border-cream-gold { border-color: #FCD34D !important; }
        .text-slate-900    { color: #0f172a !important; }

        .bg-amber-50     { background-color: #fffbeb !important; }
        .text-amber-700  { color: #b45309 !important; }
        .text-amber-800  { color: #92400e !important; }
        .border-amber-200 { border-color: #fde68a !important; }
        .border-amber-300 { border-color: #fcd34d !important; }

        .bg-blue-50     { background-color: #eff6ff !important; }
        .text-blue-700  { color: #1d4ed8 !important; }
        .border-blue-200 { border-color: #bfdbfe !important; }

        .text-xs { font-size: 0.75rem !important; }
        .px-2.5 { padding-left: 0.625rem !important; padding-right: 0.625rem !important; }
        .py-0.5 { padding-top: 0.125rem !important; padding-bottom: 0.125rem !important; }
        .rounded-md { border-radius: 0.375rem !important; }
        .inline-flex { display: inline-flex !important; }
        .items-center { align-items: center !important; }

        /* Password toggle eye feedback colors */
        .text-indigo-600 { color: #4f46e5 !important; }
        .text-gray-400   { color: #94a3b8 !important; }

        .rounded-full { border-radius: 9999px !important; }

        /* Utility: match Tailwind pointer-events-none */
        .pointer-events-none { pointer-events: none !important; }
    </style>

    @stack('styles')
</head>

<body>

    @auth
        @php
            $currentUser = auth()->user();
            $userRole = $currentUser?->role?->value ?? 'student';

            // Resolve target login gate URL:
            // Student: /login
            // Staff / FASSG / Sponsor / Accounting: /dorsu-staff-gate
            // Admin: /dorsu-sysadmin-gate
            $staffGateUrl = url(config('app.staff_login_path', 'dorsu-staff-gate'));
            $adminGateUrl = url(config('app.admin_login_path', 'dorsu-sysadmin-gate'));
            $studentGateUrl = route('login');

            if ($currentUser?->isAdmin()) {
                $loginGateUrl = $adminGateUrl;
            } elseif ($currentUser?->isFassg() || $currentUser?->isSponsor() || $currentUser?->isAccounting()) {
                $loginGateUrl = $staffGateUrl;
            } else {
                $loginGateUrl = $studentGateUrl;
            }

            // Get session timeout from database settings, default to 15 if not set
            $timeoutMinutes = \App\Models\SystemSetting::where('setting_key', 'session_timeout_minutes')->value('setting_value') ?? 15;
            $totalMilliseconds = (int) $timeoutMinutes * 60 * 1000;
            // Trigger warning modal 15 seconds before total session expiration
            $warningDelay = max($totalMilliseconds - 15000, 1000);
        @endphp

        <div id="idle-modal" class="sf-idle-modal d-none" role="dialog" aria-modal="true"
            aria-labelledby="idle-modal-title" aria-describedby="idle-modal-description"
            data-login-gate="{{ $loginGateUrl }}"
            data-user-role="{{ $userRole }}"
            data-warning-delay="{{ $warningDelay }}"
            data-countdown-seconds="15">
            <div class="sf-idle-dialog">
                <div class="sf-idle-icon" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 id="idle-modal-title" class="h5 mb-2 fw-bold text-dark">Session Expiring</h2>
                <p id="idle-modal-description" class="small text-secondary mb-4">
                    You have been inactive. You will be logged out in
                    <span id="idle-countdown" class="fw-bold text-warning">15</span> seconds due to inactivity.
                </p>
                <div class="d-flex flex-column gap-2">
                    <button id="stay-logged-in-btn" type="button" class="sf-idle-button">Stay Logged In</button>
                    <button id="idle-logout-btn" type="button" class="btn btn-link text-secondary text-decoration-none small py-1">Logout</button>
                </div>
            </div>
        </div>
    @endauth

    @auth
        @include('partials._navbar')
    @endauth

    <main class="sf-content">
        @include('partials._flash')
        @hasSection('page-title')
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                <div>
                    @hasSection('eyebrow')
                        <span class="sf-eyebrow d-block mb-1">@yield('eyebrow')</span>
                    @endif
                    <h2 class="h2 sf-heading mb-1 fw-bold">@yield('page-title')</h2>
                    @hasSection('subtitle')
                        <p class="text-secondary mb-0">@yield('subtitle')</p>
                    @endif
                </div>
                @hasSection('header-actions')
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @yield('header-actions')
                    </div>
                @endif
            </div>
        @endif
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePasswordVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';

            if (isPassword) {
                btn.classList.add('text-indigo-600');
                btn.classList.remove('text-gray-400');
            } else {
                btn.classList.remove('text-indigo-600');
                btn.classList.add('text-gray-400');
            }
        }
        window.togglePasswordVisibility = togglePasswordVisibility;
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var userMenuButton = document.getElementById('user-menu-btn');
            var userMenuDropdown = document.getElementById('user-menu-dropdown');

            if (!userMenuButton || !userMenuDropdown) {
                return;
            }

            userMenuButton.addEventListener('click', function(event) {
                event.stopPropagation();
                var isHidden = userMenuDropdown.classList.toggle('d-none');
                userMenuButton.setAttribute('aria-expanded', String(!isHidden));
            });

            document.addEventListener('click', function(event) {
                if (!userMenuDropdown.contains(event.target) && !userMenuButton.contains(event.target)) {
                    userMenuDropdown.classList.add('d-none');
                    userMenuButton.setAttribute('aria-expanded', 'false');
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
                new bootstrap.Tooltip(el);
            });
        });
    </script>
    @stack('scripts')

    @auth
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var idleModal = document.getElementById('idle-modal');
                if (!idleModal) return;

                var countdown = document.getElementById('idle-countdown');
                var stayButton = document.getElementById('stay-logged-in-btn');
                var logoutButton = document.getElementById('idle-logout-btn');
                var activityEvents = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'];

                var loginGateUrl = idleModal.getAttribute('data-login-gate') || "{{ $loginGateUrl }}";
                var warningAfter = parseInt(idleModal.getAttribute('data-warning-delay'), 10) || {{ $warningDelay }};

                var warningTimer = null;
                var countdownTimer = null;
                var warningVisible = false;

                function clearTimers() {
                    if (warningTimer) {
                        clearTimeout(warningTimer);
                        warningTimer = null;
                    }
                    if (countdownTimer) {
                        clearInterval(countdownTimer);
                        countdownTimer = null;
                    }
                }

                function redirectToLoginGate() {
                    clearTimers();
                    var separator = loginGateUrl.indexOf('?') !== -1 ? '&' : '?';
                    window.location.href = loginGateUrl + separator + 'session_expired=1';
                }

                function hideWarning() {
                    warningVisible = false;
                    idleModal.classList.add('d-none');
                    if (countdownTimer) {
                        clearInterval(countdownTimer);
                        countdownTimer = null;
                    }
                }

                function showWarning() {
                    warningVisible = true;
                    idleModal.classList.remove('d-none');
                    var secondsLeft = 15;
                    if (countdown) {
                        countdown.textContent = secondsLeft;
                    }

                    if (countdownTimer) {
                        clearInterval(countdownTimer);
                    }

                    countdownTimer = setInterval(function() {
                        secondsLeft -= 1;
                        if (countdown) {
                            countdown.textContent = Math.max(secondsLeft, 0);
                        }

                        if (secondsLeft <= 0) {
                            clearInterval(countdownTimer);
                            countdownTimer = null;
                            redirectToLoginGate();
                        }
                    }, 1000);
                }

                function resetIdleTimer() {
                    clearTimers();
                    hideWarning();
                    warningTimer = setTimeout(showWarning, warningAfter);
                }

                activityEvents.forEach(function(eventName) {
                    document.addEventListener(eventName, function() {
                        if (!warningVisible) {
                            resetIdleTimer();
                        }
                    }, {
                        passive: true
                    });
                });

                if (stayButton) {
                    stayButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        resetIdleTimer();
                    });
                }

                if (logoutButton) {
                    logoutButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        redirectToLoginGate();
                    });
                }

                resetIdleTimer();
            });
        </script>
    @endauth

    @stack('scripts')
</body>

</html>
