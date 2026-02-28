<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum SafetyObservationCategory: string
{
    case NearMiss = 'near_miss';
    case Hazard = 'hazard';
    case UnsafeAct = 'unsafe_act';
    case Positive = 'positive';
}
