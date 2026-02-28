<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum VehicleCheckItemResultType: string
{
    case PassFail = 'pass_fail';
    case Value = 'value';
    case Photo = 'photo';
}
