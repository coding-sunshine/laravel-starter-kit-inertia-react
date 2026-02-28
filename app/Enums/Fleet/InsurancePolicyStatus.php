<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum InsurancePolicyStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Pending = 'pending';
}
