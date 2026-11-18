@extends('layouts.app')
@section('title', 'Audit Logs')
@section('eyebrow')
    <span class="admin-original-copy">System Administrator</span>
    <span class="admin-desktop-copy d-none">System Administrator · Audit</span>
@endsection
@section('page-title')
    <span class="admin-original-copy">Security Audit Logs</span>
    <span class="admin-desktop-copy d-none">Security Audit Logs</span>
@endsection
@section('subtitle')
    <span class="admin-original-copy">Track and review system activities, user authentications, and record modifications.</span>
    <span class="admin-desktop-copy d-none">Track and review system activities, user authentications, and record modifications.</span>
@endsection

@push('styles')
    @include('admin.partials.desktop-styles')
    <style>
        /* Pagination Brand Overrides */
        .pagination {
            margin-bottom: 0 !important;
            justify-content: center;
        }

        .pagination .page-item.active .page-link {
            background-color: #0F2942 !important;
            border-color: #0F2942 !important;
            color: #ffffff !important;
        }

        .pagination .page-link {
            color: #0F2942;
        }

        .pagination .page-link:hover {
            color: #0A1E31;
            background-color: rgba(15, 41, 66, 0.08);
        }

        /* Form Inputs Focus Ring */
        .form-control:focus,
        .form-select:focus {
            border-color: #0F2942 !important;
            box-shadow: 0 0 0 0.25rem rgba(15, 41, 66, 0.15) !important;
        }
    </style>
@endpush

@include('admin.partials.audit-action-helpers')

