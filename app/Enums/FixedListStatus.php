<?php

namespace App\Enums;

enum FixedListStatus: string
{
    case Draft = 'Draft';
    case Submitted = 'Submitted';
    case Approved = 'Approved';
    case Rejected = 'Rejected';
}
