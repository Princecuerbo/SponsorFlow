<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DatabaseBackup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $today = now()->startOfDay();

        return view('admin.dashboard', [
            'user' => $request->user(),
            'metrics' => [
                'totalUsers' => User::query()->count(),
                'activeUsers' => User::query()->where('status', 'active')->count(),
                'todayLogs' => AuditLog::query()->where('created_at', '>=', $today)->count(),
                'lastBackup' => DatabaseBackup::query()->latest('created_at')->first(),
            ],
            'roleCounts' => collect(UserRole::cases())->mapWithKeys(
                fn (UserRole $role): array => [$role->value => User::query()->where('role', $role)->where('status', 'active')->count()],
            ),
            'recentLogs' => AuditLog::query()->with('user')->latest()->limit(10)->get(),
        ]);
    }
}
