@extends('layouts.app')

@section('title', 'Encode Beneficiary List')
@section('eyebrow', 'FASSG Office')
@section('page-title', 'Encode Beneficiary List')

@push('styles')
    <style>
        .btn-navy-primary,
        a.btn-navy-primary,
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
        a.btn-navy-primary:hover,
        a.btn-navy-primary:focus,
        button.btn-navy-primary:hover,
        button.btn-navy-primary:focus {
            background-color: #0A1E31 !important;
            border-color: #0A1E31 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(15, 41, 66, 0.15) !important;
        }

        .btn-outline-navy {
            color: #0F2942 !important;
            border-color: #0F2942 !important;
            background-color: transparent !important;
            font-weight: 600;
            transition: all 0.2s ease-in-out;
        }

        .btn-outline-navy:hover,
        .btn-outline-navy:focus {
            color: #ffffff !important;
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
        }
    </style>
@endpush

@section('content')
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card sf-card mb-4 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 gap-3">
                        <a href="{{ route('fassg.fixed-lists.index') }}"
                            class="btn btn-outline-secondary btn-sm fw-semibold d-inline-flex align-items-center gap-2">
                            <i class="bi bi-arrow-left"></i>Back to Fixed Lists
                        </a>
                        <span
                            class="badge bg-secondary-subtle text-secondary px-3 py-2 fw-semibold border border-secondary-subtle">
                            {{ ucfirst($list->status->value ?? $list->status) }} Status
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span
                                class="badge bg-light text-dark mb-2 border">{{ $list->sponsorshipProgram->program_name }}</span>
                            <h2 class="h4 fw-bold mb-1">
                                {{ $list->batch_name ?: 'Batch #' . $list->id . ' - ' . ($list->sponsorshipProgram->program_name ?? 'Unassigned Program') }}
                            </h2>
                            <p class="text-secondary small mb-0">Total Names: {{ $list->items->count() }}</p>
                        </div>
                    </div>

                    @if (in_array($list->status, [\App\Enums\FixedListStatus::Draft, \App\Enums\FixedListStatus::Rejected], true))
                        <div class="border-top pt-3 mt-3">
                            <h3 class="h6 fw-bold mb-3"><i class="bi bi-person-plus me-1"></i>Encode Student Manually</h3>
                            <form method="POST" action="{{ route('fassg.fixed-lists.items.store', $list) }}"
                                class="row g-2">
                                @csrf
                                <div class="col-md-3">
                                    <input type="text" name="student_name" class="form-control form-control-sm"
                                        placeholder="Full Name" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="student_id_number" class="form-control form-control-sm"
                                        placeholder="Student ID" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="course" class="form-control form-control-sm"
                                        placeholder="Course" required>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" name="year_level" class="form-control form-control-sm"
                                        placeholder="Year" min="1" max="5" required>
                                </div>
                                <div class="col-md-1">
                                    <button type="submit" class="btn btn-navy-primary btn-sm w-100">Add</button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card sf-card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <h3 class="h6 fw-bold mb-0">Encoded &amp; Imported Students</h3>
                </div>
                <div class="table-responsive">
                    <table class="table sf-table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">Student Name</th>
                                <th>Student ID</th>
                                <th>Course &amp; Year</th>
                                <th>SLE-FHE Status</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($list->items as $item)
                                <tr>
                                    <td class="ps-4 fw-semibold">{{ $item->student_name }}</td>
                                    <td class="sf-mono">{{ $item->student_id_number ?: 'N/A' }}</td>
                                    <td>{{ $item->course }} {{ $item->year_level ? "Year {$item->year_level}" : '' }}</td>
                                    <td>
                                        <span
                                            class="badge {{ $item->is_sle_fhe_verified ? 'bg-success-subtle text-success-emphasis border border-success-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' }}">
                                            {{ $item->is_sle_fhe_verified ? 'Verified' : 'Pending Check' }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        @if (!$item->is_sle_fhe_verified)
                                            <form method="POST"
                                                action="{{ route('fassg.fixed-lists.items.verify', [$list, $item]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-outline-navy btn-sm">Verify</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-secondary py-4">No students encoded in this
                                        batch yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            @if (in_array($list->status, [\App\Enums\FixedListStatus::Draft, \App\Enums\FixedListStatus::Rejected], true))
                <div class="card sf-card mb-4 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="h6 fw-bold mb-2"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Import CSV / Excel
                        </h3>
                        <p class="text-secondary small mb-3">Expected columns: <code>student_name</code>,
                            <code>student_id_number</code>, <code>course</code>, <code>year_level</code>
                        </p>
                        <form method="POST" action="{{ route('fassg.fixed-lists.import', $list) }}"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <input type="file" name="file" class="form-control form-control-sm" accept=".csv,.txt"
                                    required>
                            </div>
                            <button type="submit" class="btn btn-outline-navy btn-sm w-100">
                                <i class="bi bi-upload me-1"></i>Import File
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card sf-card border-0 shadow-sm">
                    <div class="card-body p-4 text-center">
                        <h3 class="h6 fw-bold mb-2">Submit to Sponsor</h3>
                        <p class="text-secondary small mb-3">Once submitted, this list will be forwarded to the sponsor for
                            review.</p>
                        <form method="POST" action="{{ route('fassg.fixed-lists.submit', $list) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-navy-primary w-100 py-2" @disabled($list->items->isEmpty())
                                onClick="if ({{ $list->items->where('is_sle_fhe_verified', true)->count() }} !== {{ $list->items->count() }}) { alert('Verify all students before forwarding this list to the sponsor.'); return false; } return confirm('All students are verified. Forward this list to the sponsor?');">
                                <i class="bi bi-send me-1"></i>Submit List
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
