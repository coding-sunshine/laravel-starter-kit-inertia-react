<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum AiAnalysisStatus: string
{
    case Pending = 'pending';
    case Reviewed = 'reviewed';
    case Actioned = 'actioned';
    case Dismissed = 'dismissed';
    case Escalated = 'escalated';
}
