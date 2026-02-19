<?php

declare(strict_types=1);

namespace App\Filament\Resources\FreightRateMasters\Pages;

use App\Filament\Resources\FreightRateMasters\FreightRateMasterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListFreightRateMasters extends ListRecords
{
    protected static string $resource = FreightRateMasterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
