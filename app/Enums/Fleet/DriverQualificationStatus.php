<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum DriverQualificationStatus: string
{
    case Valid = 'valid';
    case Expired = 'expired';
    case Suspended = 'suspended';
    case Revoked = 'revoked';
    case Pending = 'pending';
}
