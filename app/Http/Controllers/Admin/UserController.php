<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->filled('q'), fn($query) => $query->where(function ($query) use ($request): void {
                $query->where('name', 'like', '%' . $request->string('q') . '%')
                    ->orWhere('email', 'like', '%' . $request->string('q') . '%');
            }))
            ->when($request->filled('role'), fn($query) => $query->where('role', $request->string('role')))
            ->when($request->filled('status'), fn($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'user' => $request->user(),
            'users' => $users,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $status = ($validated['is_active'] ?? true) ? UserStatus::Active : UserStatus::Inactive;

            $account = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'status' => $status,
                'is_active' => $status === UserStatus::Active,
            ]);

            $this->ensureSponsorProfile($account);

            AuditLog::record('admin.user.created', 'users', $request->user(), $request->ip(), null, $request->userAgent(), $request->user()?->role?->value);
        });

        return back()->with('status', 'User account created.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'You cannot deactivate your own account.');

        $nextStatus = $user->isActive() ? UserStatus::Inactive : UserStatus::Active;
        $user->update(['status' => $nextStatus, 'is_active' => $nextStatus === UserStatus::Active]);
        AuditLog::record('admin.user.status_changed', 'users', $request->user(), $request->ip(), null, $request->userAgent(), $request->user()?->role?->value);

        return back()->with('status', "{$user->name}'s account is now {$nextStatus->value}.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($request->has('is_active')) {
            $validated['status'] = $request->boolean('is_active') ? UserStatus::Active : UserStatus::Inactive;
            $validated['is_active'] = $request->boolean('is_active');
        }

        if (filled($validated['password'] ?? null)) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        $this->ensureSponsorProfile($user);
        AuditLog::record('admin.user.updated', 'users', $request->user(), $request->ip(), null, $request->userAgent(), $request->user()?->role?->value);

        return back()->with('status', 'User account updated.');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        return $this->setStatus($request, $user, UserStatus::Inactive);
    }

    public function restore(Request $request, User $user): RedirectResponse
    {
        return $this->setStatus($request, $user, UserStatus::Active);
    }

    private function setStatus(Request $request, User $user, UserStatus $status): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'You cannot change your own account status.');
        $user->update(['status' => $status, 'is_active' => $status === UserStatus::Active]);
        AuditLog::record('admin.user.' . ($status === UserStatus::Active ? 'restored' : 'deactivated'), 'users', $request->user(), $request->ip(), null, $request->userAgent(), $request->user()?->role?->value);

        return back()->with('status', "{$user->name}'s account is now {$status->value}.");
    }

    private function ensureSponsorProfile(User $user): void
    {
        if (! $user->isSponsor()) {
            return;
        }

        Sponsor::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'company_organization_name' => $user->name,
                'contact_person' => $user->name,
                'contact_email' => $user->email,
            ],
        );
    }
}
