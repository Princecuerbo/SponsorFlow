<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SponsorFlow · Davao Oriental State University</title>

    {{-- Institutional Typography --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=Lexend:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Icons & Utility Frameworks --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        /* ─── Institutional Typography & Scoped Desktop Styling ─── */
        body.sf-public-landing {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
            background-color: #F8FAFC;
            color: #0F172A;
        }

        body.sf-public-landing h1,
        body.sf-public-landing h2,
        body.sf-public-landing h3,
        body.sf-public-landing h4,
        body.sf-public-landing .font-heading {
            font-family: 'Plus Jakarta Sans', 'Lexend', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        /* ─── Desktop-Only Refinements (min-width: 992px) ─── */
        @media screen and (min-width: 992px) {
            body.sf-public-landing .sf-landing-container {
                max-width: 1200px;
            }

            body.sf-public-landing .sf-program-card {
                border-radius: 14px;
                border: 1px solid #E2E8F0;
                transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.2s ease;
            }

            body.sf-public-landing .sf-program-card:hover {
                transform: translateY(-2px);
                border-color: #CBD5E1;
                box-shadow: 0 10px 25px -5px rgba(15, 41, 66, 0.08), 0 8px 10px -6px rgba(15, 41, 66, 0.04);
            }

            body.sf-public-landing .sf-category-card {
                border-radius: 14px;
                border: 1px solid #E2E8F0;
                transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.2s ease;
            }

            body.sf-public-landing .sf-category-card:hover {
                transform: translateY(-2px);
                border-color: #CBD5E1;
                box-shadow: 0 10px 25px -5px rgba(15, 41, 66, 0.08), 0 8px 10px -6px rgba(15, 41, 66, 0.04);
            }

            body.sf-public-landing .sf-btn-primary {
                background-color: #0F2942;
                border-color: #0F2942;
                color: #FFFFFF;
                transition: background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
            }

            body.sf-public-landing .sf-btn-primary:hover {
                background-color: #173B57;
                border-color: #173B57;
                color: #FFFFFF;
            }

            body.sf-public-landing .sf-btn-primary:focus-visible {
                outline: none;
                box-shadow: 0 0 0 3px rgba(15, 41, 66, 0.25);
            }
        }
    </style>
</head>

<body class="sf-public-landing min-h-screen flex flex-col antialiased">

    {{-- Navigation Bar --}}
    <nav class="bg-white border-b border-slate-200/90 sticky top-0 z-50 shadow-sm" aria-label="Main Navigation">
        <div class="sf-landing-container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center gap-4">
                {{-- Brand Logo & Name --}}
                <a href="{{ url('/') }}" class="flex items-center gap-2.5 text-decoration-none min-w-0 group" aria-label="SponsorFlow home">
                    <div
                        class="w-9 h-9 rounded-lg bg-[#0F2537] group-hover:bg-[#0A1E31] flex items-center justify-center text-white shadow-sm flex-shrink-0 transition">
                        <i class="fa-solid fa-hand-holding-dollar text-sm" aria-hidden="true"></i>
                    </div>
                    <span class="text-lg sm:text-xl font-bold tracking-tight text-[#0F2537] truncate font-heading">SponsorFlow</span>
                </a>

                {{-- Auth Buttons --}}
                <div class="flex items-center gap-2.5 sm:gap-3 flex-shrink-0">
                    @auth
                        <a href="{{ route('dashboard') }}"
                            class="px-4 py-2 text-xs sm:text-sm font-bold text-white sf-btn-primary bg-[#0F2942] hover:bg-[#173B57] rounded-lg shadow-sm transition inline-flex items-center gap-1.5 whitespace-nowrap">
                            <span>Dashboard</span>
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                            class="px-3 sm:px-4 py-2 text-xs sm:text-sm font-semibold text-slate-600 hover:text-[#0F2942] hover:bg-slate-100 rounded-lg transition whitespace-nowrap">
                            Log In
                        </a>
                        <a href="{{ route('register') }}"
                            class="px-3.5 sm:px-5 py-2 text-xs sm:text-sm font-bold text-white sf-btn-primary bg-[#0F2942] hover:bg-[#173B57] rounded-lg shadow-sm transition whitespace-nowrap">
                            Apply Now
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- Hero Section --}}
    <section class="bg-[#0F2537] text-white py-16 sm:py-20 px-4 sm:px-6 lg:px-8 relative overflow-hidden" aria-labelledby="hero-title">
        <div class="sf-landing-container max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-center">

            {{-- Left Content --}}
            <div class="lg:col-span-7 space-y-6">
                <div>
                    <span
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold tracking-wider uppercase bg-white/10 text-white border border-white/20 shadow-sm backdrop-blur-sm">
                        <i class="bi bi-mortarboard text-amber-300" aria-hidden="true"></i>
                        Davao Oriental State University
                    </span>
                </div>
                <h1 id="hero-title" class="text-4xl sm:text-5xl font-black tracking-tight leading-tight text-white">
                    Sponsorship support,<br class="hidden sm:inline" /> made clear.
                </h1>
                <p class="text-base sm:text-lg text-slate-300 max-w-xl font-normal leading-relaxed">
                    One accountable workflow connecting students, FASSG reviewers, sponsors, and Accounting from
                    application to confirmed support.
                </p>
                <div class="pt-2 flex flex-wrap items-center gap-4">
                    <a href="{{ route('register') }}"
                        title="Create an account to begin student verification"
                        class="inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-bold text-slate-900 bg-white hover:bg-slate-100 rounded-lg shadow-md transition duration-150">
                        <span>Verify SLE-FHE Status</span>
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                    <a href="#programs"
                        class="inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-semibold text-slate-200 hover:text-white bg-white/10 hover:bg-white/20 backdrop-blur-md rounded-lg transition duration-150 border border-white/25">
                        <span>Explore Programs</span>
                        <i class="bi bi-arrow-down" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            {{-- Right Feature Box --}}
            <div
                class="lg:col-span-5 bg-white/5 border border-white/15 backdrop-blur-md rounded-2xl p-6 sm:p-8 space-y-6 shadow-2xl">
                <div class="flex gap-4 items-start">
                    <div
                        class="w-11 h-11 rounded-xl bg-amber-400/20 text-amber-300 border border-amber-400/30 flex items-center justify-center text-lg flex-shrink-0">
                        <i class="bi bi-shield-check" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-white text-sm">Built for fair review</h4>
                        <p class="text-xs text-slate-300 mt-1 leading-relaxed">Clear criteria, verified records, human decisions, and an audit trail.</p>
                    </div>
                </div>

                <div class="flex gap-4 items-start">
                    <div
                        class="w-11 h-11 rounded-xl bg-amber-400/20 text-amber-300 border border-amber-400/30 flex items-center justify-center text-lg flex-shrink-0">
                        <i class="bi bi-diagram-3" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-white text-sm">One connected workflow</h4>
                        <p class="text-xs text-slate-300 mt-1 leading-relaxed">Every handoff stays visible from student submission to sponsor confirmation.</p>
                    </div>
                </div>

                <div class="flex gap-4 items-start">
                    <div
                        class="w-11 h-11 rounded-xl bg-amber-400/20 text-amber-300 border border-amber-400/30 flex items-center justify-center text-lg flex-shrink-0">
                        <i class="bi bi-person-badge" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-white text-sm">Role-aware by design</h4>
                        <p class="text-xs text-slate-300 mt-1 leading-relaxed">Each portal exposes only the tools and records its team needs.</p>
                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- Active Sponsorship Programs Section --}}
    <section id="programs" class="sf-landing-container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20" aria-labelledby="programs-heading">
        <div class="mb-8 border-b border-slate-200/90 pb-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-[#0F2942]">Open Opportunities</span>
                <h2 id="programs-heading" class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">Active sponsorship programs</h2>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($programs as $program)
                @php
                    $catVal = is_string($program->category) ? $program->category : ($program->category?->value ?? 'General');
                    $isGroup = $catVal === 'Group';
                    $isEmployee = $catVal === 'Employee-Based';
                    $programName = $program->program_name ?? $program->name ?? $program->title ?? 'Sponsorship Opportunity';
                    $sponsorName = $program->sponsor?->name ?? 'Institutional Sponsorship Partner';
                    $slots = $program->available_slots ?? 0;
                @endphp
                <div
                    class="sf-program-card bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between h-full">
                    <div>
                        {{-- Top Badges: Category & Available Slots --}}
                        <div class="flex justify-between items-center gap-2 mb-4">
                            @if ($isGroup)
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200/60">
                                    <i class="bi bi-people text-[11px]" aria-hidden="true"></i>
                                    {{ $catVal }}
                                </span>
                            @elseif ($isEmployee)
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                    <i class="bi bi-person-badge text-[11px]" aria-hidden="true"></i>
                                    {{ $catVal }}
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200/80">
                                    <i class="bi bi-person text-[11px]" aria-hidden="true"></i>
                                    {{ $catVal }}
                                </span>
                            @endif

                            <span
                                class="inline-flex items-center gap-1 text-xs font-semibold text-amber-800 bg-amber-50 px-2.5 py-1 rounded-md border border-amber-200/80">
                                <i class="bi bi-check-circle text-[11px] text-amber-600" aria-hidden="true"></i>
                                {{ $slots }} {{ $slots === 1 ? 'slot' : 'slots' }}
                            </span>
                        </div>

                        {{-- Prominent Sponsorship Program Name --}}
                        <h3 class="text-base sm:text-lg font-bold text-slate-900 mb-2 leading-snug line-clamp-2" title="{{ $programName }}">
                            {{ $programName }}
                        </h3>

                        {{-- Sponsor / Organization Name --}}
                        <div class="flex items-center gap-1.5 text-xs font-medium text-slate-500 mb-6">
                            <i class="bi bi-building text-slate-400 text-xs flex-shrink-0" aria-hidden="true"></i>
                            <span class="truncate" title="{{ $sponsorName }}">{{ $sponsorName }}</span>
                        </div>
                    </div>

                    {{-- Bottom Action Button --}}
                    <a href="{{ route('login') }}"
                        class="w-full py-2.5 px-4 bg-[#0F2942] hover:bg-[#173B57] text-white text-xs sm:text-sm font-semibold rounded-lg text-center shadow-sm transition inline-flex items-center justify-center gap-1.5 mt-auto">
                        <span>View Eligibility</span>
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            @empty
                <div
                    class="col-span-full bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-500 text-sm font-medium">
                    <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-xl">
                        <i class="bi bi-inbox" aria-hidden="true"></i>
                    </div>
                    <h4 class="font-bold text-slate-800 text-base mb-1">No Active Sponsorship Programs Found</h4>
                    <p class="max-w-md mx-auto text-xs sm:text-sm text-slate-500">
                        Applications open periodically based on sponsor availability, available funds, and verification quotas managed by FASSG.
                    </p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- Sponsorship Category Highlights --}}
    <section class="sf-landing-container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20" aria-labelledby="categories-heading">
        <div class="mb-8 border-b border-slate-200/90 pb-4">
            <span class="text-xs font-bold uppercase tracking-wider text-[#0F2942]">Program Pathways</span>
            <h2 id="categories-heading" class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">Sponsorship categories</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <article class="sf-category-card bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col h-full">
                <div class="w-11 h-11 mb-5 rounded-xl bg-blue-50 text-blue-700 border border-blue-200/60 flex items-center justify-center text-lg">
                    <i class="bi bi-people" aria-hidden="true"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-2">Group Sponsorship</h3>
                <p class="text-sm leading-relaxed text-slate-600">
                    Open programs with criteria filtered by verified SLE-FHE status, academic program, GPA, and location requirements.
                </p>
            </article>
            <article class="sf-category-card bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col h-full">
                <div class="w-11 h-11 mb-5 rounded-xl bg-amber-50 text-amber-700 border border-amber-200/60 flex items-center justify-center text-lg">
                    <i class="bi bi-person" aria-hidden="true"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-2">Individual Sponsorship</h3>
                <p class="text-sm leading-relaxed text-slate-600">
                    Direct sponsor agreements reviewed by FASSG and administered according to donor-specified guidelines.
                </p>
            </article>
            <article class="sf-category-card bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col h-full">
                <div class="w-11 h-11 mb-5 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200/60 flex items-center justify-center text-lg">
                    <i class="bi bi-person-badge" aria-hidden="true"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-2">Employee-Based Sponsorship</h3>
                <p class="text-sm leading-relaxed text-slate-600">
                    Institutional education grants designated for qualified dependents of university personnel.
                </p>
            </article>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="mt-auto bg-[#0F2537] border-t border-slate-800 text-slate-400 py-10 px-4 text-center text-xs" aria-label="Page Footer">
        <div class="sf-landing-container max-w-7xl mx-auto space-y-3">
            <div class="flex items-center justify-center gap-2 text-slate-300 font-semibold text-sm">
                <div class="w-6 h-6 rounded-md bg-white/10 flex items-center justify-center text-white">
                    <i class="fa-solid fa-hand-holding-dollar text-xs" aria-hidden="true"></i>
                </div>
                <span class="font-heading">SponsorFlow · Davao Oriental State University</span>
            </div>
            <p class="text-slate-400">
                Financial Assistance, Scholarship & Grants (FASSG) Office · City of Mati, Davao Oriental
            </p>
            <p class="text-slate-500 pt-1">
                &copy; {{ date('Y') }} Davao Oriental State University. All rights reserved.
            </p>
        </div>
    </footer>

</body>

</html>
