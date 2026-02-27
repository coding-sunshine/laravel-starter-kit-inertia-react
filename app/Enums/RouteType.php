<?php

declare(strict_types=1);

namespace App\Enums;

enum RouteType: string
{
    case Planned = 'planned';
    case AdHoc = 'ad_hoc';
}
