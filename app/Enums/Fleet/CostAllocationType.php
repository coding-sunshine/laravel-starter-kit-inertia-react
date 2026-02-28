<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum CostAllocationType: string
{
    case Fuel = 'fuel';
    case Maintenance = 'maintenance';
    case Insurance = 'insurance';
    case Depreciation = 'depreciation';
    case Registration = 'registration';
    case Other = 'other';
}
