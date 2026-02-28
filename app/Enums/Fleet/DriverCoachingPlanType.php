<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum DriverCoachingPlanType: string
{
    case Safety = 'safety';
    case Performance = 'performance';
    case Induction = 'induction';
    case Refresher = 'refresher';
}
