<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function read(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $notification->notifiable_type === $user->getMorphClass()
                && (int) $notification->notifiable_id === (int) $user->getKey(),
            404,
        );

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        $url = $notification->data['url'] ?? null;

        if (is_string($url) && $this->isLocalUrl($url)) {
            return redirect()->to($url);
        }

        return redirect()->route('student.applications.index');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }

    private function isLocalUrl(string $url): bool
    {
        if (str_starts_with($url, '/')) {
            return true;
        }

        $targetHost = parse_url($url, PHP_URL_HOST);
        $appHost = parse_url(url('/'), PHP_URL_HOST);

        return is_string($targetHost)
            && is_string($appHost)
            && strcasecmp($targetHost, $appHost) === 0;
    }
}