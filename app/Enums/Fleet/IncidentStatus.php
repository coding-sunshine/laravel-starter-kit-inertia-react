<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum IncidentStatus: string
{
    case Reported = 'reported';
    case Investigating = 'investigating';
    case RepairApproved = 'repair_approved';
    case Repairing = 'repairing';
    case Settled = 'settled';
    case Closed = 'closed';
}
