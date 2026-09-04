<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SponsorFlow | DORSu</title>
    {{-- CDN Fallback to bypass Vite build errors --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>

<body class="min-h-screen flex flex-col bg-slate-50 text-slate-800 font-sans antialiased">

    {{-- Navigation Bar --}}
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                {{-- Brand Logo & Name --}}
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 bg-[#002B66] rounded-xl flex items-center justify-center text-[#FFC72C] font-extrabold text-xl shadow-md border border-[#FFC72C]/30">
                        S
                    </div>
                    <span class="text-xl font-black text-[#002B66] tracking-tight">
                        Sponsor<span class="text-[#FFC72C]">Flow</span>
                    </span>
                </div>

                {{-- Auth Buttons --}}
                <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}"
                            class="px-5 py-2 text-sm font-bold text-[#002B66] bg-[#FFC72C] hover:bg-amber-400 rounded-lg shadow transition">
                            Go to Dashboard &rarr;
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                            class="px-4 py-2 text-sm font-semibold text-slate-700 hover:text-[#002B66] transition">
                            Log in
                        </a>
                        <a href="{{ route('register') }}"
                            class="px-5 py-2 text-sm font-bold text-white bg-[#002B66] hover:bg-[#001f4d] rounded-lg shadow transition border border-indigo-950">
                            Apply now
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- Hero Section --}}
    <section
        class="bg-gradient-to-b from-[#00193d] via-[#002B66] to-[#001e47] text-white py-20 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
        <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">

            {{-- Left Content --}}
            <div class="lg:col-span-7 space-y-6">
                <span
                    class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-bold tracking-wider uppercase bg-[#FFC72C]/15 text-[#FFC72C] border border-[#FFC72C]/30">
                    Davao Oriental State University
                </span>
                <h1 class="text-4xl sm:text-5xl font-black tracking-tight leading-tight text-white">
                    Sponsorship support, <br />made clear.
                </h1>
                <p class="text-base sm:text-lg text-slate-300 max-w-xl font-normal leading-relaxed">
                    One accountable workflow connecting students, FASSG reviewers, sponsors, and Accounting from
                    application to confirmed support.
                </p>
                <div class="pt-2 flex flex-wrap gap-4">
                    <a href="{{ route('register') }}"
                        class="px-6 py-3 text-sm font-bold text-[#002B66] bg-[#FFC72C] hover:bg-amber-400 rounded-xl shadow-lg transition">
                        ⚡ Verify SLE-FHE status
                    </a>
                    <a href="#programs"
                        class="inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold text-slate-200 hover:text-white bg-white/10 hover:bg-white/20 backdrop-blur-md rounded-xl transition border border-white/10">
                        Explore programs <i class="bi bi-arrow-down" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            {{-- Right Feature Box --}}
            <div
                class="lg:col-span-5 bg-white/5 border border-white/15 backdrop-blur-md rounded-2xl p-6 sm:p-8 space-y-6 shadow-2xl">
                <div class="flex gap-4 items-start">
                    <div class="p-2.5 rounded-xl bg-[#FFC72C]/20 text-[#FFC72C] font-bold">
                        ✓
                    </div>
                    <div>
                        <h4 class="font-bold text-white text-sm">Built for fair review</h4>
                        <p class="text-xs text-slate-300 mt-1">Clear criteria, verified records, human decisions, and an
                            audit trail.</p>
                    </div>
                </div>

                <div class="flex gap-4 items-start">
                    <div class="p-2.5 rounded-xl bg-[#FFC72C]/20 text-[#FFC72C] font-bold">
                        ☍
                    </div>
                    <div>
                        <h4 class="font-bold text-white text-sm">One connected workflow</h4>
                        <p class="text-xs text-slate-300 mt-1">Every handoff stays visible from student submission to
                            sponsor confirmation.</p>
                    </div>
                </div>

                <div class="flex gap-4 items-start">
                    <div class="p-2.5 rounded-xl bg-[#FFC72C]/20 text-[#FFC72C] font-bold">
                        🔒
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
                <span class="text-xs font-bold uppercase tracking-wider text-[#002B66]">Open Opportunities</span>
                <h2 class="text-2xl font-black text-slate-900 mt-1">Active scholarship programs</h2>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @forelse($programs as $program)
                <div
                    class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-sm hover:shadow-md transition flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-center mb-4">
                            <span
                                class="px-2.5 py-1 rounded-md text-xs font-bold bg-blue-50 text-[#002B66] border border-blue-100">
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
                        class="w-full py-2.5 px-4 bg-[#002B66] hover:bg-[#001f4d] text-white text-xs font-bold rounded-xl text-center shadow transition block">
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
            <span class="text-xs font-bold uppercase tracking-wider text-[#002B66]">Program pathways</span>
            <h2 class="text-2xl font-black text-slate-900 mt-1">Sponsorship categories</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <article class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="w-11 h-11 mb-5 rounded-xl bg-blue-50 text-[#002B66] flex items-center justify-center">
                    <i class="bi bi-people-fill text-lg" aria-hidden="true"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-2">Group Sponsorship</h3>
                <p class="text-sm leading-relaxed text-slate-500">Apply to open programs filtered by academic grades,
                    address, rurality, and course.</p>
            </article>
            <article class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="w-11 h-11 mb-5 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center">
                    <i class="bi bi-person-heart text-lg" aria-hidden="true"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-2">Individual Sponsorship</h3>
                <p class="text-sm leading-relaxed text-slate-500">Recorded and monitored for prospective sponsor
                    matching.</p>
            </article>
            <article class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="w-11 h-11 mb-5 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
                    <i class="bi bi-building-check text-lg" aria-hidden="true"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-2">Employee-Based Sponsorship</h3>
                <p class="text-sm leading-relaxed text-slate-500">Qualified grants for dependents of institutional
                    personnel.</p>
            </article>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="mt-auto bg-[#00193d] border-t border-slate-800 text-slate-400 py-8 text-center text-xs font-medium">
        <p>&copy; {{ date('Y') }} Davao Oriental State University – SponsorFlow. All rights reserved.</p>
    </footer>

</body>

</html>
