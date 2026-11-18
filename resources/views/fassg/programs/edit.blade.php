@extends('layouts.app')

@section('title', 'Edit Program')
@section('eyebrow', 'FASSG Office · Programs')
@section('page-title', 'Update ' . $program->program_name)
@section('subtitle', 'Modify program criteria, application capacity, or student eligibility requirements.')

@push('styles')
    @include('fassg.partials.programs-styles')
@endpush

@section('header-actions')
    <a href="{{ route('fassg.programs.index') }}"
        class="btn btn-outline-secondary btn-sm sfp-back-btn no-print">
        <i class="bi bi-arrow-left me-1"></i> Back to Programs
    </a>
@endsection

@section('content')
    <div class="sf-programs-page">
        <div class="sfp-form-container">
            @include('fassg.programs._form', [
                'action' => route('fassg.programs.update', $program),
                'method' => 'PUT',
                'submitLabel' => 'Save Changes',
                'approvedCount' => $approvedCount ?? 0,
            ])
        </div>
    </div>
@endsection
