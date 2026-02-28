<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum WorkOrderLineType: string
{
    case Labour = 'labour';
    case Part = 'part';
    case Other = 'other';
}
