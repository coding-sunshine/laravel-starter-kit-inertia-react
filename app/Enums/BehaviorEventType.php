<?php

declare(strict_types=1);

namespace App\Enums;

enum BehaviorEventType: string
{
    case HarshBraking = 'harsh_braking';
    case HarshAcceleration = 'harsh_acceleration';
    case HarshCornering = 'harsh_cornering';
    case Speeding = 'speeding';
    case Idling = 'idling';
    case SeatbeltOff = 'seatbelt_off';
    case Collision = 'collision';
}
