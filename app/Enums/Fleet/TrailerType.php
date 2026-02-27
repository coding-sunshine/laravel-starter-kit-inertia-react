<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum TrailerType: string
{
    case Flatbed = 'flatbed';
    case Box = 'box';
    case Tank = 'tank';
    case Refrigerated = 'refrigerated';
    case Lowloader = 'lowloader';
    case Other = 'other';
}
