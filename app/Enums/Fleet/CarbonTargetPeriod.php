<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum CarbonTargetPeriod: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Annual = 'annual';
}
