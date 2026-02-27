<?php

declare(strict_types=1);

namespace App\Enums;

enum TelematicsDeviceStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
    case Decommissioned = 'decommissioned';
}
