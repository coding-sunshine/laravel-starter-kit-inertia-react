<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum EmissionsScope: string
{
    case Vehicle = 'vehicle';
    case Trip = 'trip';
    case Driver = 'driver';
    case Organization = 'organization';
}
