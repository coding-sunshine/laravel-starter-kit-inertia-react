<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum GeofenceAlertOn: string
{
    case Entry = 'entry';
    case Exit = 'exit';
    case Speeding = 'speeding';
}
