<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum VehicleRecallStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case NotApplicable = 'not_applicable';
}
