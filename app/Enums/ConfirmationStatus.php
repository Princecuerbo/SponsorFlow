<?php

namespace App\Enums;

enum ConfirmationStatus: string
{
    case Pending = 'Pending';
    case Confirmed = 'Confirmed';
    case Rejected = 'Rejected';
}