@section('content')
    {{-- Filter Bar --}}
    <div class="card sf-card admin-filter-bar admin-audit-filter-bar mb-4">
        <div class="card-body p-0">
            <form id="auditLogFilterForm" method="GET" action="{{ route('admin.audit-logs.index') }}"
                class="row g-2 align-items-end admin-audit-toolbar">
                <div class="col-md-3 admin-audit-col-search">
                    <label for="auditSearch" class="form-label small fw-semibold text-secondary">Action or module</label>
                    <div class="position-relative">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y text-muted ms-3" style="font-size: 13px; pointer-events: none;"></i>
                        <input id="auditSearch" class="form-control" style="padding-left: 36px !important;" type="text" name="q" value="{{ request('q') }}"
                            placeholder="Search action or module..."
                            oninput="clearTimeout(window.searchTimer); window.searchTimer = setTimeout(() => this.form.submit(), 600)">
                    </div>
                </div>

                {{-- Role Filter Select --}}
                <div class="col-md-3 admin-audit-col-role">
                    <label for="auditRole" class="form-label small fw-semibold text-secondary">Role</label>
                    <select id="auditRole" name="role" class="form-select bg-white" onchange="this.form.submit()">
                        <option value="">All roles</option>
                        @foreach ($roles as $key => $label)
                            <option value="{{ $key }}" @selected(request('role') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 admin-audit-col-from">
                    <label for="auditFrom" class="form-label small fw-semibold text-secondary">From date</label>
                    <input id="auditFrom" class="form-control" type="date" name="from" value="{{ request('from') }}"
                        onchange="this.form.submit()">
                </div>
                <div class="col-md-2 admin-audit-col-to">
                    <label for="auditTo" class="form-label small fw-semibold text-secondary">To date</label>
                    <input id="auditTo" class="form-control" type="date" name="to" value="{{ request('to') }}"
                        onchange="this.form.submit()">
                </div>
                <div class="col-md-2 admin-audit-col-reset text-end">
                    <a href="{{ route('admin.audit-logs.index') }}" class="sf-btn-reset-filters admin-btn-reset w-100">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset Filters</span>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Audit Logs Table --}}
    <div class="card sf-card admin-card">
        <div class="table-responsive">
            <table class="table sf-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Timestamp</th>
                        <th>User / Role</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>IP Address</th>
                        <th class="pe-4">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        @php
                            $meta = resolveAuditActionMeta($log->action);
                            $parsedDetails = null;
                            $isJson = false;
                            $previewText = $log->details ?? '—';
                            if ($log->details && (str_starts_with(trim($log->details), '{') || str_starts_with(trim($log->details), '['))) {
                                $decoded = json_decode($log->details, true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                    $parsedDetails = $decoded;
                                    $isJson = true;
                                    $keys = array_keys($decoded);
                                    $previewText = implode(', ', array_slice($keys, 0, 3));
                                    if (count($keys) > 3) $previewText .= '...';
                                }
                            }
                        @endphp
                        <tr>
                            <td class="ps-4 text-nowrap">
                                <span class="admin-original-copy" style="color: #475569;">
                                    {{ $log->created_at?->timezone('Asia/Manila')->format('M d, Y, h:i A') }}
                                </span>
                                <div class="admin-desktop-copy d-none">
                                    <div class="fw-medium text-dark">{{ $log->created_at?->timezone('Asia/Manila')->format('M d, Y') }}</div>
                                    <div class="small text-muted">{{ $log->created_at?->timezone('Asia/Manila')->format('h:i A') }}</div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $log->user?->name ?? 'System' }}</div>
                                <div class="small text-secondary">
                                    {{ $log->role ? $roles[$log->role] ?? $log->role : 'System' }}
                                </div>
                            </td>
                            <td>
                                <span class="admin-original-copy">
                                    <code class="px-2 py-1 rounded fw-semibold"
                                        style="background-color: rgba(15, 41, 66, 0.08); color: #0F2942;">
                                        {{ $log->action }}
                                    </code>
                                </span>
                                <div class="admin-desktop-copy d-none">
                                    <div class="fw-semibold text-dark" style="font-size: 13px;">{{ $meta['label'] }}</div>
                                    <code class="text-muted" style="font-size: 11px; background: rgba(15, 41, 66, 0.05); padding: 1px 4px; border-radius: 4px;">{{ $log->action }}</code>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border px-2.5 py-1 text-nowrap" style="font-size: 11.5px; font-weight: 500;">
                                    {{ $log->target_module }}
                                </span>
                            </td>
                            <td class="font-monospace text-secondary small text-nowrap">
                                {{ $log->ip_address ?? '—' }}
                            </td>
                            <td class="pe-4">
                                <span class="admin-original-copy" style="color: #475569;">
                                    {{ $log->details ?? '—' }}
                                </span>
                                <div class="admin-desktop-copy d-none">
                                    @if ($log->details)
                                        <div class="d-flex align-items-center justify-content-between gap-2">
                                            <span class="text-truncate text-secondary small" style="max-width: 160px;" title="{{ is_string($log->details) ? $log->details : '' }}">
                                                {{ $previewText }}
                                            </span>
                                            <button type="button" class="btn btn-sm admin-action-btn admin-action-view"
                                                data-bs-toggle="modal" data-bs-target="#auditDetailsModal{{ $log->id }}"
                                                title="View Details" aria-label="View Details">
                                                <i class="bi bi-eye"></i>View Details
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="admin-empty-state">
                                <span class="admin-empty-icon"><i class="bi bi-journal-text"></i></span>
                                <h4 class="admin-empty-title">No Audit Events Found</h4>
                                <p class="admin-empty-desc">No events matched the current filter criteria. Try resetting your search parameters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer d-flex flex-column align-items-center justify-content-center py-3 px-4 gap-2 text-center">
            <div class="pagination-sm mb-1">
                {{ $logs->links('pagination::bootstrap-4') }}
            </div>
            <div class="small text-muted">
                Showing <span class="fw-semibold text-dark">{{ $logs->firstItem() ?? 0 }}</span> to
                <span class="fw-semibold text-dark">{{ $logs->lastItem() ?? 0 }}</span> of
                <span class="fw-semibold text-dark">{{ $logs->total() }}</span> results
            </div>
        </div>
    </div>

    {{-- Audit Log Details Modals --}}
    @foreach ($logs as $log)
        @if ($log->details)
            @php
                $meta = resolveAuditActionMeta($log->action);
                $parsed = null;
                $isJsonPayload = false;
                $hasDiff = false;
                if (str_starts_with(trim($log->details), '{') || str_starts_with(trim($log->details), '[')) {
                    $decoded = json_decode($log->details, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $parsed = $decoded;
                        $isJsonPayload = true;
                        // Check if before/after diff structure
                        foreach ($parsed as $k => $v) {
                            if (is_array($v) && (array_key_exists('before', $v) || array_key_exists('after', $v))) {
                                $hasDiff = true;
                                break;
                            }
                        }
                    }
                }
            @endphp
            <div class="modal fade" id="auditDetailsModal{{ $log->id }}" tabindex="-1" aria-hidden="true"
                aria-labelledby="auditTitle{{ $log->id }}">
                <div class="modal-dialog modal-dialog-centered modal-lg admin-modal-audit-details">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div class="d-flex align-items-center gap-3">
                                <div class="admin-metric-icon admin-icon-navy" style="width: 40px; height: 40px; border-radius: 10px; font-size: 18px;">
                                    <i class="bi bi-journal-text"></i>
                                </div>
                                <div>
                                    <h2 id="auditTitle{{ $log->id }}" class="modal-title h5 fw-bold mb-0">Audit Log Details</h2>
                                    <p class="modal-subtitle">Log Entry #{{ $log->id }} · {{ $meta['label'] }}</p>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            {{-- Labeled Metadata Grid --}}
                            <div class="admin-audit-grid">
                                <div class="admin-audit-section">
                                    <div class="admin-audit-label">Action</div>
                                    <div class="admin-audit-value fw-semibold">{{ $meta['label'] }}</div>
                                    <code class="small text-muted d-block mt-1" style="font-size: 11px;">{{ $log->action }}</code>
                                </div>
                                <div class="admin-audit-section">
                                    <div class="admin-audit-label">Performed By</div>
                                    <div class="admin-audit-value fw-semibold">{{ $log->user?->name ?? 'System' }}</div>
                                    <div class="small text-muted">{{ $log->user?->email ?? ($log->role ? $roles[$log->role] ?? $log->role : 'Automated System Process') }}</div>
                                </div>
                                <div class="admin-audit-section">
                                    <div class="admin-audit-label">Date & Time</div>
                                    <div class="admin-audit-value">{{ $log->created_at?->timezone('Asia/Manila')->format('F d, Y · h:i:s A') }}</div>
                                    <div class="small text-muted">Philippine Standard Time (UTC+8)</div>
                                </div>
                                <div class="admin-audit-section">
                                    <div class="admin-audit-label">Target Module & Network</div>
                                    <div class="admin-audit-value">
                                        <span class="badge bg-light text-secondary border px-2 py-0.5" style="font-size: 11px;">{{ $log->target_module }}</span>
                                        <span class="font-monospace text-secondary small ms-2">{{ $log->ip_address ?? '—' }}</span>
                                    </div>
                                    @if ($log->user_agent)
                                        <div class="small text-muted text-truncate mt-1" title="{{ $log->user_agent }}">{{ $log->user_agent }}</div>
                                    @endif
                                </div>
                            </div>

                            {{-- Event Details / Payload --}}
                            <div class="mt-3">
                                <div class="admin-audit-label mb-2">Event Details & Payload</div>
                                @if ($hasDiff)
                                    <div class="border rounded-3 overflow-hidden">
                                        <table class="table sf-table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th class="ps-3" style="width: 30%;">Setting / Property</th>
                                                    <th style="width: 35%;">Previous Value (Before)</th>
                                                    <th style="width: 35%;">Updated Value (After)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($parsed as $key => $diff)
                                                    @if (is_array($diff) && (array_key_exists('before', $diff) || array_key_exists('after', $diff)))
                                                        <tr>
                                                            <td class="ps-3 fw-semibold text-dark">{{ str($key)->replace('_', ' ')->title() }}</td>
                                                            <td>
                                                                <div class="admin-audit-diff-col admin-audit-diff-before">
                                                                    {{ is_scalar($diff['before'] ?? null) ? ($diff['before'] ?? '—') : json_encode($diff['before'] ?? null) }}
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="admin-audit-diff-col admin-audit-diff-after">
                                                                    {{ is_scalar($diff['after'] ?? null) ? ($diff['after'] ?? '—') : json_encode($diff['after'] ?? null) }}
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @elseif ($isJsonPayload)
                                    <pre class="admin-audit-codebox mb-0"><code>{{ json_encode($parsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                @elseif (!empty($log->details))
                                    <div class="p-3 bg-light rounded-3 border text-dark font-monospace small" style="word-break: break-all;">
                                        {{ $log->details }}
                                    </div>
                                @else
                                    <div class="p-3 bg-light rounded-3 border text-muted small fst-italic text-center">
                                        No additional payload or metadata recorded for this event.
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn admin-btn-close-modal px-4" data-bs-dismiss="modal">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endsection
