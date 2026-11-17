@extends('layouts.app')

@php
    $unreadCount = $user->unreadNotifications->count();
    $items = $notifications ?? $user->notifications;
@endphp

@section('title', 'Notifications')
@section('eyebrow', 'Student Portal · Notifications')
@section('page-title')
    Notifications
    @if ($unreadCount > 0)
        <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 small fw-semibold align-middle ms-2">
            {{ $unreadCount }} new
        </span>
    @endif
@endsection
@section('subtitle', 'Stay updated on your application status, verifications, and announcements.')

@push('styles')
<style>
    /* SponsorFlow Student Notifications Panel */
    .sf-notif-panel {
        background-color: #FFFFFF !important;
        border: 1px solid var(--sf-border-color, #E1E8EC) !important;
        border-radius: 14px !important;
        box-shadow: 0 1px 3px 0 rgba(15, 41, 66, 0.04), 0 2px 6px 0 rgba(15, 41, 66, 0.02) !important;
        padding: 1.5rem;
        width: 100%;
    }

    .sf-notif-panel-header {
        border-bottom: 1px solid var(--sf-border-color, #E1E8EC);
        padding-bottom: 1rem;
        margin-bottom: 0.5rem;
    }

    .sf-btn-refresh {
        background-color: #E8F0F6 !important;
        color: #0F2942 !important;
        border: 1px solid #D2E1ED !important;
        font-size: 0.8rem !important;
        font-weight: 600 !important;
        padding: 0.35rem 0.75rem !important;
        border-radius: 8px !important;
        text-decoration: none !important;
        transition: all 0.15s ease-in-out !important;
    }

    .sf-btn-refresh:hover,
    .sf-btn-refresh:focus {
        background-color: #D9E8F3 !important;
        color: #0F2942 !important;
        border-color: #BACDDC !important;
        box-shadow: 0 1px 3px rgba(15, 41, 66, 0.08) !important;
    }

    .sf-notif-item {
        padding: 1rem 0.75rem;
        border-bottom: 1px solid var(--sf-border-color, #E1E8EC);
        border-radius: 10px;
        transition: background-color 0.15s ease;
    }

    .sf-notif-item:last-child {
        border-bottom: none;
    }

    .sf-notif-item:hover {
        background-color: #F8FAFC;
    }

    .sf-notif-item.is-unread {
        background-color: #FAFBFD;
        border-left: 3.5px solid #123B59;
        padding-left: 0.75rem;
    }

    .sf-notif-icon-box {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: grid;
        place-items: center;
        background-color: #F1F5F9;
        border: 1px solid #E1E8EC;
    }

    .sf-notif-item.is-unread .sf-notif-icon-box {
        background-color: #E8F0F6;
        border-color: #D2E1ED;
    }

    .sf-notif-title {
        color: var(--sf-slate, #0F2942);
        font-size: 0.925rem;
        line-height: 1.4;
    }

    .sf-notif-item.is-unread .sf-notif-title {
        font-weight: 700;
        color: #0F2942;
    }

    .sf-notif-item.is-read .sf-notif-title {
        font-weight: 600;
        color: #475569;
    }

    .sf-notif-title:hover {
        color: #0F2942;
    }

    .sf-notif-badge-unread {
        display: inline-block;
        background-color: #E8F0F6;
        color: #0F2942;
        border: 1px solid #D2E1ED;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.1rem 0.5rem;
        border-radius: 20px;
        line-height: 1.4;
    }

    .sf-notif-message {
        color: var(--sf-muted, #64748B);
        font-size: 0.85rem;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    .sf-notif-meta {
        font-size: 0.8rem;
        color: var(--sf-muted, #64748B);
    }

    .sf-notif-link {
        color: var(--sf-navy, #0F2942) !important;
        font-size: 0.8rem;
    }

    .sf-notif-link:hover {
        color: var(--sf-navy-deep, #0A1E31) !important;
        text-decoration: underline !important;
    }

    .sf-btn-mark-read {
        background-color: #FFFFFF !important;
        border: 1px solid var(--sf-border-color, #E1E8EC) !important;
        color: #475569 !important;
        font-size: 0.775rem !important;
        font-weight: 600 !important;
        transition: all 0.15s ease !important;
    }

    .sf-btn-mark-read:hover {
        background-color: #F8FAFC !important;
        color: #0F2942 !important;
        border-color: #CBD5E1 !important;
    }

    /* Minimal Empty State */
    .sf-notif-empty-state {
        min-height: 200px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 2.5rem 1.5rem;
    }

    .sf-notif-empty-title {
        color: #4B5563;
        font-size: 0.95rem;
        font-weight: 600;
    }

    .sf-notif-empty-subtitle {
        color: var(--sf-muted, #64748B);
        font-size: 0.825rem;
    }

    @media (max-width: 767.98px) {
        .sf-notif-panel {
            padding: 1rem;
            border-radius: 12px !important;
        }

        .sf-notif-item {
            padding: 0.875rem 0.35rem;
        }

        .sf-notif-item.is-unread {
            padding-left: 0.5rem;
        }

        .sf-notif-icon-box {
            width: 36px;
            height: 36px;
            font-size: 0.95rem;
        }
    }
</style>
@endpush

@section('content')
<div class="sf-notif-panel">

    {{-- Clean Panel Header --}}
    <div class="sf-notif-panel-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
            <h3 class="h6 sf-heading mb-0 fw-bold">Recent Notifications</h3>
            @if ($unreadCount > 0)
                <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-2 py-0.5 fw-semibold" style="font-size: 0.72rem;">
                    {{ $unreadCount }} unread
                </span>
            @endif
        </div>

        <div class="d-flex align-items-center gap-2">
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.readAll') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 rounded-3 px-2.5 py-1 fw-semibold shadow-sm" style="font-size: 0.8rem;">
                        <i class="bi bi-check2-all" aria-hidden="true"></i>
                        <span>Mark all as read</span>
                    </button>
                </form>
            @endif

            <a href="{{ route('notifications.index') }}" class="btn btn-sm sf-btn-refresh d-inline-flex align-items-center gap-1 shadow-sm" title="Refresh notification list">
                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                <span>Refresh</span>
            </a>
        </div>
    </div>

    {{-- Notification List or Minimal Empty State --}}
    @if ($items->isEmpty())
        <div class="sf-notif-empty-state">
            <p class="sf-notif-empty-title mb-1">No notifications yet.</p>
            <p class="sf-notif-empty-subtitle mb-0">Updates about your verification and sponsorship applications will appear here.</p>
        </div>
    @else
        <div class="sf-notif-list">
            @foreach ($items as $notification)
                @php
                    $isUnread = is_null($notification->read_at);
                    $icon = $notification->data['icon'] ?? 'bell';
                    $title = $notification->data['title'] ?? 'Notification';
                    $message = $notification->data['message'] ?? '';
                    $url = $notification->data['url'] ?? null;

                    $iconColor = match($icon) {
                        'x-circle' => 'text-danger bi-x-circle-fill',
                        'patch-check' => 'text-success bi-patch-check-fill',
                        'check2-circle', 'shield-check' => 'text-success bi-check-circle-fill',
                        'exclamation-circle' => 'text-warning bi-exclamation-circle-fill',
                        'briefcase' => 'text-primary bi-briefcase-fill',
                        default => 'text-secondary bi-bell-fill',
                    };
                @endphp

                <div @class([
                    'sf-notif-item',
                    'is-unread' => $isUnread,
                    'is-read' => ! $isUnread,
                ])>
                    <div class="d-flex align-items-start gap-3">
                        {{-- Icon Box --}}
                        <div class="sf-notif-icon-box flex-shrink-0" aria-hidden="true">
                            <i class="bi {{ $iconColor }}"></i>
                        </div>

                        {{-- Details --}}
                        <div class="sf-notif-content flex-grow-1 min-w-0">
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                @if (!empty($url))
                                    <a href="{{ route('student.notifications.read', $notification) }}" class="sf-notif-title text-decoration-none">
                                        {{ $title }}
                                    </a>
                                @else
                                    <span class="sf-notif-title">
                                        {{ $title }}
                                    </span>
                                @endif

                                @if ($isUnread)
                                    <span class="sf-notif-badge-unread">Unread</span>
                                @endif
                            </div>

                            @if (!empty($message))
                                <p class="sf-notif-message mb-2">
                                    {{ $message }}
                                </p>
                            @endif

                            <div class="d-flex align-items-center gap-3 sf-notif-meta flex-wrap">
                                <span>
                                    <i class="bi bi-clock me-1" aria-hidden="true"></i>{{ $notification->created_at->format('M d, Y, h:i A') }}
                                </span>
                                @if (!empty($url))
                                    <span class="opacity-50">·</span>
                                    <a href="{{ route('student.notifications.read', $notification) }}" class="sf-notif-link fw-semibold text-decoration-none">
                                        View details <i class="bi bi-arrow-right small" aria-hidden="true"></i>
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="sf-notif-action flex-shrink-0 ms-auto align-self-start pt-1">
                            @if ($isUnread)
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="m-0">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm sf-btn-mark-read d-inline-flex align-items-center gap-1 rounded-3 px-2.5 py-1"
                                        title="Mark as read">
                                        <i class="bi bi-check2" aria-hidden="true"></i>
                                        <span class="d-none d-sm-inline">Mark as read</span>
                                    </button>
                                </form>
                            @else
                                <span class="text-muted small d-inline-flex align-items-center gap-1 py-1" title="Notification read">
                                    <i class="bi bi-check2-all text-secondary" aria-hidden="true"></i>
                                    <span class="d-none d-sm-inline">Read</span>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination if present --}}
        @if (method_exists($items, 'hasPages') && $items->hasPages())
            <div class="pt-3 border-top mt-3 d-flex justify-content-center">
                {{ $items->links() }}
            </div>
        @endif
    @endif

</div>
@endsection
