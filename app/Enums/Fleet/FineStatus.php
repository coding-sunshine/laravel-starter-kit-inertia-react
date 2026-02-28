<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum FineStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Appealed = 'appealed';
    case Waived = 'waived';
}
