<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum OperatorLicenceStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Revoked = 'revoked';
    case Surrendered = 'surrendered';
    case Applied = 'applied';
    case PendingReview = 'pending_review';
}
