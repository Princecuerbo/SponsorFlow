@extends('layouts.app')
@section('title', 'User Accounts')
@section('eyebrow')
    <span class="admin-original-copy">System Administrator · Accounts</span>
    <span class="admin-desktop-copy d-none">System Administrator · Accounts</span>
@endsection
@section('page-title')
    <span class="admin-original-copy">User Account Management</span>
    <span class="admin-desktop-copy d-none">User Account Management</span>
@endsection
@section('subtitle')
    <span class="admin-original-copy">Manage user accounts, roles, and access across the portal.</span>
    <span class="admin-desktop-copy d-none">Manage user accounts, roles, and access across the portal.</span>
@endsection

@push('styles')
    @include('admin.partials.desktop-styles')
    <style>
        /* Focus ring matching navy theme */
        .form-select:focus,
        .form-control:focus {
            border-color: #0F2942 !important;
            box-shadow: 0 0 0 0.25rem rgba(15, 41, 66, 0.15) !important;
        }

        /* Default and Active (selected) options stay clean white */
        select.form-select option,
        select.form-select option:checked {
            background-color: #ffffff !important;
            color: #1e293b !important;
        }

        /* Hovered option turns dark navy with white text */
        select.form-select option:hover {
            background-color: #0F2942 !important;
            color: #ffffff !important;
        }

        .filter-dropdown .dropdown-item.active {
            background-color: #ffffff !important;
            color: #0F2942 !important;
        }

        .filter-dropdown .dropdown-item:hover,
        .filter-dropdown .dropdown-item:focus {
            background-color: rgba(15, 41, 66, 0.08) !important;
            color: #0F2942 !important;
        }

        #adminRoleDropdown .dropdown-item.active {
            background-color: #ffffff !important;
            color: #000f28 !important;
        }

        #adminRoleDropdown .dropdown-item:hover,
        #adminRoleDropdown .dropdown-item:focus {
            background-color: rgba(15, 41, 66, 0.08) !important;
            color: #0F2942 !important;
        }
    </style>
@endpush

