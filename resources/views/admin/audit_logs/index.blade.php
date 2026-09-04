@extends('layouts.app')
@section('title', 'Audit Logs')
@section('eyebrow', 'System Administrator')
@section('page-title', 'Security Audit Logs')

@section('content')
    <div class="card sf-card mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="row g-2">
                <div class="col-md-4">
                    <input class="form-control" type="text" name="q" value="{{ request('q') }}" placeholder="Search action or module"
                        oninput="clearTimeout(window.searchTimer); window.searchTimer = setTimeout(() => this.form.submit(), 600)">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="role" onchange="this.form.submit()">
                        <option value="">All roles</option>
                        @foreach($roles as $key => $label)
                            <option value="{{ $key }}" @selected(request('role') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input class="form-control" type="date" name="from" value="{{ request('from') }}" onchange="this.form.submit()">
                </div>
                <div class="col-md-2">
                    <input class="form-control" type="date" name="to" value="{{ request('to') }}" onchange="this.form.submit()">
                </div>
            </form>
        </div>
    </div>

    <div class="card sf-card" style="border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div class="table-responsive">
            <table class="table sf-table mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Timestamp</th>
                        <th>User / Role</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>IP Address</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="ps-4" style="color: #475569;">{{ $log->created_at?->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
                            <td>
                                {{ $log->user?->name ?? 'System' }}
                                <div class="small" style="color: #475569;">{{ $log->role ? ($roles[$log->role] ?? $log->role) : 'System' }}</div>
                            </td>
                            <td class="fw-semibold">{{ $log->action }}</td>
                            <td><span class="badge bg-light text-dark">{{ $log->target_module }}</span></td>
                            <td class="font-monospace" style="color: #475569;">{{ $log->ip_address ?? '—' }}</td>
                            <td style="color: #475569;">{{ $log->details ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-5">No audit events found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $logs->links() }}</div>
@endsection
