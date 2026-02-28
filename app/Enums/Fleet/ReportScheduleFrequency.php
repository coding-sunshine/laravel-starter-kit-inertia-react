<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum ReportScheduleFrequency: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
}
