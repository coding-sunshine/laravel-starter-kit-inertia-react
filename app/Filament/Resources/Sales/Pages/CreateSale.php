<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Resources\Sales\SaleResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;
}
