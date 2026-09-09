<?php

namespace App\Notifications;

use App\Models\SponsorshipProgram;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewSponsorshipProgramOpened extends Notification
{
    use Queueable;

    public function __construct(public readonly SponsorshipProgram $program)
    {
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
        return [
            'icon'       => 'briefcase',
            'title'      => 'New sponsorship program opened',
            'message'    => "A new sponsorship program, {$this->program->program_name}, is now open for applications.",
            'url'        => route('student.applications.create', $this->program),
            'program_id' => $this->program->id,
        ];
    }
}