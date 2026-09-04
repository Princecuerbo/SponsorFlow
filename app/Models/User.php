<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'last_login_at',
        'privacy_consent_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'last_login_at' => 'datetime',
            'privacy_consent_at' => 'datetime',
        ];
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function sponsor(): HasOne
    {
        return $this->hasOne(Sponsor::class);
    }

    public function uploadedFixedLists(): HasMany
    {
        return $this->hasMany(FixedList::class, 'uploaded_by_fassg_id');
    }

    public function uploadedSponsorApprovals(): HasMany
    {
        return $this->hasMany(SponsorApproval::class, 'uploaded_by_sponsor_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function databaseBackups(): HasMany
    {
        return $this->hasMany(DatabaseBackup::class, 'created_by_user_id');
    }

    public function hasAnyRole(UserRole|string ...$roles): bool
    {
        $allowed = array_map(
            static fn(UserRole|string $role): UserRole => $role instanceof UserRole
                ? $role
                : UserRole::from($role),
            $roles,
        );

        return in_array($this->role, $allowed, true);
    }

    public function isStudent(): bool
    {
        return $this->role === UserRole::Student;
    }

    public function isFassg(): bool
    {
        return $this->role === UserRole::Fassg;
    }

    public function isSponsor(): bool
    {
        return $this->role === UserRole::Sponsor;
    }

    public function isAccounting(): bool
    {
        return $this->role === UserRole::Accounting;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function setIsActiveAttribute(bool $value): void
    {
        $this->attributes['status'] = $value ? UserStatus::Active->value : UserStatus::Inactive->value;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function hasConsentedToPrivacy(): bool
    {
        return $this->privacy_consent_at !== null;
    }

    public function homeRoute(): string
    {
        return match ($this->role) {
            UserRole::Student => 'student.dashboard',
            UserRole::Fassg => 'fassg.dashboard',
            UserRole::Sponsor => 'sponsor.dashboard',
            UserRole::Accounting => 'accounting.dashboard',
            UserRole::Admin => 'admin.dashboard',
        };
    }
}
