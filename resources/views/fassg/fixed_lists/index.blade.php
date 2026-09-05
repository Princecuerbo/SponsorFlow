@extends('layouts.app')

@section('title', 'Fixed Lists')
@section('eyebrow', 'FASSG Office')
@section('page-title', 'Sponsor-Provided Fixed Lists')

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
        }

        .btn-outline-navy:hover,
        .btn-outline-navy:focus {
            color: #ffffff !important;
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
        }

        .badge-info-custom {
            background-color: #e0f2fe !important;
            color: #0369a1 !important;
            border: 1px solid #bae6fd !important;
        }
    </style>
@endpush

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold mb-1">Sponsor-Provided Fixed Lists</h1>
            <p class="text-secondary small mb-0">Manage and process batch beneficiary lists forwarded by sponsors.</p>
        </div>
        <button type="button" class="btn btn-navy-primary fw-semibold d-inline-flex align-items-center gap-2 px-3"
            data-bs-toggle="modal" data-bs-target="#newFixedListModal">
            <i class="bi bi-plus-lg"></i>Upload / Encode List
        </button>
    </div>

    @if ($fixedLists->isEmpty())
        <div class="card sf-card border-0 shadow-sm">
            <div class="sf-empty-state text-center p-5">
                <i class="bi bi-list-check text-secondary fs-1 d-block mb-3"></i>
                <div class="fw-semibold">No fixed lists yet</div>
                <div class="small text-secondary mb-3">Upload a sponsor-provided beneficiary list, or encode names manually.
                </div>
                <button type="button" class="btn btn-navy-primary btn-sm px-3" data-bs-toggle="modal"
                    data-bs-target="#newFixedListModal">Upload / Encode List</button>
            </div>
        </div>
    @else
        <div class="accordion" id="fixedListsAccordion">
            @foreach ($fixedLists as $list)
                <div class="card sf-card mb-3 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h2 class="h6 sf-heading mb-1 fw-bold">
                                    <a href="{{ route('fassg.fixed-lists.show', $list) }}"
                                        class="text-decoration-none text-dark">
                                        {{ $list->batch_name ?: 'Batch #' . $list->id . ' - ' . ($list->sponsorshipProgram->program_name ?? 'Unassigned Program') }}
                                    </a>
                                </h2>
                                <div class="small text-secondary">
                                    {{ $list->sponsorshipProgram->program_name }} · {{ $list->total_names }}
                                    {{ Str::plural('name', $list->total_names) }}
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                @php($listStatus = strtolower($list->status->value ?? (string) $list->status))
                                @if ($listStatus === 'draft')
                                    <span
                                        class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-2 fw-semibold d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-pencil-square"></i>Draft
                                    </span>
                                @elseif ($listStatus === 'submitted')
                                    <span
                                        class="badge badge-info-custom px-2 py-2 fw-semibold d-inline-flex align-items-center gap-1">
                                        <i class="bi bi-send me-1"></i>Forwarded to Sponsor
                                    </span>
                                @else
                                    <span class="badge bg-light text-secondary border px-2 py-2 fw-semibold">
                                        {{ ucfirst($listStatus) }}
                                    </span>
                                @endif

                                <a href="{{ route('fassg.fixed-lists.show', $list) }}" class="btn btn-outline-navy btn-sm"
                                    title="View and manage fixed list" aria-label="View and manage fixed list">
                                    <i class="bi bi-eye"></i>
                                </a>

                                @if (in_array($list->status, [\App\Enums\FixedListStatus::Draft, \App\Enums\FixedListStatus::Rejected], true))
                                    <a href="{{ route('fassg.fixed-lists.edit', $list) }}"
                                        class="btn btn-outline-secondary btn-sm" title="Rename fixed list"
                                        aria-label="Rename fixed list">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('fassg.fixed-lists.destroy', $list) }}"
                                        class="d-inline" onsubmit="return confirm('Delete this fixed list?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm"
                                            title="Delete fixed list" aria-label="Delete fixed list">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table sf-table mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Student ID</th>
                                        <th>Course</th>
                                        <th>Year</th>
                                        <th>SLE-FHE Check</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($list->items as $item)
                                        <tr>
                                            <td class="fw-semibold">{{ $item->student_name }}</td>
                                            <td class="sf-mono text-secondary">{{ $item->student_id_number ?: 'N/A' }}</td>
                                            <td class="text-secondary">{{ $item->course ?: '—' }}</td>
                                            <td class="text-secondary">{{ $item->year_level ?: '—' }}</td>
                                            <td>
                                                @if ($item->is_sle_fhe_verified)
                                                    <span
                                                        class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-2 py-1">
                                                        <i class="bi bi-check-lg me-1"></i>Verified SLE-FHE
                                                    </span>
                                                @else
                                                    <span
                                                        class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                                        <i class="bi bi-exclamation-circle me-1"></i>Needs Checking
                                                    </span>
                                                @endif
                                            </td>
                                            <td><x-status-badge :status="$item->status" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Upload / encode modal --}}
    <div class="modal fade" id="newFixedListModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('fassg.fixed-lists.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title sf-heading fw-bold">New Fixed List</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small text-secondary">Sponsorship Program <span
                                        class="text-danger">*</span></label>
                                <select name="sponsorship_program_id" class="form-select" required>
                                    <option value="">Select…</option>
                                    @foreach ($programs as $program)
                                        <option value="{{ $program->id }}">{{ $program->program_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-secondary">Batch Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="batch_name" class="form-control" placeholder="e.g. 2026 Batch A"
                                    required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small text-secondary">Upload CSV/Excel of Names</label>
                                <input type="file" name="file" id="file" class="form-control" accept=".csv,.txt">
                                <div class="form-text">Or leave blank and encode names one by one after creating the batch.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-navy-primary"><i class="bi bi-upload me-1"></i>Create
                            Batch</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
