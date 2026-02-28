<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum FineType: string
{
    case Speeding = 'speeding';
    case Parking = 'parking';
    case Other = 'other';
}
