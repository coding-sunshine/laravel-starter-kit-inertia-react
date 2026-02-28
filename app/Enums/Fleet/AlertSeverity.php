<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum AlertSeverity: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Critical = 'critical';
    case Emergency = 'emergency';
}
