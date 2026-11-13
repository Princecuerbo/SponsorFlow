<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Sponsor;
use App\Models\User;
use App\Support\AdminAccountProtection;
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
            ->when($request->filled('q'), fn ($query) => $query->where(function ($query) use ($request): void {
                $query->where('name', 'like', '%'.$request->string('q').'%')
                    ->orWhere('email', 'like', '%'.$request->string('q').'%');
            }))
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
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

            $this->recordAccountChange($request, $account, 'created');
        });

        return back()->with('status', 'User account created.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        return $this->setStatus($request, $user);
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

        if (filled($validated['password'] ?? null)) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        DB::transaction(function () use ($request, $user, $validated): void {
            [$account, $activeAdmins] = $this->lockAccounts($request, $user);
            $status = array_key_exists('is_active', $validated)
                ? ($validated['is_active'] ? UserStatus::Active : UserStatus::Inactive)
                : $account->status;
            AdminAccountProtection::validate($request->user(), $account, UserRole::from($validated['role']), $status, $activeAdmins);
            $before = $account->only(['name', 'email', 'role', 'status']);
            $changes = $validated;
            unset($changes['is_active']);
            $changes['status'] = $status;
            $account->update($changes);
            $this->ensureSponsorProfile($account);
            $this->recordAccountChange($request, $account, 'updated', $before, isset($changes['password']));
        }, 3);

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

    private function setStatus(Request $request, User $user, ?UserStatus $status = null): RedirectResponse
    {
        $nextStatus = DB::transaction(function () use ($request, $user, $status): UserStatus {
            [$account, $activeAdmins] = $this->lockAccounts($request, $user);
            $nextStatus = $status ?? ($account->isActive() ? UserStatus::Inactive : UserStatus::Active);
            AdminAccountProtection::validate($request->user(), $account, $account->role, $nextStatus, $activeAdmins);
            $before = $account->only(['name', 'email', 'role', 'status']);
            $account->update(['status' => $nextStatus]);
            $this->recordAccountChange($request, $account, $nextStatus === UserStatus::Active ? 'restored' : 'deactivated', $before);

            return $nextStatus;
        }, 3);

        return back()->with('status', "{$user->name}'s account is now {$nextStatus->value}.");
    }

    private function lockAccounts(Request $request, User $target): array
    {
        // Always lock administrators first, in the same order, to serialize demotions/deactivations.
        $admins = User::query()->where('role', UserRole::Admin)->orderBy('id')->lockForUpdate()->get();
        $actor = $admins->firstWhere('id', $request->user()->id);
        abort_unless($actor?->isActive(), 403);

        return [User::query()->lockForUpdate()->findOrFail($target->id), $admins->filter(fn (User $admin) => $admin->isActive())->count()];
    }

    private function recordAccountChange(Request $request, User $account, string $action, array $before = [], bool $passwordChanged = false): void
    {
        AuditLog::record('admin.user.'.$action, 'users', $request->user(), $request->ip(), json_encode([
            'user_id' => $account->id,
            'before' => $before,
            'after' => $account->only(['name', 'email', 'role', 'status']),
            'password_changed' => $passwordChanged,
        ], JSON_THROW_ON_ERROR), $request->userAgent(), $request->user()?->role?->value);
    }

    private function ensureSponsorProfile(User $user): void
    {
        if (! $user->isSponsor()) {
            return;
        }

        Sponsor::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'company_organization_name' => $user->name,
                'contact_person' => $user->name,
                'contact_email' => $user->email,
            ],
        );
    }
}
