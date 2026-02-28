<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum EmissionsType: string
{
    case FuelCombustion = 'fuel_combustion';
    case Electricity = 'electricity';
    case WellToTank = 'well_to_tank';
    case Total = 'total';
}
