<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum DefectCategory: string
{
    case Safety = 'safety';
    case Mechanical = 'mechanical';
    case Electrical = 'electrical';
    case Bodywork = 'bodywork';
    case Other = 'other';
}
