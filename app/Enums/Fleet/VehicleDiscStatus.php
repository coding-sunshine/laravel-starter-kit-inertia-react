<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum VehicleDiscStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Replaced = 'replaced';
}
