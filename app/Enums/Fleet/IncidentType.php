<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum IncidentType: string
{
    case Collision = 'collision';
    case Theft = 'theft';
    case Vandalism = 'vandalism';
    case Fire = 'fire';
    case Flood = 'flood';
    case Breakdown = 'breakdown';
    case Other = 'other';
}
