<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'target_module',
        'details',
        'ip_address',
        'user_agent',
        'role',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        string $action,
        string $targetModule,
        ?User $user = null,
        ?string $ipAddress = null,
        ?string $details = null,
        ?string $userAgent = null,
        ?string $role = null,
    ): self {
        $resolvedUserAgent = $userAgent ?? request()?->userAgent();
        $resolvedRole = $role ?? ($user?->role instanceof \BackedEnum ? $user->role->value : $user?->role);

        return static::create([
            'user_id' => $user?->id,
            'action' => $action,
            'target_module' => $targetModule,
            'details' => $details,
            'ip_address' => $ipAddress,
            'user_agent' => $resolvedUserAgent,
            'role' => $resolvedRole,
        ]);
    }
}
