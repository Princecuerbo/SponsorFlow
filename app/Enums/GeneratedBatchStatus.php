<?php

namespace App\Enums;

enum GeneratedBatchStatus: string
{
    case Saved = 'Saved';
    case Submitted = 'Submitted';
    case Approved = 'Approved';
    case Rejected = 'Rejected';
}
