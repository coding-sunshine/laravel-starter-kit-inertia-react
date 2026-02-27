<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum CostCenterType: string
{
    case Department = 'department';
    case Project = 'project';
    case Location = 'location';
    case VehicleType = 'vehicle_type';
}
