@extends('layouts.app')

@section('title', 'Application status')
@section('eyebrow', 'Student Portal · Applications')
@section('page-title', 'Track your progress')
@section('subtitle', 'Follow each application from submission through sponsorship completion.')

@section('content')
    @php $steps = ['Pending', 'Verified', 'Approved', 'Ongoing', 'Expired']; @endphp
    @forelse ($applications as $application)
        @php
            $current = array_search($application->status->value, $steps, true);
            $current = $current === false ? -1 : $current;
        @endphp
        <section class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
                    <div>
                        <h3 class="h6 sf-heading mb-1">{{ $application->sponsorshipProgram->program_name }}</h3>
                        <p class="text-secondary small mb-0">Submitted
                            {{ $application->submitted_at?->format('M d, Y, h:i A') ?? 'recently' }}</p>
                    </div>
                    <x-status-badge :status="$application->status" class="align-self-start px-3 py-2 rounded-pill" />
                </div>
                <div class="sf-timeline">
                    @foreach ($steps as $index => $step)
                        <div
                            class="sf-timeline__step {{ $index < $current ? 'is-complete' : '' }} {{ $index === $current ? 'is-current' : '' }}">
                            <div class="fs-4 mb-2"><i
                                    class="bi {{ $index < $current ? 'bi-check-circle-fill text-success' : ($index === $current ? 'bi-record-circle text-success' : 'bi-circle text-secondary') }}"></i>
                            </div><strong class="small">{{ $step }}</strong>
                            @if ($step === 'Verified' && $application->verified_at)
                                <div class="text-secondary small mt-1">{{ $application->verified_at->format('M d, Y, h:i A') }}</div>
                            @elseif ($step === 'Approved' && $application->approved_at)
                                <div class="text-secondary small mt-1">{{ $application->approved_at->format('M d, Y, h:i A') }}</div>
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
            <h3 class="h6 sf-heading mb-3">No applications yet</h3>
            <p class="text-secondary mb-3">Browse open sponsorship programs to get started.</p><a class="btn btn-success"
                href="{{ route('student.programs.index') }}">Browse programs</a>
        </div>
    @endforelse
@endsection
