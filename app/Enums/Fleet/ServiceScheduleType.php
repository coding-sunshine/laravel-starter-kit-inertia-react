<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum ServiceScheduleType: string
{
    case Mot = 'mot';
    case Service = 'service';
    case Inspection = 'inspection';
    case Pmi = 'pmi';
}
