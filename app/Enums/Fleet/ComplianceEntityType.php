<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum ComplianceEntityType: string
{
    case Vehicle = 'vehicle';
    case Driver = 'driver';
    case Organization = 'organization';
    case Trailer = 'trailer';
}
