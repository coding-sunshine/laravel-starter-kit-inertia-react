<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum InsurancePolicyType: string
{
    case Comprehensive = 'comprehensive';
    case ThirdParty = 'third_party';
    case Fleet = 'fleet';
    case GoodsInTransit = 'goods_in_transit';
}
