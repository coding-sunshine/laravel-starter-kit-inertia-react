<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum TachographCalibrationStatus: string
{
    case Valid = 'valid';
    case DueSoon = 'due_soon';
    case Expired = 'expired';
}
