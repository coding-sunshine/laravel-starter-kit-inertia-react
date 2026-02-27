<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum GeofenceType: string
{
    case Circle = 'circle';
    case Polygon = 'polygon';
    case AdministrativeBoundary = 'administrative_boundary';
}
