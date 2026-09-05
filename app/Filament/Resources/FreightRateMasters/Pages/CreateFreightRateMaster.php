<?php

declare(strict_types=1);

namespace App\Filament\Resources\FreightRateMasters\Pages;

use App\Filament\Resources\FreightRateMasters\FreightRateMasterResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateFreightRateMaster extends CreateRecord
{
    protected static string $resource = FreightRateMasterResource::class;
}
