<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum WorkshopBayStatus: string
{
    case Available = 'available';
    case Occupied = 'occupied';
    case Maintenance = 'maintenance';
    case Reserved = 'reserved';
}
