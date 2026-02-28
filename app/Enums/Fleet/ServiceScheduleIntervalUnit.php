<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum ServiceScheduleIntervalUnit: string
{
    case Days = 'days';
    case Weeks = 'weeks';
    case Months = 'months';
    case Km = 'km';
    case Miles = 'miles';
}
