<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum FuelCardStatus: string
{
    case Active = 'active';
    case Blocked = 'blocked';
    case Expired = 'expired';
    case Lost = 'lost';
}
