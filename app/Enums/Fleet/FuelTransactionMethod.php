<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum FuelTransactionMethod: string
{
    case ChipPin = 'chip_pin';
    case Contactless = 'contactless';
    case Mobile = 'mobile';
    case Manual = 'manual';
}
