<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum VehicleStatus: string
{
    case Active = 'active';
    case Maintenance = 'maintenance';
    case Vor = 'vor';
    case Disposed = 'disposed';
}
