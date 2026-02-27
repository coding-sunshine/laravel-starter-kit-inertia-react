<?php

declare(strict_types=1);

namespace App\Enums;

enum TripStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
