<?php

namespace App\Enums;

enum FixedListItemStatus: string
{
    case Pending = 'Pending';
    case Verified = 'Verified';
    case Eligible = 'Eligible';
    case Ineligible = 'Ineligible';
}
