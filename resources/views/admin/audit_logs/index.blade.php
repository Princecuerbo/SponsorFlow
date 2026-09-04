@extends('layouts.app')
@section('title', 'Audit Logs')
@section('eyebrow', 'System Administrator')
@section('page-title', 'Security Audit Logs')

@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
            <div>
                <h4 class="fw-bold text-dark mb-1">Audit Logs</h4>
                <p class="text-muted small mb-0">Track and review system activities, user authentications, and record
                    modifications.</p>
            </div>
        </div>

        <div class="card sf-card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
            <div class="card-header bg-white border-bottom p-3">
                <form id="auditLogFilterForm" method="GET" action="{{ route('admin.audit-logs.index') }}"
                    class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <input class="form-control" type="text" name="q" value="{{ request('q') }}"
                            placeholder="Search action or module"
                            oninput="clearTimeout(window.searchTimer); window.searchTimer = setTimeout(() => this.form.submit(), 600)">
                    </div>

                    {{-- Custom Role Filter Dropdown --}}
                    <div class="col-md-3">
                        <div class="dropdown">
                            <button
                                class="form-select text-start bg-white d-flex justify-content-between align-items-center"
                                type="button" id="roleFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <span
                                    id="selectedRoleLabel">{{ request('role') ? $roles[request('role')] ?? request('role') : 'All roles' }}</span>
                            </button>
                            <ul class="dropdown-menu shadow-sm border w-100 mt-1 p-1" aria-labelledby="roleFilterDropdown">
                                <li>
                                    <a class="dropdown-item rounded py-2 {{ request('role') == '' ? 'active-filter' : '' }}"
                                        href="javascript:void(0)" onclick="setRoleFilter('', 'All roles')">All roles</a>
                                </li>
                                @foreach ($roles as $key => $label)
                                    <li>
                                        <a class="dropdown-item rounded py-2 {{ request('role') === $key ? 'active-filter' : '' }}"
                                            href="javascript:void(0)"
                                            onclick="setRoleFilter('{{ $key }}', '{{ $label }}')">{{ $label }}</a>
                                    </li>
                                @endforeach
                            </ul>
                            <input type="hidden" name="role" id="roleInput" value="{{ request('role') }}">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <input class="form-control" type="date" name="from" value="{{ request('from') }}"
                            onchange="this.form.submit()">
                    </div>
                    <div class="col-md-2">
                        <input class="form-control" type="date" name="to" value="{{ request('to') }}"
                            onchange="this.form.submit()">
                    </div>
                    <div class="col-md-1 text-end">
                        <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-outline-secondary w-100"
                            title="Reset Filters">Reset</a>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table sf-table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Timestamp</th>
                            <th>User / Role</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>IP Address</th>
                            <th class="pe-4">Details</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse($logs as $log)
                            <tr>
                                <td class="ps-4" style="color: #475569;">
                                    {{ $log->created_at?->timezone('Asia/Manila')->format('M d, Y h:i A') }}
                                </td>
                                <td>
                                    <div>{{ $log->user?->name ?? 'System' }}</div>
                                    <div class="small" style="color: #475569;">
                                        {{ $log->role ? $roles[$log->role] ?? $log->role : 'System' }}
                                    </div>
                                </td>
                                <td>
                                    <code class="px-2 py-1 rounded fw-semibold"
                                        style="background-color: rgba(15, 41, 66, 0.08); color: #0F2942;">
                                        {{ $log->action }}
                                    </code>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1">
                                        {{ $log->target_module }}
                                    </span>
                                </td>
                                <td class="font-monospace" style="color: #475569;">{{ $log->ip_address ?? '—' }}</td>
                                <td class="pe-4" style="color: #475569;">{{ $log->details ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-secondary py-5">
                                    <i class="bi bi-shield-check display-6 d-block mb-2 text-secondary opacity-50"></i>
                                    No audit events found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div
                class="card-footer bg-white border-top d-flex flex-column align-items-center justify-content-center py-3 px-4 gap-2 text-center">
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
    </div>
@endsection

<script>
    function setRoleFilter(value, labelText) {
        document.getElementById('roleInput').value = value;
        document.getElementById('selectedRoleLabel').innerText = labelText;
        document.getElementById('auditLogFilterForm').submit();
    }
</script>

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

    /* Custom Dropdown Hover & Active States */
    .dropdown-item.active-filter {
        background-color: #ffffff !important;
        color: #0F2942 !important;
        font-weight: 600;
    }

    .dropdown-item:hover {
        background-color: rgba(15, 41, 66, 0.08) !important;
        color: #0F2942 !important;
    }
</style>
