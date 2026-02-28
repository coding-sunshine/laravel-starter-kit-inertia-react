<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum ServiceScheduleIntervalType: string
{
    case Mileage = 'mileage';
    case Time = 'time';
    case Both = 'both';
}
