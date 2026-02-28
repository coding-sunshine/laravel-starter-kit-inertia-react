<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum ToolboxTalkStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
