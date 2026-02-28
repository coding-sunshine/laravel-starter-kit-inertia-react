<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum EvBatteryChargingStatus: string
{
    case NotCharging = 'not_charging';
    case Charging = 'charging';
    case FastCharging = 'fast_charging';
    case Complete = 'complete';
}
