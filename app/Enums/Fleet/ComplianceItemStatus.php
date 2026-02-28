<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum ComplianceItemStatus: string
{
    case Valid = 'valid';
    case ExpiringSoon = 'expiring_soon';
    case Expired = 'expired';
    case Renewed = 'renewed';
    case Revoked = 'revoked';
}
