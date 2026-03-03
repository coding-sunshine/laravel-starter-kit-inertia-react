<?php

declare(strict_types=1);

namespace App\Filament\Resources\Commissions\Pages;

use App\Filament\Resources\Commissions\CommissionResource;
use Filament\Resources\Pages\ListRecords;

final class ListCommissions extends ListRecords
{
    protected static string $resource = CommissionResource::class;
}
