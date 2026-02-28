<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum VehicleLeaseStatus: string
{
    case Active = 'active';
    case Ended = 'ended';
    case Terminated = 'terminated';
}
