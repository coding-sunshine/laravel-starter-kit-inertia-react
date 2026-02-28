<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum VehicleCheckTemplateCheckType: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case PreTrip = 'pre_trip';
    case PostTrip = 'post_trip';
    case Inspection = 'inspection';
}
