<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum VehicleCheckStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Failed = 'failed';
}
