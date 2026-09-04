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

    public function isActiveSponsorship(): bool
    {
        return $this === self::Approved || $this === self::Ongoing;
    }
}
