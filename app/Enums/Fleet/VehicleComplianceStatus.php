<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum VehicleComplianceStatus: string
{
    case Compliant = 'compliant';
    case ExpiringSoon = 'expiring_soon';
    case Expired = 'expired';
}
