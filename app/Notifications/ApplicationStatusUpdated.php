<?php

namespace App\Notifications;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ApplicationStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Application $application,
        public readonly ApplicationStatus $status,
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
        $programName = $this->application->sponsorshipProgram->program_name ?? 'sponsorship program';

        return [
            'icon'           => 'patch-check',
            'title'          => 'Application status updated',
            'message'        => "Your application for {$programName} is now {$this->status->value}.",
            'url'            => route('student.applications.show', $this->application),
            'application_id' => $this->application->id,
            'status'         => $this->status->value,
        ];
    }
}