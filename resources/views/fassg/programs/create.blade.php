@extends('layouts.app')

@section('title', 'Create Program')
@section('eyebrow', 'FASSG Office · Programs')
@section('page-title', 'Create Sponsorship Program')
@section('subtitle', 'Configure program details, eligibility filtering criteria, and applicant submission guidelines.')

@push('styles')
    @include('fassg.partials.programs-styles')
@endpush

@section('content')
    <div class="sf-programs-page">
        <div class="sfp-form-container">
            @include('fassg.programs._form', [
                'action' => route('fassg.programs.store'),
                'method' => 'POST',
                'submitLabel' => 'Create & Open Program',
                'approvedCount' => 0,
            ])
        </div>
    </div>
@endsection
