<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum IncidentSeverity: string
{
    case Minor = 'minor';
    case Major = 'major';
    case TotalLoss = 'total_loss';
}
