<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum PermitToWorkStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
