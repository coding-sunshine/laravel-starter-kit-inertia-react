<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum FuelCardType: string
{
    case Fleet = 'fleet';
    case Individual = 'individual';
    case Emergency = 'emergency';
}
