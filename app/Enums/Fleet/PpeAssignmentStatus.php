<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum PpeAssignmentStatus: string
{
    case Active = 'active';
    case Returned = 'returned';
    case Expired = 'expired';
}
