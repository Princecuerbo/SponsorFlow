@extends('layouts.app')

@section('title', 'Application status')

@section('content')
    @php $steps = ['Pending', 'Verified', 'Approved', 'Ongoing', 'Expired']; @endphp
    <div class="mb-4">
        <p class="text-uppercase small fw-semibold text-success mb-2">Your applications</p>
        <h1 class="display-6 fw-bold mb-1">Track your progress</h1>
        <p class="text-secondary mb-0">Follow each application from submission through sponsorship completion.</p>
    </div>
    @forelse ($applications as $application)
        @php
            $current = array_search($application->status->value, $steps, true);
            $current = $current === false ? -1 : $current;
        @endphp
        <section class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
                    <div>
                        <h2 class="h5 fw-bold mb-1">{{ $application->sponsorshipProgram->program_name }}</h2>
                        <p class="text-secondary small mb-0">Submitted
                            {{ $application->submitted_at?->format('M d, Y') ?? 'recently' }}</p>
                    </div><span
                        class="badge rounded-pill text-bg-success align-self-start px-3 py-2">{{ $application->status->value }}</span>
                </div>
                <div class="sf-timeline">
                    @foreach ($steps as $index => $step)
                        <div
                            class="sf-timeline__step {{ $index < $current ? 'is-complete' : '' }} {{ $index === $current ? 'is-current' : '' }}">
                            <div class="fs-4 mb-2"><i
                                    class="bi {{ $index < $current ? 'bi-check-circle-fill text-success' : ($index === $current ? 'bi-record-circle text-success' : 'bi-circle text-secondary') }}"></i>
                            </div><strong class="small">{{ $step }}</strong>
                            @if ($step === 'Verified' && $application->verified_at)
                                <div class="text-secondary small mt-1">{{ $application->verified_at->format('M d') }}</div>
                            @elseif ($step === 'Approved' && $application->approved_at)
                                <div class="text-secondary small mt-1">{{ $application->approved_at->format('M d') }}</div>
                            @endif
                        </div>
                    @endforeach
                </div><a class="btn btn-sm btn-outline-success mt-4"
                    href="{{ route('student.applications.show', $application) }}">View application details <i
                        class="bi bi-arrow-right ms-1"></i></a>
            </div>
        </section>
    @empty
        <div class="bg-white border rounded-4 p-5 text-center"><i class="bi bi-file-earmark-text text-secondary fs-1"></i>
            <h2 class="h5 mt-3">No applications yet</h2>
            <p class="text-secondary mb-3">Browse open sponsorship programs to get started.</p><a class="btn btn-success"
                href="{{ route('student.programs.index') }}">Browse programs</a>
        </div>
    @endforelse
@endsection
