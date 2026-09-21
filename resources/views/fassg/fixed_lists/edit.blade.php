@extends('layouts.app')

@section('title', 'Rename Fixed List')
@section('eyebrow', 'FASSG Office')
@section('page-title', 'Rename Fixed List')

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
                    <h3 class="h6 sf-heading mb-3">Rename Fixed List</h3>
                    <p class="text-secondary small">Update the batch name for
                        {{ $fixedList->sponsorshipProgram->program_name }}.</p>
                    <form method="POST" action="{{ route('fassg.fixed-lists.update', $fixedList) }}" class="mt-4">
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
                            <a href="{{ $backUrl }}" class="btn btn-outline-danger"><i class="bi bi-x-lg me-1"></i> Cancel</a>
                            <button type="submit" class="btn btn-sf-navy"><i class="bi bi-check2 me-1"></i>Save
                                Name</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
