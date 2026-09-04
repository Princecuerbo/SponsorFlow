<?php

namespace App\Enums;

enum ProgramStatus: string
{
    case Open = 'Open';
    case Closed = 'Closed';
    case Expired = 'Expired';
}
