<?php

declare(strict_types=1);

namespace App\Filament\Resources\PowerPlants\Pages;

use App\Filament\Resources\PowerPlants\PowerPlantResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditPowerPlant extends EditRecord
{
    protected static string $resource = PowerPlantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
