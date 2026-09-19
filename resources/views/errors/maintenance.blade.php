<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Under Maintenance · SponsorFlow | DORSU</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        h1, h2, h3, .font-heading {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        }
    </style>
</head>

<body class="min-h-screen bg-slate-50 flex flex-col justify-between text-slate-800 antialiased selection:bg-amber-100 selection:text-amber-900">

    {{-- Top Simple Brand Bar --}}
    <header class="w-full bg-white/80 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-10">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="{{ Route::has('landing') ? route('landing') : url('/') }}" class="flex items-center gap-2.5 text-decoration-none group">
                <div class="w-9 h-9 rounded-lg bg-[#0f294a] flex items-center justify-center text-white shadow-sm transition-transform group-hover:scale-105">
                    <i class="fa-solid fa-hand-holding-dollar text-sm"></i>
                </div>
                <span class="text-lg font-bold tracking-tight text-[#0f294a] font-heading">SponsorFlow</span>
            </a>

            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200/60">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    Maintenance Mode
                </span>
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main class="flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-8">
        <div class="w-full max-w-lg">
            <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/60 border border-slate-200/80 p-8 sm:p-10 text-center relative overflow-hidden">
                
                {{-- Decorative top accent line --}}
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-amber-400 via-amber-500 to-[#0f294a]"></div>

                {{-- Status Icon --}}
                <div class="mx-auto mb-6 w-20 h-20 rounded-2xl bg-amber-50 border border-amber-200/80 flex items-center justify-center text-amber-600 shadow-inner">
                    <i class="fa-solid fa-screwdriver-wrench text-3xl"></i>
                </div>

                {{-- Heading --}}
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0f294a] tracking-tight mb-3 font-heading">
                    System Under Maintenance
                </h1>

                {{-- Message --}}
                <p class="text-slate-600 text-sm sm:text-base leading-relaxed mb-8">
                    SponsorFlow is currently undergoing scheduled maintenance to improve services. Please check back shortly or contact the FASSG office for urgent inquiries.
                </p>

                {{-- Actions --}}
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="{{ Route::has('landing') ? route('landing') : url('/') }}"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl text-sm font-bold text-white bg-[#0f294a] hover:bg-[#0a1b30] shadow-md shadow-slate-900/10 hover:shadow-lg transition-all transform active:scale-95">
                        <i class="bi bi-house-door text-base"></i>
                        <span>Back to Home</span>
                    </a>
                </div>

                {{-- Quick Info Box --}}
                <div class="mt-8 pt-6 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
                    <div class="flex items-center gap-1.5">
                        <i class="bi bi-building text-slate-400"></i>
                        <span>DORSu FASSG Office</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <i class="bi bi-clock text-slate-400"></i>
                        <span>Service will resume shortly</span>
                    </div>
                </div>

            </div>

            {{-- Subtle Admin Gate link --}}
            <div class="text-center mt-6">
                <a href="{{ url(config('app.admin_login_path', 'dorsu-sysadmin-gate')) }}"
                    class="text-xs text-slate-400 hover:text-slate-600 transition inline-flex items-center gap-1">
                    <i class="bi bi-shield-lock"></i>
                    <span>Administrator Access</span>
                </a>
            </div>
        </div>
    </main>

    {{-- Simple Footer --}}
    <footer class="w-full py-4 text-center text-xs text-slate-500 border-t border-slate-200/60 bg-white">
        <p>&copy; {{ date('Y') }} Davao Oriental State University · Financial Assistance &amp; Scholarships Services Group</p>
    </footer>

</body>

</html>
