<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum SustainabilityGoalStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Achieved = 'achieved';
    case Cancelled = 'cancelled';
}
