@extends('layouts.app')
@section('title', 'Database Backups')
@section('eyebrow', 'System Administrator')
@section('page-title', 'Database Backup Snapshots')

@section('content')
    <div class="alert alert-warning border-0">
        <i class="bi bi-exclamation-triangle me-2"></i>Restoring a snapshot replaces current database data. Confirm the file
        and maintenance procedure before restoring.
    </div>

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="fw-bold mb-0">Database Snapshots</h4>
            <p class="text-muted small mb-0">Manage and restore system backup files.</p>
        </div>
        <form action="{{ route('admin.backups.store') }}" method="POST">
            @csrf
            <button type="submit" class="btn fw-semibold d-inline-flex align-items-center gap-2"
                style="background-color: #0f294a; color: #fff; border: none; border-radius: 8px;">
                <i class="bi bi-database-add"></i>Create New Backup
            </button>
        </form>
    </div>

    <div class="card sf-card" style="border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div class="table-responsive">
            <table class="table sf-table mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">File name</th>
                        <th>Size</th>
                        <th>Created</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backups as $backup)
                        <tr>
                            <td class="ps-4 fw-semibold">{{ $backup->file_name }}</td>
                            <td style="color: #475569;">{{ number_format($backup->file_size / 1024, 1) }} KB</td>
                            <td style="color: #475569;">{{ $backup->created_at?->format('M d, Y h:i A') }}</td>
                            <td><span class="badge text-bg-success">{{ ucfirst($backup->status) }}</span></td>
                            <td class="text-end pe-4">
                                <a class="btn btn-sm btn-outline-secondary"
                                    href="{{ route('admin.backups.download', $backup) }}" title="Download snapshot">
                                    <i class="bi bi-download"></i>
                                </a>
                                <form class="d-inline" method="POST" action="{{ route('admin.backups.restore', $backup) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger" type="submit" title="Restore snapshot"
                                        onclick="return confirm('Restore this database snapshot?')">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-5">No backup snapshots have been created.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $backups->links() }}</div>
@endsection
