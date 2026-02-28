<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum ReportTriggeredBy: string
{
    case Manual = 'manual';
    case Scheduled = 'scheduled';
    case Api = 'api';
}
