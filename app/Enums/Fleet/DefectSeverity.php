<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum DefectSeverity: string
{
    case Minor = 'minor';
    case Major = 'major';
    case Dangerous = 'dangerous';
}
