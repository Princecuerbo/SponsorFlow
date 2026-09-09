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
            --sf-navy: #1e3a8a;
            --sf-navy-deep: #172554;
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

        .btn-sf-navy {
            background: var(--sf-navy);
            border-color: var(--sf-navy);
            color: #fff;
            font-weight: 600;
        }

        .btn-sf-navy:hover {
            background: var(--sf-navy-deep);
            border-color: var(--sf-navy-deep);
            color: #fff;
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
            --bs-btn-color: #ffffff;
            --bs-btn-bg: #1e3a8a;
            --bs-btn-border-color: #1e3a8a;
            --bs-btn-hover-color: #ffffff;
            --bs-btn-hover-bg: #172554;
            --bs-btn-hover-border-color: #172554;
            --bs-btn-focus-shadow-rgb: 30, 58, 138;
            --bs-btn-active-color: #ffffff;
            --bs-btn-active-bg: #172554;
            --bs-btn-active-border-color: #172554;
            background-color: #1e3a8a !important;
            border-color: #1e3a8a !important;
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

        .rounded-full { border-radius: 9999px !important; }
        .hidden { display: none !important; }

        /* Positioning & sizing utilities matching login page */
        .relative { position: relative !important; }
        .absolute { position: absolute !important; }
        .inset-y-0 { top: 0 !important; bottom: 0 !important; }
        .right-0 { right: 0 !important; }
        .flex { display: flex !important; }
        .items-center { align-items: center !important; }
        .pr-3\.5 { padding-right: 0.875rem !important; }
        .pr-12 { padding-right: 3rem !important; }
        .text-slate-400 { color: #94a3b8 !important; }
        .text-slate-400:hover, .hover\:text-slate-600:hover { color: #475569 !important; }
        .h-5 { height: 1.25rem !important; }
        .w-5 { width: 1.25rem !important; }
        button.absolute { background: transparent; border: 0; }
    </style>

    @stack('styles')
</head>

<body>

    @auth
        <div id="idle-modal" class="sf-idle-modal d-none" role="dialog" aria-modal="true"
            aria-labelledby="idle-modal-title" aria-describedby="idle-modal-description">
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
                <button id="stay-logged-in-btn" type="button" class="sf-idle-button">Stay Logged In</button>
            </div>
        </div>
    @endauth

    @auth
        @include('partials._navbar')
    @endauth

    <main class="sf-content">
        @include('partials._flash')
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
                var countdown = document.getElementById('idle-countdown');
                var stayButton = document.getElementById('stay-logged-in-btn');
                var logoutForm = document.getElementById('logout-form');
                var activityEvents = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'];

                // ==========================================
                // DYNAMICALLY FETCH FROM DATABASE SETTINGS:
                // ==========================================
                @php
                    // Get the session timeout minutes from the database, default to 15 if not set
                    $timeoutMinutes = \App\Models\SystemSetting::where('setting_key', 'session_timeout_minutes')->value('setting_value') ?? 15;

                    // Convert minutes to total milliseconds
                    $totalMilliseconds = (int) $timeoutMinutes * 60 * 1000;

                    // Trigger the warning modal 15 seconds before the total session time expires
                    // (Ensures it handles short testing times like 1 minute safely)
                    $warningDelay = max($totalMilliseconds - 15000, 1000);
                @endphp

                var warningAfter = {{ $warningDelay }};
                // ==========================================

                var warningTimer;
                var countdownTimer;
                var warningVisible = false;

                function clearTimers() {
                    clearTimeout(warningTimer);
                    clearInterval(countdownTimer);
                }

                function hideWarning() {
                    warningVisible = false;
                    idleModal.classList.add('d-none');
                    clearInterval(countdownTimer);
                }

                function logoutForInactivity() {
                    var logoutForm = document.getElementById('logout-form');

                    if (logoutForm) {
                        logoutForm.submit();
                    } else {
                        var form = document.createElement('form');
                        form.method = 'POST';
                        form.action = "{{ route('logout') }}";

                        var csrfToken = document.createElement('input');
                        csrfToken.type = 'hidden';
                        csrfToken.name = '_token';
                        csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                        form.appendChild(csrfToken);
                        document.body.appendChild(form);
                        form.submit();
                    }
                }

                function showWarning() {
                    warningVisible = true;
                    idleModal.classList.remove('d-none');
                    var secondsLeft = 15; // 15-second modal countdown
                    countdown.textContent = secondsLeft;

                    clearInterval(countdownTimer);
                    countdownTimer = setInterval(function() {
                        secondsLeft -= 1;
                        countdown.textContent = Math.max(secondsLeft, 0);

                        if (secondsLeft <= 0) {
                            clearInterval(countdownTimer);
                            logoutForInactivity();
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

                stayButton.addEventListener('click', resetIdleTimer);
                resetIdleTimer();
            });
        </script>
    @endauth

    @stack('scripts')
</body>

</html>
