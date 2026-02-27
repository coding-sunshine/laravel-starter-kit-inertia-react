<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum GarageType: string
{
    case Internal = 'internal';
    case External = 'external';
    case Mobile = 'mobile';
}
