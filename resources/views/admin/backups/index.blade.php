@extends('layouts.app')
@section('title', 'Database Backups')
@section('eyebrow', 'System Administrator')
@section('page-title', 'Database Backup Snapshots')
@section('subtitle', 'Manage and restore system backup files.')

@section('header-actions')
    <form action="{{ route('admin.backups.store') }}" method="POST" class="m-0">
        @csrf
        <button type="submit" class="btn fw-semibold d-inline-flex align-items-center gap-2"
            style="background-color: #0f294a; color: #fff; border: none; border-radius: 8px;">
            <i class="bi bi-database-add"></i>Create New Backup
        </button>
    </form>
@endsection

@section('content')
    <div class="alert border-0 border-start border-4 border-primary rounded-3 p-3 mb-4"
        style="background-color: rgba(15, 41, 66, 0.05); color: #0F2942;">
        <i class="bi bi-exclamation-triangle me-2"></i>Restoring a snapshot replaces current database data. Confirm the file
        and maintenance procedure before restoring.
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="table-responsive">
            <table class="table sf-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">File name</th>
                        <th class="text-nowrap">Size</th>
                        <th class="text-nowrap">Created</th>
                        <th class="text-nowrap">Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backups as $backup)
                        <tr>
                            <td class="ps-4 fw-semibold text-nowrap">{{ $backup->file_name }}</td>
                            <td class="text-nowrap" style="color: #475569;">{{ number_format($backup->file_size / 1024, 1) }} KB</td>
                            <td class="text-nowrap" style="color: #475569;">{{ $backup->created_at?->format('M d, Y, h:i A') }}</td>
                            <td class="text-nowrap"><x-status-badge :status="$backup->status" /></td>
                            <td class="text-end pe-4">
                                <a class="btn btn-sm btn-outline-secondary"
                                    href="{{ route('admin.backups.download', $backup) }}" title="Download snapshot">
                                    <i class="bi bi-download"></i>
                                </a>
                                <form class="d-inline" method="POST"
                                    action="{{ route('admin.backups.restore', $backup) }}">
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
                            <td colspan="5" class="text-center py-5">
                                <i class="bi bi-database-fill-gear display-5 text-muted mb-3 d-block"></i>
                                <h3 class="h6 sf-heading mb-3">No Backup Snapshots Found</h3>
                                <p class="text-muted small mb-0">Create a new backup to safeguard system data.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $backups->links() }}</div>
@endsection
