@extends('layouts.app')
@section('title', 'User Accounts')
@section('eyebrow', 'System Administrator')
@section('page-title', 'User Account Management')

@push('styles')
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
    <div class="card sf-card mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2">
                <div class="col-md-5">
                    <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search name or email..."
                        oninput="clearTimeout(window.searchTimer); window.searchTimer = setTimeout(() => this.form.submit(), 600)">
                </div>
                <div class="col-md-2">
                    <input type="hidden" name="role" id="filterRole" value="{{ request('role') }}">
                    <div class="dropdown filter-dropdown" id="roleFilterDropdown">
                        <button type="button"
                            class="btn btn-light border w-100 text-start d-flex align-items-center justify-content-between"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <span>{{ request('role') ? \App\Enums\UserRole::tryFrom(request('role'))?->label() : 'All roles' }}</span>
                            <i class="bi bi-chevron-down text-muted"></i>
                        </button>
                        <ul class="dropdown-menu w-100">
                            <li><button type="button" class="dropdown-item {{ request('role') === '' ? 'active' : '' }}"
                                    onclick="setFilter('filterRole', '', 'All roles', 'roleFilterDropdown')">All
                                    roles</button></li>
                            @foreach (\App\Enums\UserRole::cases() as $role)
                                <li><button type="button"
                                        class="dropdown-item {{ request('role') === $role->value ? 'active' : '' }}"
                                        onclick="setFilter('filterRole', '{{ $role->value }}', '{{ $role->label() }}', 'roleFilterDropdown')">{{ $role->label() }}</button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="hidden" name="status" id="filterStatus" value="{{ request('status') }}">
                    <div class="dropdown filter-dropdown" id="statusFilterDropdown">
                        <button type="button"
                            class="btn btn-light border w-100 text-start d-flex align-items-center justify-content-between"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <span>{{ request('status') === 'active' ? 'Active' : (request('status') === 'inactive' ? 'Deactivated' : 'All statuses') }}</span>
                            <i class="bi bi-chevron-down text-muted"></i>
                        </button>
                        <ul class="dropdown-menu w-100">
                            <li><button type="button" class="dropdown-item {{ request('status') === '' ? 'active' : '' }}"
                                    onclick="setFilter('filterStatus', '', 'All statuses', 'statusFilterDropdown')">All
                                    statuses</button></li>
                            <li><button type="button"
                                    class="dropdown-item {{ request('status') === 'active' ? 'active' : '' }}"
                                    onclick="setFilter('filterStatus', 'active', 'Active', 'statusFilterDropdown')">Active</button>
                            </li>
                            <li><button type="button"
                                    class="dropdown-item {{ request('status') === 'inactive' ? 'active' : '' }}"
                                    onclick="setFilter('filterStatus', 'inactive', 'Deactivated', 'statusFilterDropdown')">Deactivated</button>
                            </li>
                        </ul>
                    </div>
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
                                <span
                                    class="badge {{ $account->isActive() ? 'bg-success-subtle text-success-emphasis' : 'bg-secondary-subtle text-secondary-emphasis' }}">
                                    {{ $account->isActive() ? 'Active' : 'Deactivated' }}
                                </span>
                            </td>
                            <td>
                                @if ($account->last_login_at)
                                    <span class="small"
                                        style="color: #475569;">{{ $account->last_login_at->diffForHumans() }}</span>
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
                                            <i
                                                class="bi {{ $account->isActive() ? 'bi-person-dash' : 'bi-person-check' }}"></i>
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
                                <label class="form-label fw-semibold" for="editName{{ $account->id }}"
                                    style="font-size: 0.85rem; color: #475569;">Name / Organization Name</label>
                                <input class="form-control" id="editName{{ $account->id }}" name="name"
                                    value="{{ $account->name }}" required
                                    style="border-radius: 8px; background-color: #f8fafc;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="editEmail{{ $account->id }}"
                                    style="font-size: 0.85rem; color: #475569;">Email</label>
                                <input class="form-control" id="editEmail{{ $account->id }}" type="email"
                                    name="email" value="{{ $account->email }}" required
                                    style="border-radius: 8px; background-color: #f8fafc;">
                            </div>
                            <label class="form-label fw-semibold" for="editRole{{ $account->id }}"
                                style="font-size: 0.85rem; color: #475569;">Role</label>
                            <select class="form-select" id="editRole{{ $account->id }}" name="role" required
                                style="border-radius: 8px; background-color: #f8fafc;">
                                @foreach (\App\Enums\UserRole::cases() as $role)
                                    <option value="{{ $role->value }}" @selected($account->role === $role)>{{ $role->label() }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="mb-3 mt-3">
                                <label class="form-label fw-bold small" for="editPassword{{ $account->id }}"
                                    style="color: #475569;">New Password (Optional)</label>
                                <input type="password" name="password" class="form-control"
                                    id="editPassword{{ $account->id }}"
                                    placeholder="Leave blank to keep current password"
                                    style="border-radius: 8px; background-color: #f8fafc;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small" for="editPasswordConfirmation{{ $account->id }}"
                                    style="color: #475569;">Confirm New Password</label>
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
                    <div class="modal-header border-bottom pb-3">
                        <div>
                            <h5 class="modal-title fw-bold text-dark"><i class="bi bi-person-plus-fill me-2"
                                    style="color: #0F2942;"></i>Add User Account</h5>
                            <p class="text-muted small mb-0">Create a new institutional or student account credential.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary small" for="adminName">Name / Organization
                                Name</label>
                            <input class="form-control" id="adminName" name="name" placeholder="e.g. Maria Santos"
                                required style="border-radius: 8px; background-color: #f8fafc;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary small" for="adminEmail">Email</label>
                            <input class="form-control" id="adminEmail" type="email" name="email"
                                placeholder="student@sponsorflow.test" required
                                style="border-radius: 8px; background-color: #f8fafc;">
                        </div>
                        <div class="mb-3">
                            @php($availableRoles = \App\Enums\UserRole::cases())
                            <label class="form-label fw-semibold text-secondary small" for="roleInput">Role</label>
                            <input type="hidden" name="role" id="roleInput"
                                value="{{ $availableRoles[0]->value ?? '' }}" required>
                            <div class="dropdown" id="adminRoleDropdown">
                                <button type="button" id="adminRoleDropdownButton"
                                    class="btn btn-light border w-100 text-start d-flex align-items-center justify-content-between"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <span>{{ $availableRoles[0]->label() ?? 'Select role' }}</span>
                                    <i class="bi bi-chevron-down text-muted"></i>
                                </button>
                                <ul class="dropdown-menu w-100">
                                    @foreach ($availableRoles as $role)
                                        <li>
                                            <button type="button"
                                                class="dropdown-item role-option {{ $loop->first ? 'active' : '' }}"
                                                data-role-value="{{ $role->value }}"
                                                data-role-label="{{ $role->label() }}">
                                                {{ $role->label() }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col">
                                <label class="form-label fw-semibold text-secondary small"
                                    for="adminPassword">Password</label>
                                <div class="position-relative">
                                    <input class="form-control pe-5" id="adminPassword" type="password" name="password"
                                        minlength="8" required style="background-color: #f8fafc;">
                                    <button type="button"
                                        class="position-absolute end-0 top-50 translate-middle-y text-muted pe-3 border-0 bg-transparent"
                                        aria-label="Show password"
                                        onclick="togglePasswordVisibility('adminPassword', this)">
                                        <i class="bi bi-eye text-muted"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col">
                                <label class="form-label fw-semibold text-secondary small"
                                    for="adminPasswordConfirmation">Confirm</label>
                                <div class="position-relative">
                                    <input class="form-control pe-5" id="adminPasswordConfirmation" type="password"
                                        name="password_confirmation" required style="background-color: #f8fafc;">
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
                    <div class="modal-footer border-top bg-light-subtle py-2.5 px-3">
                        <button type="button" class="btn btn-outline-secondary px-3"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn text-white px-4 fw-semibold"
                            style="background-color: #0F2942;">Create account</button>
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
            if (urlParams.get('action') === 'create') {
                const addUserModal = new bootstrap.Modal(document.getElementById('addUserModal'));
                if (addUserModal) addUserModal.show();
            }
        });
    </script>
@endsection
