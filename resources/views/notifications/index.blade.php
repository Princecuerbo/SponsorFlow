@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="container-fluid px-0 px-md-3" style="max-width: 900px; margin: 0 auto;">

    {{-- Header Section --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h1 class="h4 fw-bold mb-0 text-dark">Notifications</h1>
                @if ($user->unreadNotifications->count() > 0)
                    <span class="badge bg-danger rounded-pill px-2.5 py-1 small fw-semibold">
                        {{ $user->unreadNotifications->count() }} new
                    </span>
                @endif
            </div>
            <p class="text-secondary small mb-0">Stay updated on your application status, verifications, and announcements.</p>
        </div>

        {{-- Mark All as Read Button --}}
        @if ($user->unreadNotifications->count() > 0)
            <div>
                <form method="POST" action="{{ route('notifications.readAll') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5 rounded-3 px-3 py-1.5 fw-semibold shadow-sm">
                        <i class="bi bi-check2-all fs-6"></i>
                        <span>Mark all as read</span>
                    </button>
                </form>
            </div>
        @endif
    </div>

    @php
        $items = $notifications ?? $user->notifications;
    @endphp

    {{-- Notifications List or Empty State --}}
    @if ($items->isEmpty())
        <div class="card sf-card border-0 shadow-sm rounded-4 p-5 text-center">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light text-secondary mx-auto mb-3"
                style="width: 64px; height: 64px;">
                <i class="bi bi-bell-slash fs-2 text-muted"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">No notifications yet</h5>
            <p class="text-secondary small mb-0" style="max-width: 420px; margin: 0 auto;">
                You don't have any notifications right now. When there are updates regarding your applications, programs, or account, they will appear here.
            </p>
        </div>
    @else
        <div class="card sf-card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="list-group list-group-flush">
                @foreach ($items as $notification)
                    @php
                        $isUnread = is_null($notification->read_at);
                        $icon = $notification->data['icon'] ?? 'bell';
                        $title = $notification->data['title'] ?? 'Notification';
                        $message = $notification->data['message'] ?? '';
                        $url = $notification->data['url'] ?? null;
                    @endphp

                    <div class="list-group-item p-3 p-md-4 border-bottom {{ $isUnread ? 'border-start border-3 border-primary' : '' }}"
                        style="background-color: {{ $isUnread ? '#f8faff' : '#ffffff' }}; transition: background-color 0.2s ease;">
                        <div class="d-flex flex-column flex-md-row align-items-start gap-3">

                            {{-- Notification Icon --}}
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 {{ $isUnread ? 'bg-primary text-white shadow-sm' : 'bg-light text-secondary border' }}"
                                style="width: 42px; height: 42px;">
                                <i class="bi bi-{{ $icon }} fs-5"></i>
                            </div>

                            {{-- Notification Content --}}
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    @if (!empty($url))
                                        <a href="{{ $url }}" class="text-decoration-none {{ $isUnread ? 'text-dark fw-bold' : 'text-secondary fw-semibold' }}" style="font-size: 0.95rem;">
                                            {{ $title }}
                                        </a>
                                    @else
                                        <span class="{{ $isUnread ? 'text-dark fw-bold' : 'text-secondary fw-semibold' }}" style="font-size: 0.95rem;">
                                            {{ $title }}
                                        </span>
                                    @endif

                                    {{-- Active indicator for unread notifications --}}
                                    @if ($isUnread)
                                        <span class="badge rounded-pill bg-primary" style="width: 8px; height: 8px; padding: 0;" title="Unread notification"></span>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill fw-semibold"
                                            style="font-size: 0.7rem; padding: 0.2rem 0.55rem;">
                                            Unread
                                        </span>
                                    @endif
                                </div>

                                @if (!empty($message))
                                    <p class="text-secondary small mb-2" style="line-height: 1.5;">
                                        {{ $message }}
                                    </p>
                                @endif

                                <div class="d-flex align-items-center gap-3 text-muted small" style="font-size: 0.8rem;">
                                    <span>
                                        <i class="bi bi-clock me-1"></i>{{ $notification->created_at->format('M d, Y, h:i A') }}
                                    </span>
                                    @if (!empty($url))
                                        <span>·</span>
                                        <a href="{{ $url }}" class="text-primary text-decoration-none fw-medium">
                                            View details <i class="bi bi-arrow-right small"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>

                            {{-- Individual Action / Mark as read button --}}
                            <div class="ms-md-auto flex-shrink-0 align-self-start pt-1">
                                @if ($isUnread)
                                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="m-0">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-white border shadow-sm text-secondary d-inline-flex align-items-center gap-1.5 rounded-3 px-3 py-1.5"
                                            title="Mark as read" style="font-size: 0.8rem; background: #ffffff;">
                                            <i class="bi bi-check2 text-primary"></i>
                                            <span class="fw-semibold">Mark as read</span>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small d-inline-flex align-items-center gap-1 px-2 py-1">
                                        <i class="bi bi-check2-all text-secondary"></i>
                                        <span>Read</span>
                                    </span>
                                @endif
                            </div>

                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination if paginated --}}
            @if (method_exists($items, 'hasPages') && $items->hasPages())
                <div class="card-footer bg-white border-top py-3 d-flex justify-content-center">
                    {{ $items->links() }}
                </div>
            @endif
        </div>
    @endif

</div>
@endsection
