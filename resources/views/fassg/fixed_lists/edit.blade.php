@extends('layouts.app')

@section('title', 'Rename Fixed List')
@section('eyebrow', 'FASSG Office')
@section('page-title', 'Rename Fixed List')

@push('styles')
    <style>
        .btn-navy-primary,
        button.btn-navy-primary {
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
            color: #ffffff !important;
            font-weight: 600;
            box-shadow: none !important;
            transition: all 0.2s ease-in-out;
        }

        .btn-navy-primary:hover,
        .btn-navy-primary:focus,
        button.btn-navy-primary:hover,
        button.btn-navy-primary:focus {
            background-color: #0A1E31 !important;
            border-color: #0A1E31 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(15, 41, 66, 0.15) !important;
        }
    </style>
@endpush

@section('content')
    @php
        $fallbackUrl = route('fassg.fixed-lists.show', $fixedList->id);
        $previousUrl = url()->previous();
        $backUrl = old('redirect_to', $previousUrl !== request()->url() ? $previousUrl : $fallbackUrl);
    @endphp

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card sf-card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-1">Rename Fixed List</h2>
                    <p class="text-secondary small mb-4">Update the batch name for
                        {{ $fixedList->sponsorshipProgram->program_name }}.</p>

                    <form method="POST" action="{{ route('fassg.fixed-lists.update', $fixedList) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="redirect_to" value="{{ $backUrl }}">

                        <div class="mb-4">
                            <label class="form-label" for="batch_name">Batch Name <span class="text-danger">*</span></label>
                            <input type="text" id="batch_name" name="batch_name"
                                value="{{ old('batch_name', $fixedList->batch_name) }}"
                                class="form-control @error('batch_name') is-invalid @enderror" maxlength="150" required
                                autofocus>
                            @error('batch_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ $backUrl }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-navy-primary"><i class="bi bi-check2 me-1"></i>Save
                                Name</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
