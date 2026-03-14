<?php

declare(strict_types=1);

namespace App\Filament\Resources\Lots\Pages;

use App\Filament\Resources\Lots\LotResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateLot extends CreateRecord
{
    protected static string $resource = LotResource::class;
}
