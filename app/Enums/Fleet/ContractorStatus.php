<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum ContractorStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Inactive = 'inactive';
}
