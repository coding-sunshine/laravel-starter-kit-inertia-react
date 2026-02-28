<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum WorkOrderUrgency: string
{
    case Routine = 'routine';
    case Important = 'important';
    case Critical = 'critical';
    case Emergency = 'emergency';
}
