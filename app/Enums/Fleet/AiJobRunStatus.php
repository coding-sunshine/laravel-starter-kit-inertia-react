<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum AiJobRunStatus: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
