@php
    $items = auth()->user()?->role->navItems() ?? [];
@endphp

<nav class="nav flex-column gap-1">
    @if (auth()->user()->isStudent())
        <a href="{{ route('student.dashboard') }}"
            class="nav-link rounded-3 px-3 py-2 d-flex align-items-center gap-2 {{ request()->routeIs('student.dashboard') ? 'text-white' : 'text-dark' }}"
            @if (request()->routeIs('student.dashboard')) style="background:#0f4d3a;" @endif>
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('student.sle-fhe') }}"
            class="nav-link rounded-3 px-3 py-2 d-flex align-items-center gap-2 {{ request()->routeIs('student.sle-fhe') || request()->routeIs('student.verification.*') ? 'text-white' : 'text-dark' }}"
            @if (request()->routeIs('student.sle-fhe') || request()->routeIs('student.verification.*')) style="background:#0f4d3a;" @endif>
            <i class="bi bi-patch-check"></i>
            <span>SLE-FHE Verification</span>
        </a>
        <a href="{{ route('student.applications.index') }}"
            class="nav-link rounded-3 px-3 py-2 d-flex align-items-center gap-2 {{ request()->routeIs('student.applications.*') ? 'text-white' : 'text-dark' }}"
            @if (request()->routeIs('student.applications.*')) style="background:#0f4d3a;" @endif>
            <i class="bi bi-file-earmark-text"></i>
            <span>My Applications</span>
        </a>
        <a href="{{ route('student.programs.index') }}"
            class="nav-link rounded-3 px-3 py-2 d-flex align-items-center gap-2 {{ request()->routeIs('student.programs.*') ? 'text-white' : 'text-dark' }}"
            @if (request()->routeIs('student.programs.*')) style="background:#0f4d3a;" @endif>
            <i class="bi bi-briefcase"></i>
            <span>Browse Programs</span>
        </a>
    @else
        @foreach ($items as $item)
            <a href="{{ route($item['route']) }}"
                class="nav-link rounded-3 px-3 py-2 d-flex align-items-center gap-2 {{ request()->routeIs(str($item['route'])->beforeLast('.')->append('.*')->toString()) || request()->routeIs($item['route']) ? 'text-white' : 'text-dark' }}"
                @if (request()->routeIs(str($item['route'])->beforeLast('.')->append('.*')->toString()) ||
                        request()->routeIs($item['route'])) style="background:#0f4d3a;" @endif>
                <i class="bi {{ $item['icon'] }}"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    @endif
</nav>
