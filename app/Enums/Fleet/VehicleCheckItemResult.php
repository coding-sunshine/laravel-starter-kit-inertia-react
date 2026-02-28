<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum VehicleCheckItemResult: string
{
    case Pass = 'pass';
    case Fail = 'fail';
    case Na = 'na';
}
