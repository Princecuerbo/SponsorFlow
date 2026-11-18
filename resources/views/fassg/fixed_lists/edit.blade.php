@extends('layouts.app')

@section('title', 'Rename Fixed List')
@section('eyebrow', 'FASSG Office · Fixed Lists · Rename')
@section('page-title', 'Rename Fixed List')

@push('styles')
    @include('fassg.partials.fixed-lists-styles')
@endpush

@section('content')
    @php
        $fallbackUrl = route('fassg.fixed-lists.show', $fixedList->id);
        $previousUrl = url()->previous();
        $backUrl = old('redirect_to', $previousUrl !== request()->url() ? $previousUrl : $fallbackUrl);
    @endphp

    <div class="sf-fixed-list-edit-page">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card sf-card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <p class="text-secondary small mb-4">
                            Update the display name of this fixed list for <strong>{{ $fixedList->sponsorshipProgram->program_name }}</strong>.
                        </p>
                        <form method="POST" action="{{ route('fassg.fixed-lists.update', $fixedList) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="redirect_to" value="{{ $backUrl }}">

                            <div class="mb-4">
                                <label class="form-label" for="batch_name">Fixed List Name <span class="text-danger">*</span></label>
                                <input type="text" id="batch_name" name="batch_name"
                                    value="{{ old('batch_name', $fixedList->batch_name) }}"
                                    class="form-control @error('batch_name') is-invalid @enderror" maxlength="150" required
                                    autofocus placeholder="e.g. Governor Endorsed Candidates 2026">
                                @error('batch_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ $backUrl }}" class="btn btn-outline-secondary btn-sm fw-semibold px-3">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-sf-navy btn-sm fw-semibold px-3 d-inline-flex align-items-center gap-1.5">
                                    <i class="bi bi-check2"></i>Save Name
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
