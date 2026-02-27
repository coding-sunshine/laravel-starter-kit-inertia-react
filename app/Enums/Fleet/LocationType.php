<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum LocationType: string
{
    case Depot = 'depot';
    case Customer = 'customer';
    case ServiceStation = 'service_station';
    case Parking = 'parking';
    case Warehouse = 'warehouse';
    case Other = 'other';
}
