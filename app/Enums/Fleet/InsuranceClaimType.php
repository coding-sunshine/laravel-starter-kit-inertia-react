<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum InsuranceClaimType: string
{
    case Motor = 'motor';
    case Liability = 'liability';
    case GoodsInTransit = 'goods_in_transit';
    case EmployersLiability = 'employers_liability';
}
