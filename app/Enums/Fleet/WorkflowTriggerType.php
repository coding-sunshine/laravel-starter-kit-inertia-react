<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum WorkflowTriggerType: string
{
    case Schedule = 'schedule';
    case Event = 'event';
    case Manual = 'manual';
    case Webhook = 'webhook';
}
