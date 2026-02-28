<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum PoolVehicleBookingStatus: string
{
    case Booked = 'booked';
    case CheckedOut = 'checked_out';
    case Returned = 'returned';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';
}
