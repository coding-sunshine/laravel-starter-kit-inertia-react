<?php

declare(strict_types=1);

namespace App\Enums\Billing;

enum RefundStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Processed = 'processed';
}
