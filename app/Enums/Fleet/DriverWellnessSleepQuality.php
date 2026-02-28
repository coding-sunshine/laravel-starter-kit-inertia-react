<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum DriverWellnessSleepQuality: string
{
    case Poor = 'poor';
    case Fair = 'fair';
    case Good = 'good';
    case Excellent = 'excellent';
}
