<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum MileageClaimStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Paid = 'paid';
}
