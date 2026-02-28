<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum ElockEventType: string
{
    case Lock = 'lock';
    case Unlock = 'unlock';
    case Tamper = 'tamper';
    case GeofenceUnlock = 'geofence_unlock';
}
