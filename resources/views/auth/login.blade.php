<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login | SponsorFlow - DORSU</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear {
            display: none !important;
        }
    </style>
</head>

<body class="min-h-screen bg-slate-50 px-4 py-10 font-sans antialiased sm:px-6 lg:px-8">
    <main class="flex min-h-[calc(100vh-5rem)] items-center justify-center">
        <div class="w-full max-w-md">
            <header class="mb-6 text-center">
                <div
                    class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full border-2 border-[#FFC72C] bg-white p-1 text-sm font-black tracking-tighter text-[#002B66] shadow-md">
                    DORSu
                </div>
                <h1 class="text-2xl font-black tracking-tight text-[#002B66]">SponsorFlow</h1>
                <p class="mt-1 text-xs font-semibold uppercase tracking-widest text-slate-500">Davao Oriental State
                    University · SLE-FHE</p>
            </header>

            <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xl sm:p-8">
                <div class="mb-6 text-center">
                    <p class="mb-2 text-xs font-bold uppercase tracking-wider text-blue-600">Student portal</p>
                    <h2 class="text-xl font-bold text-slate-900">Welcome back</h2>
                    <p class="mt-1 text-sm text-slate-500">Sign in with your institutional DORSU account.</p>
                </div>

                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                        role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label for="email"
                            class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Student
                            email</label>
                        <input type="email" name="email" id="email" required autofocus
                            value="{{ old('email') }}" autocomplete="email" placeholder="student@dorsu.edu.ph"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-[#002B66] focus:ring-2 focus:ring-[#002B66]/20">
                    </div>

                    <div>
                        <label for="password"
                            class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Password</label>
                        <div class="relative">
                            <input type="password" name="password" id="student-password-input" required
                                autocomplete="current-password" placeholder="Password"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 pr-12 text-sm outline-none transition focus:border-[#002B66] focus:ring-2 focus:ring-[#002B66]/20">
                            <button type="button" id="toggle-student-password-btn"
                                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 transition hover:text-slate-600"
                                aria-label="Toggle password visibility">
                                <svg class="show-icon h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remember" value="1"
                            class="h-4 w-4 rounded border-slate-300 text-[#002B66] focus:ring-[#002B66]">
                        Remember me
                    </label>

                    <button type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#002B66] px-4 py-3 text-sm font-bold text-white shadow-md transition hover:bg-[#001f4d]">
                        <span>Log in</span>
                        <span aria-hidden="true">&rarr;</span>
                    </button>
                </form>

                <p class="mt-5 text-center text-sm text-slate-500">New SLE-FHE student?
                    <a href="{{ route('register') }}" class="font-bold text-blue-600 hover:text-blue-800">Register
                        here</a>
                </p>
            </section>

            <p class="mt-6 text-center text-xs leading-relaxed text-slate-400">
                Use your DORSU email. SLE-FHE eligibility is verified by FASSG prior to application processing.
            </p>
        </div>
    </main>

    <script>
        const passwordInput = document.getElementById('student-password-input');
        const toggleButton = document.getElementById('toggle-student-password-btn');
        if (passwordInput && toggleButton) {
            toggleButton.addEventListener('click', function() {
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                toggleButton.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            });
        }
    </script>
</body>

</html>
