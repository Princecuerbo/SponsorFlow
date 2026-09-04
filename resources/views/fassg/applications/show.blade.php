@extends('layouts.app')

@section('title', 'Review application')

@section('nav')
    <a href="{{ route('fassg.applications.index') }}">Applicants</a>
@endsection

@section('content')
    <h1>Verify {{ $application->studentProfile->user->name }}</h1>
    <p>Program: {{ $application->sponsorshipProgram->program_name }}</p>
    <p>Status: {{ $application->status->value }}
        @if ($application->status === \App\Enums\ApplicationStatus::Approved)
            <small class="text-muted d-block">Confirmed
                {{ $application->approved_at?->format('M d, Y') ?? ($application->updated_at?->format('M d, Y') ?? '—') }}</small>
        @endif
    </p>
    <p>GWA: {{ $application->gpa_submitted }} · Address: {{ $application->address_submitted }} · Rural:
        {{ $application->is_rural_submitted ? 'Yes' : 'No' }}</p>
    <p>Student ID: {{ $application->studentProfile->student_id_number }} · SLE-FHE:
        {{ $application->studentProfile->is_sle_fhe_verified ? 'Verified' : 'No' }}</p>

    @if ($eligibilityErrors)
        <ul class="errors">
            @foreach ($eligibilityErrors as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <h2>Documents</h2>
    <ul>
        @foreach ($application->documents as $document)
            <li>
                {{ $document->document_type->label() }}:
                <a
                    href="{{ route('fassg.applications.documents.download', [$application, $document]) }}">{{ $document->file_name }}</a>
            </li>
        @endforeach
    </ul>

    @if ($application->status->value === 'Pending')
        <form method="POST" action="{{ route('fassg.applications.verify', $application) }}">
            @csrf
            @method('PATCH')
            <label><input type="checkbox" name="grades_verified" value="1" required> Grade slip matches submitted
                GWA</label>
            <label><input type="checkbox" name="address_verified" value="1" required> Proof of residence and barangay
                certificate match the address</label>
            <button type="submit">Mark Verified</button>
        </form>
        <form method="POST" action="{{ route('fassg.applications.reject', $application) }}">
            @csrf
            @method('PATCH')
            <label>Rejection reason
                <input name="reason">
            </label>
            <button type="submit">Reject</button>
        </form>
    @endif
@endsection
