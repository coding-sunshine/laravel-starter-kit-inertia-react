<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum DriverStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Terminated = 'terminated';
    case OnLeave = 'on_leave';
}
