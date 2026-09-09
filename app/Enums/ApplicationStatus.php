<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Pending = 'Pending';
    case Verified = 'Verified';
    case Approved = 'Approved';
    case Rejected = 'Rejected';
    case Ongoing = 'Ongoing';
    case Expired = 'Expired';
    case ResubmissionRequested = 'Resubmission Requested';

    public function isActiveSponsorship(): bool
    {
        return $this === self::Approved || $this === self::Ongoing;
    }
}
