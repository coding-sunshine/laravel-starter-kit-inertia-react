<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum RiskAssessmentType: string
{
    case Generic = 'generic';
    case Driving = 'driving';
    case Maintenance = 'maintenance';
    case Site = 'site';
}
