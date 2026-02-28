<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum FuelTransactionValidationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Flagged = 'flagged';
    case Rejected = 'rejected';
}
