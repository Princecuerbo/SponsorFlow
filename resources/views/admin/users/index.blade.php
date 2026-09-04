@extends('layouts.app')
@section('title', 'User Accounts')
@section('eyebrow', 'System Administrator')
@section('page-title', 'User Account Management')

@section('content')
    <div class="card sf-card mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2">
                <div class="col-md-5">
                    <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search name or email..."
                        oninput="clearTimeout(window.searchTimer); window.searchTimer = setTimeout(() => this.form.submit(), 600)">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="role" onchange="this.form.submit()">
                        <option value="">All roles</option>
                        @foreach (\App\Enums\UserRole::cases() as $role)
                            <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="status" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Deactivated</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button"
                        class="btn fw-semibold w-100 d-inline-flex align-items-center justify-content-center gap-2"
                        style="background-color: #0f294a; color: #fff; border: none; border-radius: 8px;"
                        data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="bi bi-person-plus"></i>Add User Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card sf-card" style="border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div class="table-responsive">
            <table class="table sf-table mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last login</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $account)
                        <tr>
                            <td class="ps-4 fw-semibold">{{ $account->name }}</td>
                            <td style="color: #475569;">{{ $account->email }}</td>
                            <td><span class="badge bg-light text-dark">{{ $account->role->label() }}</span></td>
                            <td>
                                <span class="badge {{ $account->isActive() ? 'bg-success-subtle text-success-emphasis' : 'bg-secondary-subtle text-secondary-emphasis' }}">
                                    {{ $account->isActive() ? 'Active' : 'Deactivated' }}
                                </span>
                            </td>
                            <td>
                                @if ($account->last_login_at)
                                    <span class="small" style="color: #475569;">{{ $account->last_login_at->diffForHumans() }}</span>
                                @else
                                    <span class="badge bg-light text-secondary fw-normal">Never</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal"
                                    data-bs-target="#editUser{{ $account->id }}" title="Edit user">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                @if (!$account->is($user))
                                    <form method="POST"
                                        action="{{ $account->isActive() ? route('admin.users.deactivate', $account) : route('admin.users.restore', $account) }}"
                                        class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-secondary" type="submit"
                                            title="{{ $account->isActive() ? 'Deactivate account' : 'Restore account' }}">
                                            <i class="bi {{ $account->isActive() ? 'bi-person-dash' : 'bi-person-check' }}"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-5">No user accounts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $users->links() }}</div>

    {{-- Edit Modals --}}
    @foreach ($users as $account)
        <div class="modal fade" id="editUser{{ $account->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content" style="border-radius: 14px;">
                    <form method="POST" action="{{ route('admin.users.update', $account) }}">
                        @csrf @method('PUT')
                        <div class="modal-header" style="border-bottom: 1px solid #e2e8f0;">
                            <h2 class="modal-title h5 fw-bold">Edit user account</h2>
                            <button class="btn-close" data-bs-dismiss="modal" type="button"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="editName{{ $account->id }}" style="font-size: 0.85rem; color: #475569;">Name / Organization Name</label>
                                <input class="form-control" id="editName{{ $account->id }}" name="name"
                                    value="{{ $account->name }}" required style="border-radius: 8px; background-color: #f8fafc;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="editEmail{{ $account->id }}" style="font-size: 0.85rem; color: #475569;">Email</label>
                                <input class="form-control" id="editEmail{{ $account->id }}" type="email" name="email"
                                    value="{{ $account->email }}" required style="border-radius: 8px; background-color: #f8fafc;">
                            </div>
                            <label class="form-label fw-semibold" for="editRole{{ $account->id }}" style="font-size: 0.85rem; color: #475569;">Role</label>
                            <select class="form-select" id="editRole{{ $account->id }}" name="role" required style="border-radius: 8px; background-color: #f8fafc;">
                                @foreach (\App\Enums\UserRole::cases() as $role)
                                    <option value="{{ $role->value }}" @selected($account->role === $role)>{{ $role->label() }}</option>
                                @endforeach
                            </select>
                            <div class="mb-3 mt-3">
                                <label class="form-label fw-bold small" for="editPassword{{ $account->id }}" style="color: #475569;">New Password (Optional)</label>
                                <input type="password" name="password" class="form-control"
                                    id="editPassword{{ $account->id }}" placeholder="Leave blank to keep current password"
                                    style="border-radius: 8px; background-color: #f8fafc;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small" for="editPasswordConfirmation{{ $account->id }}" style="color: #475569;">Confirm New Password</label>
                                <input type="password" name="password_confirmation" class="form-control"
                                    id="editPasswordConfirmation{{ $account->id }}" placeholder="Re-enter new password"
                                    style="border-radius: 8px; background-color: #f8fafc;">
                            </div>
                        </div>
                        <div class="modal-footer" style="border-top: 1px solid #e2e8f0;">
                            <button class="btn fw-semibold" type="submit"
                                style="background-color: #0f294a; color: #fff; border: none; border-radius: 8px; padding: 0.6rem 1.25rem;">
                                Save changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    {{-- Add Modal --}}
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius: 14px;">
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                    <div class="modal-header" style="border-bottom: 1px solid #e2e8f0;">
                        <h2 class="modal-title h5 fw-bold">Add user account</h2>
                        <button class="btn-close" data-bs-dismiss="modal" type="button"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="adminName" style="font-size: 0.85rem; color: #475569;">Name / Organization Name</label>
                            <input class="form-control" id="adminName" name="name" required style="border-radius: 8px; background-color: #f8fafc;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="adminEmail" style="font-size: 0.85rem; color: #475569;">Email</label>
                            <input class="form-control" id="adminEmail" type="email" name="email" required style="border-radius: 8px; background-color: #f8fafc;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="adminRole" style="font-size: 0.85rem; color: #475569;">Role</label>
                            <select class="form-select" id="adminRole" name="role" required style="border-radius: 8px; background-color: #f8fafc;">
                                @foreach (\App\Enums\UserRole::cases() as $role)
                                    <option value="{{ $role->value }}">{{ $role->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col">
                                <label class="form-label fw-semibold" for="adminPassword" style="font-size: 0.85rem; color: #475569;">Password</label>
                                <input class="form-control" id="adminPassword" type="password" name="password"
                                    minlength="8" required style="border-radius: 8px; background-color: #f8fafc;">
                            </div>
                            <div class="col">
                                <label class="form-label fw-semibold" for="adminPasswordConfirmation" style="font-size: 0.85rem; color: #475569;">Confirm</label>
                                <input class="form-control" id="adminPasswordConfirmation" type="password"
                                    name="password_confirmation" required style="border-radius: 8px; background-color: #f8fafc;">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #e2e8f0;">
                        <button class="btn fw-semibold" type="submit"
                            style="background-color: #0f294a; color: #fff; border: none; border-radius: 8px; padding: 0.6rem 1.25rem;">
                            Create account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('action') === 'create') {
                const addUserModal = new bootstrap.Modal(document.getElementById('addUserModal'));
                if (addUserModal) addUserModal.show();
            }
        });
    </script>
@endsection
