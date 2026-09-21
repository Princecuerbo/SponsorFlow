@extends('layouts.app')

@section('title', 'Edit program')
@section('eyebrow', 'FASSG Office · Programs')
@section('page-title', 'Update ' . $program->program_name)

@section('header-actions')
    <a href="{{ route('fassg.programs.index') }}"
        class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i> Back to Programs
    </a>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            @include('fassg.programs._form', [
                'action' => route('fassg.programs.update', $program),
                'method' => 'PUT',
                'approvedCount' => $approvedCount ?? 0,
            ])
        </div>
    </div>
@endsection