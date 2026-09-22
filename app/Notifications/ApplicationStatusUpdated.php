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
        public readonly ApplicationStatus|string $status,
        public readonly ?string $customTitle = null,
        public readonly ?string $customMessage = null,
    ) {}

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
        $statusValue = $this->status instanceof ApplicationStatus
            ? $this->status->value
            : (string) $this->status;

        $normalized = strtolower(trim(str_replace(['-', '_'], ' ', $statusValue)));

        if ($normalized === 'rejected') {
            $title = $this->customTitle ?? 'Application Status Update: Rejected';
            $message = $this->customMessage ?? "Your application for {$programName} has been reviewed and was not selected/rejected. Please check your student dashboard for details.";
            $icon = 'x-circle';
        } else {
            $statusName = match ($normalized) {
                'verified' => 'Verified',
                'sponsor reviewed', 'under sponsor review' => 'Under Sponsor Review',
                'shortlisted' => 'Shortlisted',
                'approved' => 'Approved',
                'ongoing' => 'Ongoing',
                'resubmission requested' => 'Resubmission Requested',
                default => ucwords(str_replace('_', ' ', $statusValue)),
            };

            $title = $this->customTitle ?? 'Application Progress Update';
            $message = $this->customMessage ?? "Your application for {$programName} has moved to {$statusName}.";
            $icon = match ($statusName) {
                'Approved' => 'patch-check',
                'Verified' => 'check2-circle',
                'Resubmission Requested' => 'exclamation-circle',
                default => 'info-circle',
            };
        }

        return [
            'icon' => $icon,
            'title' => $title,
            'message' => $message,
            'url' => route('student.applications.show', $this->application),
            'application_id' => $this->application->id,
            'status' => $statusValue,
        ];
    }
}
