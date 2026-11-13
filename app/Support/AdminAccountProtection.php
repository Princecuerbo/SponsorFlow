<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AdminAccountProtection
{
    /** Call with the administrator rows and target account locked in one transaction. */
    public static function validate(User $actor, User $target, UserRole $role, UserStatus $status, int $activeAdmins): void
    {
        if ($actor->is($target) && ($role !== $target->role || $status !== $target->status)) {
            throw ValidationException::withMessages([
                'account' => 'You cannot change your own role or account status.',
            ]);
        }

        if ($target->isAdmin() && $target->isActive() && $activeAdmins <= 1
            && ($role !== UserRole::Admin || $status !== UserStatus::Active)) {
            throw ValidationException::withMessages([
                'account' => 'At least one active administrator must remain.',
            ]);
        }
    }
}
