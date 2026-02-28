<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum DataMigrationRunStatus: string
{
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case RolledBack = 'rolled_back';
}
