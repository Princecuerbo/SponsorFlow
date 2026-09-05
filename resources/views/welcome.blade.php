<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SponsorFlow | DORSu</title>
    {{-- CDN Fallback to bypass Vite build errors --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>

<body class="min-h-screen flex flex-col bg-slate-50 text-slate-800 font-sans antialiased">

    {{-- Navigation Bar --}}
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center gap-2">
                {{-- Brand Logo & Name --}}
                <a href="/" class="flex items-center gap-2 text-decoration-none min-w-0">
                    <!-- Navy Badge with hand-holding-dollar icon -->
                    <div
                        class="w-9 h-9 rounded-lg bg-[#0f294a] flex items-center justify-center text-white shadow-sm flex-shrink-0">
                        <i class="fa-solid fa-hand-holding-dollar text-sm"></i>
                    </div>
                    <!-- Brand Name -->
                    <span class="text-lg sm:text-xl font-bold tracking-tight text-[#0f294a] truncate">SponsorFlow</span>
                </a>

                {{-- Auth Buttons --}}
                <div class="flex items-center gap-2 sm:gap-4 flex-shrink-0">
                    @auth
                        <a href="{{ route('dashboard') }}"
                            class="px-3 sm:px-5 py-2 text-xs sm:text-sm font-bold text-white bg-[#0f294a] hover:bg-[#0a1b30] rounded-lg shadow transition whitespace-nowrap">
                            Dashboard &rarr;
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                            class="px-2 sm:px-4 py-2 text-xs sm:text-sm font-semibold text-slate-600 hover:text-[#0f294a] transition whitespace-nowrap">
                            Log in
                        </a>
                        <a href="{{ route('register') }}"
                            class="px-3 sm:px-5 py-2 text-xs sm:text-sm font-bold text-white bg-[#0f294a] hover:bg-[#0a1b30] rounded-lg shadow transition whitespace-nowrap">
                            Apply now
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- Hero Section --}}
    <section class="bg-[#0f294a] text-white py-20 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">

            {{-- Left Content --}}
            <div class="lg:col-span-7 space-y-6">
                <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold tracking-wider uppercase bg-white text-slate-900 border border-white/20 shadow-sm">
                    Davao Oriental State University
                </span>
                <h1 class="text-4xl sm:text-5xl font-black tracking-tight leading-tight text-white">
                    Sponsorship support, <br />made clear.
                </h1>
                <p class="text-base sm:text-lg text-slate-300 max-w-xl font-normal leading-relaxed">
                    One accountable workflow connecting students, FASSG reviewers, sponsors, and Accounting from
                    application to confirmed support.
                </p>
                <div class="pt-2 flex flex-wrap items-center gap-4">
                    <a href="{{ route('register') }}"
                        class="inline-flex items-center justify-center px-6 py-3 text-sm font-bold text-slate-900 bg-white hover:bg-slate-100 rounded-lg shadow-lg transition">
                        Verify SLE-FHE status
                    </a>
                    <a href="#programs"
                        class="inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-semibold text-slate-200 hover:text-white bg-white/10 hover:bg-white/20 backdrop-blur-md rounded-lg transition border border-white/25">
                        Explore programs <i class="bi bi-arrow-down" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            {{-- Right Feature Box --}}
            <div
                class="lg:col-span-5 bg-white/5 border border-white/15 backdrop-blur-md rounded-2xl p-6 sm:p-8 space-y-6 shadow-2xl">
                <div class="flex gap-4 items-start">
                    <div
                        class="p-2.5 rounded-xl bg-amber-400/20 text-amber-300 flex items-center justify-center w-10 h-10 flex-shrink-0">
                        <i class="fa-solid fa-check text-base"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-white text-sm">Built for fair review</h4>
                        <p class="text-xs text-slate-300 mt-1">Clear criteria, verified records, human decisions, and an
                            audit trail.</p>
                    </div>
                </div>

                <div class="flex gap-4 items-start">
                    <div
                        class="p-2.5 rounded-xl bg-amber-400/20 text-amber-300 flex items-center justify-center w-10 h-10 flex-shrink-0">
                        <i class="fa-solid fa-network-wired text-base"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-white text-sm">One connected workflow</h4>
                        <p class="text-xs text-slate-300 mt-1">Every handoff stays visible from student submission to
                            sponsor confirmation.</p>
                    </div>
                </div>

                <div class="flex gap-4 items-start">
                    <div
                        class="p-2.5 rounded-xl bg-amber-400/20 text-amber-300 flex items-center justify-center w-10 h-10 flex-shrink-0">
                        <i class="fa-solid fa-user-lock text-base"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-white text-sm">Role-aware by design</h4>
                        <p class="text-xs text-slate-300 mt-1">Each portal exposes only the tools and records its team
                            needs.</p>
                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- Active Scholarship Programs Section --}}
    <section id="programs" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="mb-8 border-b border-slate-200 pb-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-[#0f294a]">Open Opportunities</span>
                <h2 class="text-2xl font-black text-slate-900 mt-1">Active scholarship programs</h2>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @forelse($programs as $program)
                <div
                    class="bg-white rounded-3 border border-slate-200 p-6 shadow-sm hover:shadow-md transition flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-center mb-4">
                            <span
                                class="px-2.5 py-1 rounded-md text-xs font-bold bg-blue-50 text-[#0f294a] border border-blue-100">
                                {{ $program->category ?? 'General' }}
                            </span>
                            <span
                                class="text-xs font-bold text-amber-700 bg-amber-50 px-2.5 py-1 rounded-md border border-amber-200">
                                {{ $program->available_slots ?? 0 }} slots
                            </span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-1 line-clamp-2">{{ $program->title }}</h3>
                        <p class="text-xs font-medium text-slate-500 mb-4">
                            {{ $program->sponsor->name ?? 'Davao Oriental Community Foundation' }}</p>
                    </div>

                    <a href="{{ route('login') }}"
                        class="w-full py-2.5 px-4 bg-[#0f294a] hover:bg-[#0a1b30] text-white text-xs font-bold rounded-xl text-center shadow transition block">
                        View eligibility &rarr;
                    </a>
                </div>
            @empty
                <div
                    class="col-span-full bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-500 text-sm font-medium">
                    No active scholarship programs are currently listed. Applications open periodically based on sponsor
                    availability, available funds, and slots managed by FASSG.
                </div>
            @endforelse
        </div>
    </section>

    {{-- Sponsorship Category Highlights --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
        <div class="mb-8">
            <span class="text-xs font-bold uppercase tracking-wider text-[#0f294a]">Program pathways</span>
            <h2 class="text-2xl font-black text-slate-900 mt-1">Sponsorship categories</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <article class="bg-white rounded-3 border border-slate-200 p-6 shadow-sm hover:shadow-md transition">
                <div class="w-11 h-11 mb-5 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i class="fa-solid fa-people-group text-lg" aria-hidden="true"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-2">Group Sponsorship</h3>
                <p class="text-sm leading-relaxed text-slate-500">Apply to open programs filtered by academic grades,
                    address, rurality, and course.</p>
            </article>
            <article class="bg-white rounded-3 border border-slate-200 p-6 shadow-sm hover:shadow-md transition">
                <div class="w-11 h-11 mb-5 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i class="fa-solid fa-user text-lg" aria-hidden="true"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-2">Individual Sponsorship</h3>
                <p class="text-sm leading-relaxed text-slate-500">Recorded and monitored for prospective sponsor
                    matching.</p>
            </article>
            <article class="bg-white rounded-3 border border-slate-200 p-6 shadow-sm hover:shadow-md transition">
                <div class="w-11 h-11 mb-5 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i class="fa-solid fa-user-tie text-lg" aria-hidden="true"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-2">Employee-Based Sponsorship</h3>
                <p class="text-sm leading-relaxed text-slate-500">Qualified grants for dependents of institutional
                    personnel.</p>
            </article>
        </div>
    </section>

    {{-- Footer --}}
    <footer
        class="mt-auto bg-[#0f294a] border-t border-slate-800 text-slate-400 py-8 px-4 text-center text-xs font-medium">
        <p class="max-w-7xl mx-auto">&copy; {{ date('Y') }} Davao Oriental State University – SponsorFlow. All
            rights reserved.</p>
    </footer>

</body>

</html>
