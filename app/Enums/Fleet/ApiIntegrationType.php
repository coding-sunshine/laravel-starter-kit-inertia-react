<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum ApiIntegrationType: string
{
    case Telematics = 'telematics';
    case FuelCard = 'fuel_card';
    case Maintenance = 'maintenance';
    case Insurance = 'insurance';
    case Compliance = 'compliance';
    case Mapping = 'mapping';
    case Weather = 'weather';
    case Traffic = 'traffic';
    case Other = 'other';
}
