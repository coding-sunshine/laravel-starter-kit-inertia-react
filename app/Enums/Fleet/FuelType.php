<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum FuelType: string
{
    case Petrol = 'petrol';
    case Diesel = 'diesel';
    case Electric = 'electric';
    case Adblue = 'adblue';
    case Lpg = 'lpg';
}
