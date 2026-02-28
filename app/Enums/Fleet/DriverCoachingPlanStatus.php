<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum DriverCoachingPlanStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
