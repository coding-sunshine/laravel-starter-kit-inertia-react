<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum ContractorComplianceType: string
{
    case Insurance = 'insurance';
    case Certification = 'certification';
    case License = 'license';
    case Safety = 'safety';
}
