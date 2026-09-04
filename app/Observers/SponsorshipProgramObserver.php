<?php

namespace App\Observers;

use App\Enums\ProgramStatus;
use App\Models\SponsorshipProgram;

class SponsorshipProgramObserver
{
    /**
     * Handle the SponsorshipProgram "updated" event.
     */
    public function updated(SponsorshipProgram $program): void
    {
        if ($program->isDirty('status') && $program->status === ProgramStatus::Expired) {
            $program->cascadeExpiredApplications();
        }
    }
}
