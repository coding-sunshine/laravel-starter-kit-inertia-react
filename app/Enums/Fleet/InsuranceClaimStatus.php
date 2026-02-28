<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum InsuranceClaimStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Acknowledged = 'acknowledged';
    case Investigating = 'investigating';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Settled = 'settled';
    case Closed = 'closed';
}
