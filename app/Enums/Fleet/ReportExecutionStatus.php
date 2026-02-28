<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum ReportExecutionStatus: string
{
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
