<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum WorkOrderType: string
{
    case Service = 'service';
    case Repair = 'repair';
    case Inspection = 'inspection';
    case Modification = 'modification';
    case Recall = 'recall';
}
