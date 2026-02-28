<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum ContractorComplianceStatus: string
{
    case Valid = 'valid';
    case ExpiringSoon = 'expiring_soon';
    case Expired = 'expired';
    case Missing = 'missing';
}
