@extends('layouts.app')
@section('title', 'Database Backups')
@section('eyebrow')
    <span class="admin-original-copy">System Administrator</span>
    <span class="admin-desktop-copy d-none">System Administrator · Backups</span>
@endsection
@section('page-title')
    <span class="admin-original-copy">Database Backup Snapshots</span>
    <span class="admin-desktop-copy d-none">Database Backups</span>
@endsection
@section('subtitle')
    <span class="admin-original-copy">Create and download private database table snapshots.</span>
    <span class="admin-desktop-copy d-none">Create and download database table snapshots.</span>
@endsection

@push('styles')
    @include('admin.partials.desktop-styles')
@endpush

@section('header-actions')
    <form action="{{ route('admin.backups.store') }}" method="POST" class="m-0">
        @csrf
        <button type="submit" class="btn fw-semibold admin-btn-primary" style="height: 38px;">
            <i class="bi bi-database-add"></i>
            <span class="admin-original-copy">Create New Backup</span>
            <span class="admin-desktop-copy d-none">Create Backup</span>
        </button>
    </form>
@endsection

@section('content')
    {{-- Information Panel --}}
    <div class="admin-info-notice">
        <i class="bi bi-info-circle"></i>
        <div>
            <div class="fw-semibold mb-1" style="color: #0F2942;">Database Backup Policy & Snapshot Limitations</div>
            <ul class="mb-0 ps-3" style="font-size: 12.5px; line-height: 1.55;">
                <li>Web-based database restoration is unavailable. Restoring database data requires a separately verified maintenance procedure.</li>
                <li>Database table snapshots do not include uploaded documents or media files stored in filesystem storage.</li>
                <li>Other database objects and server configurations require separate operational backups.</li>
            </ul>
        </div>
    </div>

    {{-- Backups Table --}}
    <div class="card sf-card admin-card">
        <div class="table-responsive">
            <table class="table sf-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">File Name</th>
                        <th class="text-nowrap">Size</th>
                        <th class="text-nowrap">Created</th>
                        <th class="text-nowrap">Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backups as $backup)
                        <tr>
                            <td class="ps-4 text-nowrap">
                                <div class="fw-semibold text-dark d-flex align-items-center gap-2">
                                    <i class="bi bi-database text-secondary" style="font-size: 15px;" aria-hidden="true"></i>
                                    <span>{{ $backup->file_name }}</span>
                                </div>
                            </td>
                            <td class="text-nowrap text-secondary">
                                {{ number_format($backup->file_size / 1024, 1) }} KB
                            </td>
                            <td class="text-nowrap">
                                <span class="admin-original-copy text-secondary">
                                    {{ $backup->created_at?->format('M d, Y, h:i A') }}
                                </span>
                                <div class="admin-desktop-copy d-none">
                                    <div class="fw-medium text-dark">{{ $backup->created_at?->format('M d, Y') }}</div>
                                    <div class="small text-muted">{{ $backup->created_at?->format('h:i A') }}</div>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <x-status-badge :status="$backup->status" />
                            </td>
                            <td class="text-end pe-4">
                                @if ($backup->status === 'completed')
                                    <a class="btn btn-sm admin-action-btn admin-action-edit"
                                        href="{{ route('admin.backups.download', $backup) }}"
                                        title="Download snapshot" aria-label="Download snapshot">
                                        <i class="bi bi-download"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="admin-empty-state">
                                <span class="admin-empty-icon"><i class="bi bi-database"></i></span>
                                <h4 class="admin-empty-title">No Backups Available</h4>
                                <p class="admin-empty-desc">Create a database table snapshot to begin keeping backup records.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $backups->links() }}</div>
@endsection
