<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum DefectStatus: string
{
    case Reported = 'reported';
    case Acknowledged = 'acknowledged';
    case Assigned = 'assigned';
    case WorkOrdered = 'work_ordered';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case CannotReproduce = 'cannot_reproduce';
}
