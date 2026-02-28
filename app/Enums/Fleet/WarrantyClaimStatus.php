<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum WarrantyClaimStatus: string
{
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Settled = 'settled';
}
