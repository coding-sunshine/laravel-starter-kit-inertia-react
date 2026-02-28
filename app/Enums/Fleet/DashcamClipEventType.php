<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum DashcamClipEventType: string
{
    case HarshBraking = 'harsh_braking';
    case Collision = 'collision';
    case Speeding = 'speeding';
    case Distraction = 'distraction';
    case LaneDeparture = 'lane_departure';
    case Other = 'other';
}
