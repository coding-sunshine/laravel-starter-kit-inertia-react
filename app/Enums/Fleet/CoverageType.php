<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum CoverageType: string
{
    case Individual = 'individual';
    case Fleet = 'fleet';
    case AnyDriver = 'any_driver';
}
