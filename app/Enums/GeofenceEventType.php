<?php

declare(strict_types=1);

namespace App\Enums;

enum GeofenceEventType: string
{
    case Enter = 'enter';
    case Exit = 'exit';
}
