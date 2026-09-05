<?php

declare(strict_types=1);

namespace App\Filament\Resources\PowerPlants\Pages;

use App\Filament\Resources\PowerPlants\PowerPlantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListPowerPlants extends ListRecords
{
    protected static string $resource = PowerPlantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
