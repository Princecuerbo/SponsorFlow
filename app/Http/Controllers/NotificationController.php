<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Notifications are available only to student accounts.
     */
    private function ensureStudent(Request $request): void
    {
        abort_unless(
            $request->user()?->isStudent() ?? false,
            403,
            'Notifications are only available for student accounts.',
        );
    }

    /**
     * Display a listing of the authenticated user's notifications.
     */
    public function index(Request $request): View
    {
        $this->ensureStudent($request);

        $user = $request->user();
        $notifications = $user->notifications()->paginate(15);

        return view('notifications.index', [
            'user' => $user,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark an individual notification as read.
     */
    public function markAsRead(Request $request, string $id): RedirectResponse
    {
        $this->ensureStudent($request);

        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $this->ensureStudent($request);

        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
