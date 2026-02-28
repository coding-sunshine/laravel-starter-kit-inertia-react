<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum SafetyObservationStatus: string
{
    case Open = 'open';
    case InReview = 'in_review';
    case Closed = 'closed';
}
