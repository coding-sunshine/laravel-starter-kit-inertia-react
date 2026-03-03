<?php

declare(strict_types=1);

namespace App\Filament\Resources\Commissions\Pages;

use App\Filament\Resources\Commissions\CommissionResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewCommission extends ViewRecord
{
    protected static string $resource = CommissionResource::class;
}
