<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum DriverQualificationType: string
{
    case License = 'license';
    case Cpc = 'cpc';
    case Training = 'training';
    case Certification = 'certification';
    case Medical = 'medical';
}