@section('content')
    @php
        $failedForm = $errors->any() ? old('_account_form') : null;
    @endphp

    {{-- Filter Bar --}}
    <div class="card sf-card admin-filter-bar mb-4">
        <div class="card-body p-0">
            <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-center">
                <div class="col-md-5 admin-toolbar-search">
                    <div class="position-relative">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y text-muted ms-3" style="font-size: 13px; pointer-events: none;"></i>
                        <input class="form-control" style="padding-left: 36px !important;" name="q" value="{{ request('q') }}" placeholder="Search name or email..."
                            oninput="clearTimeout(window.searchTimer); window.searchTimer = setTimeout(() => this.form.submit(), 600)">
                    </div>
                </div>
                <div class="col-md-2 admin-toolbar-role">
                    <select name="role" class="form-select" onchange="this.form.submit()">
                        <option value="">All roles</option>
                        @foreach (\App\Enums\UserRole::cases() as $role)
                            <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 admin-toolbar-status">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Deactivated</option>
                    </select>
                </div>
                <div class="col-md-3 admin-toolbar-add">
                    <button type="button"
                        class="btn fw-semibold admin-btn-primary"
                        data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="bi bi-person-plus me-1.5"></i>Add User Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- User Accounts Table --}}
    <div class="card sf-card admin-card">
        <div class="table-responsive">
            <table class="table sf-table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4 admin-col-name">Name</th>
                        <th class="admin-col-email">Email</th>
                        <th class="text-nowrap admin-col-role">Role</th>
                        <th class="text-nowrap admin-col-status">Status</th>
                        <th class="text-nowrap admin-col-login">Last Login</th>
                        <th class="text-end pe-4 admin-col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $account)
                        <tr>
                            <td class="ps-4 fw-semibold text-dark">{{ $account->name }}</td>
                            <td style="color: #475569;">{{ $account->email }}</td>
                            <td>
                                <span class="badge bg-light text-secondary border px-2.5 py-1 text-nowrap" style="font-size: 11.5px; font-weight: 500;">
                                    {{ $account->role->label() }}
                                </span>
                            </td>
                            <td>
                                <x-status-badge :status="$account->isActive() ? 'Active' : $account->status" />
                            </td>
                            <td class="text-nowrap">
                                @if ($account->last_login_at)
                                    <span class="small" style="color: #475569;">
                                        {{ $account->last_login_at->format('M d, Y · h:i A') }}
                                    </span>
                                @else
                                    <span class="admin-original-copy badge bg-light text-secondary fw-normal">Never</span>
                                    <span class="admin-desktop-copy d-none text-muted small">Never signed in</span>
                                @endif
                            </td>
                            <td class="text-end pe-4 admin-col-actions">
                                <div class="admin-actions-group">
                                    <button class="btn btn-sm admin-action-btn admin-action-edit" type="button"
                                        data-bs-toggle="modal" data-bs-target="#editUser{{ $account->id }}"
                                        title="Edit Account" aria-label="Edit Account">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    @if (!$account->is($user))
                                        <form method="POST"
                                            action="{{ $account->isActive() ? route('admin.users.deactivate', $account) : route('admin.users.restore', $account) }}"
                                            class="d-inline m-0">
                                            @csrf
                                            @if ($account->isActive())
                                                <button class="btn btn-sm admin-action-btn admin-action-deactivate" type="submit"
                                                    title="Deactivate Account" aria-label="Deactivate Account">
                                                    <i class="bi bi-person-slash"></i>
                                                </button>
                                            @else
                                                <button class="btn btn-sm admin-action-btn admin-action-activate" type="submit"
                                                    title="Activate Account" aria-label="Activate Account">
                                                    <i class="bi bi-person-check"></i>
                                                </button>
                                            @endif
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="admin-empty-state">
                                <span class="admin-empty-icon"><i class="bi bi-people"></i></span>
                                <h4 class="admin-empty-title">No User Accounts Found</h4>
                                <p class="admin-empty-desc">No accounts match the current filter criteria. Try adjusting your search term or filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $users->links() }}</div>

    {{-- Edit Modals --}}
    @foreach ($users as $account)
        @php
            $isFailedEdit = $failedForm === 'editUser'.$account->id;
        @endphp
        <div class="modal fade" id="editUser{{ $account->id }}" tabindex="-1" aria-hidden="true"
            aria-labelledby="editUserTitle{{ $account->id }}" @if($isFailedEdit) data-reopen-modal @endif>
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.users.update', $account) }}">
                        @csrf @method('PUT')
                        <input type="hidden" name="_account_form" value="editUser{{ $account->id }}">
                        <div class="modal-header">
                            <div class="d-flex align-items-center gap-3">
                                <div class="admin-metric-icon admin-icon-navy" style="width: 40px; height: 40px; border-radius: 10px; font-size: 18px;">
                                    <i class="bi bi-pencil-square"></i>
                                </div>
                                <div>
                                    <h2 id="editUserTitle{{ $account->id }}" class="modal-title h5 fw-bold mb-0">Edit User Account</h2>
                                    <p class="modal-subtitle">Update account credentials and portal role.</p>
                                </div>
                            </div>
                            <button class="btn-close" data-bs-dismiss="modal" type="button" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @if($isFailedEdit)
                                <div class="alert alert-danger" role="alert"><ul class="mb-0">
                                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                                </ul></div>
                            @endif
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary small" for="editName{{ $account->id }}">Name / Organization Name</label>
                                <input class="form-control" id="editName{{ $account->id }}" name="name"
                                    value="{{ $isFailedEdit ? old('name', $account->name) : $account->name }}" maxlength="150" required
                                    style="border-radius: 8px;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary small" for="editEmail{{ $account->id }}">Email Address</label>
                                <input class="form-control" id="editEmail{{ $account->id }}" type="email"
                                    name="email" value="{{ $isFailedEdit ? old('email', $account->email) : $account->email }}" maxlength="191" required
                                    style="border-radius: 8px;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary small" for="editRole{{ $account->id }}">Role</label>
                                <select class="form-select" id="editRole{{ $account->id }}" name="role" required @disabled($account->id === $user->id)
                                    style="border-radius: 8px;">
                                    @foreach (\App\Enums\UserRole::cases() as $role)
                                        <option value="{{ $role->value }}" @selected(($isFailedEdit ? old('role', $account->role->value) : $account->role->value) === $role->value)>{{ $role->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                @if($account->id === $user->id)
                                    <input type="hidden" name="role" value="{{ $account->role->value }}">
                                    <small class="text-muted d-block mt-1">You cannot change your own role or account status.</small>
                                @endif
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-secondary small" for="editPassword{{ $account->id }}">New Password (Optional)</label>
                                    <input type="password" name="password" class="form-control"
                                        id="editPassword{{ $account->id }}"
                                        placeholder="Leave blank to keep current"
                                        style="border-radius: 8px;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-secondary small" for="editPasswordConfirmation{{ $account->id }}">Confirm New Password</label>
                                    <input type="password" name="password_confirmation" class="form-control"
                                        id="editPasswordConfirmation{{ $account->id }}" placeholder="Re-enter new password"
                                        style="border-radius: 8px;">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn admin-btn-cancel px-3" data-bs-dismiss="modal">
                                <i class="bi bi-x-lg me-1"></i>Cancel
                            </button>
                            <button class="btn fw-semibold admin-btn-primary" type="submit">
                                <i class="bi bi-check-lg me-1"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    {{-- Add User Account Modal --}}
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true" aria-labelledby="addUserTitle" @if($failedForm === 'addUserModal') data-reopen-modal @endif>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                    <input type="hidden" name="_account_form" value="addUserModal">
                    <div class="modal-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="admin-metric-icon admin-icon-navy" style="width: 40px; height: 40px; border-radius: 10px; font-size: 18px;">
                                <i class="bi bi-person-plus"></i>
                            </div>
                            <div>
                                <h2 id="addUserTitle" class="modal-title h5 fw-bold mb-0">Create User Account</h2>
                                <p class="modal-subtitle">Enter account details and assign the appropriate portal role.</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if($failedForm === 'addUserModal')
                            <div class="alert alert-danger" role="alert"><ul class="mb-0">
                                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                            </ul></div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary small" for="adminName">Full Name / Organization Name</label>
                            <input class="form-control" id="adminName" name="name" placeholder="e.g. Maria Santos" maxlength="150" value="{{ $failedForm === 'addUserModal' ? old('name') : '' }}"
                                required style="border-radius: 8px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary small" for="adminEmail">Email Address</label>
                            <input class="form-control" id="adminEmail" type="email" name="email"
                                placeholder="e.g. user@dorsu.edu.ph" maxlength="191" value="{{ $failedForm === 'addUserModal' ? old('email') : '' }}" required
                                style="border-radius: 8px;">
                        </div>
                        <div class="mb-3">
                            @php
                                $availableRoles = \App\Enums\UserRole::cases();
                                $selectedRole = \App\Enums\UserRole::tryFrom((string) ($failedForm === 'addUserModal' ? old('role') : 'student')) ?? \App\Enums\UserRole::Student;
                            @endphp
                            <label class="form-label fw-semibold text-secondary small" for="roleInput">Role</label>
                            <input type="hidden" name="role" id="roleInput"
                                value="{{ $selectedRole->value }}" required>
                            <div class="dropdown" id="adminRoleDropdown">
                                <button type="button" id="adminRoleDropdownButton"
                                    class="btn btn-light border w-100 text-start d-flex align-items-center justify-content-between"
                                    data-bs-toggle="dropdown" aria-expanded="false" style="height: 38px; border-radius: 8px;">
                                    <span>{{ $selectedRole->label() }}</span>
                                    <i class="bi bi-chevron-down text-muted"></i>
                                </button>
                                <ul class="dropdown-menu w-100">
                                    @foreach ($availableRoles as $role)
                                        <li>
                                            <button type="button"
                                                class="dropdown-item role-option {{ $selectedRole === $role ? 'active' : '' }}"
                                                data-role-value="{{ $role->value }}"
                                                data-role-label="{{ $role->label() }}">
                                                {{ $role->label() }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small"
                                    for="adminPassword">Password</label>
                                <div class="position-relative">
                                    <input class="form-control pe-5" id="adminPassword" type="password" name="password"
                                        minlength="8" required style="border-radius: 8px;">
                                    <button type="button"
                                        class="position-absolute end-0 top-50 translate-middle-y text-muted pe-3 border-0 bg-transparent"
                                        aria-label="Show password"
                                        onclick="togglePasswordVisibility('adminPassword', this)">
                                        <i class="bi bi-eye text-muted"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small"
                                    for="adminPasswordConfirmation">Confirm Password</label>
                                <div class="position-relative">
                                    <input class="form-control pe-5" id="adminPasswordConfirmation" type="password"
                                        name="password_confirmation" required style="border-radius: 8px;">
                                    <button type="button"
                                        class="position-absolute end-0 top-50 translate-middle-y text-muted pe-3 border-0 bg-transparent"
                                        aria-label="Show password"
                                        onclick="togglePasswordVisibility('adminPasswordConfirmation', this)">
                                        <i class="bi bi-eye text-muted"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn admin-btn-cancel px-3" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn fw-semibold admin-btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Create Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function setFilter(inputId, value, label, dropdownId) {
            const input = document.getElementById(inputId);
            const dropdown = document.getElementById(dropdownId);

            input.value = value;
            dropdown.querySelector('button[data-bs-toggle="dropdown"] span').textContent = label;
            dropdown.querySelectorAll('.dropdown-item').forEach(function(option) {
                option.classList.toggle('active', option.textContent.trim() === label);
            });
            input.form.submit();
        }

        function togglePasswordVisibility(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            const isPassword = input.type === 'password';

            input.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('bi-eye', !isPassword);
            icon.classList.toggle('bi-eye-slash', isPassword);
            button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const roleInput = document.getElementById('roleInput');
            const roleButton = document.getElementById('adminRoleDropdownButton');
            const roleOptions = document.querySelectorAll('#adminRoleDropdown .role-option');

            roleOptions.forEach(function(option) {
                option.addEventListener('click', function() {
                    roleInput.value = option.dataset.roleValue;
                    roleButton.querySelector('span').textContent = option.dataset.roleLabel;
                    roleOptions.forEach(function(item) {
                        item.classList.toggle('active', item === option);
                    });
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const failedModal = document.querySelector('.modal[data-reopen-modal]');
            const modal = failedModal || (urlParams.get('action') === 'create' ? document.getElementById('addUserModal') : null);
            if (modal) bootstrap.Modal.getOrCreateInstance(modal).show();
        });
    </script>
@endsection
