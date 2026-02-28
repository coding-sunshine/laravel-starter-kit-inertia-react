<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum TrainingSessionStatus: string
{
    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Postponed = 'postponed';
}
