<?php

declare(strict_types=1);

namespace App\Enums\Fleet;

enum CostAllocationSourceType: string
{
    case FuelTransaction = 'fuel_transaction';
    case WorkOrder = 'work_order';
    case InsurancePremium = 'insurance_premium';
    case ManualEntry = 'manual_entry';
}
