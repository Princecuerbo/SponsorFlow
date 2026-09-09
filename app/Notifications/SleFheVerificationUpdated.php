<?php

namespace App\Notifications;

use App\Models\StudentProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SleFheVerificationUpdated extends Notification
{
    use Queueable;

    public function __construct(
        public readonly StudentProfile $profile,
        public readonly string $outcome,
    ) {
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $verified = $this->outcome === 'verified';

        return [
            'icon'   => 'shield-check',
            'title'  => $verified ? 'SLE-FHE verification approved' : 'SLE-FHE verification needs fixes',
            'message'=> $verified
                ? 'Your SLE-FHE verification has been approved. You can now apply to sponsorship programs.'
                : 'Your SLE-FHE verification was returned for correction. Please review and update your details.',
            'url'    => route('student.sle-fhe'),
        ];
    }
}