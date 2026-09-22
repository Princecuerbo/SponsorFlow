<?php

namespace App\Enums;

enum SleFheStatus: string
{
    case Unverified = 'Unverified';
    case PendingReview = 'Pending Review';
    case Verified = 'Verified';
    case Rejected = 'Rejected';
}
