<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::query()->with('user')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $search = $request->string('q')->toString();
                $query->where(function ($query) use ($search): void {
                    $query->where('action', 'like', '%'.$search.'%')
                        ->orWhere('target_module', 'like', '%'.$search.'%');
                });
            })
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($query) use ($search): void {
                    $query->where('action', 'like', '%'.$search.'%')
                        ->orWhere('target_module', 'like', '%'.$search.'%');
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')->toString()))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('to')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $roles = collect(UserRole::cases())->mapWithKeys(
            fn (UserRole $role): array => [$role->value => $role->label()],
        )->all();

        return view('admin.audit_logs.index', compact('logs', 'roles'));
    }
}