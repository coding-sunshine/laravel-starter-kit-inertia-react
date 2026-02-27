<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum VehicleType: string
{
    case Car = 'car';
    case Van = 'van';
    case Truck = 'truck';
    case Bus = 'bus';
    case Motorcycle = 'motorcycle';
}
