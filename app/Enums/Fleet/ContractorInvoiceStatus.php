<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum ContractorInvoiceStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Paid = 'paid';
    case Disputed = 'disputed';
    case Cancelled = 'cancelled';
}
