@extends('layouts.app')

@section('title', 'Edit program')
@section('page-title', 'Edit Sponsorship Program')

@section('nav')
    <a href="{{ route('fassg.programs.index') }}">Programs</a>
@endsection

@section('content')
    <div class="container-fluid px-4 py-3">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0">Update {{ $program->program_name }}</h1>
            <a href="{{ route('fassg.programs.index') }}"
                class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> Back to Programs
            </a>
        </div>
        @include('fassg.programs._form', [
            'action' => route('fassg.programs.update', $program),
            'method' => 'PUT',
        ])
    </div>
@endsection
