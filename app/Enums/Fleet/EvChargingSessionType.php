<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum EvChargingSessionType: string
{
    case AcSlow = 'ac_slow';
    case AcFast = 'ac_fast';
    case DcRapid = 'dc_rapid';
    case DcUltraRapid = 'dc_ultra_rapid';
}
