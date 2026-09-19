<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Notifications\ApplicationStatusUpdated;

class NotificationService
{
    /**
     * Trigger a student notification for an application rejection.
     */
    public static function notifyApplicationRejected(Application $application, ?string $reason = null): void
    {
        $application->loadMissing(['studentProfile.user', 'sponsorshipProgram']);
        $studentUser = $application->studentProfile?->user;

        if ($studentUser !== null) {
            $studentUser->notify(new ApplicationStatusUpdated($application, ApplicationStatus::Rejected));
        }
    }

    /**
     * Trigger a student notification for application progress movement along the pipeline.
     */
    public static function notifyApplicationProgress(Application $application, ApplicationStatus|string $status): void
    {
        $application->loadMissing(['studentProfile.user', 'sponsorshipProgram']);
        $studentUser = $application->studentProfile?->user;

        if ($studentUser !== null) {
            $studentUser->notify(new ApplicationStatusUpdated($application, $status));
        }
    }

    /**
     * Instance wrapper for notifyApplicationRejected.
     */
    public function notifyRejection(Application $application, ?string $reason = null): void
    {
        self::notifyApplicationRejected($application, $reason);
    }

    /**
     * Instance wrapper for notifyApplicationProgress.
     */
    public function notifyProgress(Application $application, ApplicationStatus|string $status): void
    {
        self::notifyApplicationProgress($application, $status);
    }
}
