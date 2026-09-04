@extends('layouts.app')

@section('title', 'Applicants')

@section('nav')
    <a href="{{ route('fassg.dashboard') }}">Dashboard</a>
    <a href="{{ route('fassg.programs.index') }}">Programs</a>
    <a href="{{ route('fassg.applications.index') }}">Applicants</a>
    <a href="{{ route('fassg.fixed-lists.index') }}">Fixed lists</a>
    <a href="{{ route('fassg.reports.index') }}">Reports</a>
@endsection

@section('content')
    <h1>Application review</h1>
    <form method="GET">
        <label>Status
            <select name="status" onchange="this.form.submit()">
                <option value="">All</option>
                @foreach (\App\Enums\ApplicationStatus::cases() as $case)
                    <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->value }}</option>
                @endforeach
            </select>
        </label>
    </form>
    <table>
        <thead>
            <tr>
                <th>Student</th>
                <th>Program</th>
                <th>GWA</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($applications as $application)
                <tr>
                    <td>{{ $application->studentProfile->user->name }}
                        ({{ $application->studentProfile->student_id_number }})</td>
                    <td>{{ $application->sponsorshipProgram->program_name }}</td>
                    <td>{{ $application->gpa_submitted }}</td>
                    <td>
                        <div>{{ $application->status->value }}</div>
                        @if ($application->status === \App\Enums\ApplicationStatus::Approved)
                            <small
                                class="text-muted">{{ $application->approved_at?->format('M d, Y') ?? ($application->updated_at?->format('M d, Y') ?? '—') }}</small>
                        @endif
                    </td>
                    <td><a href="{{ route('fassg.applications.show', $application) }}">Review</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No applications.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
