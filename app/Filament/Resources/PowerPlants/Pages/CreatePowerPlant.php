<?php

declare(strict_types=1);

namespace App\Filament\Resources\PowerPlants\Pages;

use App\Filament\Resources\PowerPlants\PowerPlantResource;
use Filament\Resources\Pages\CreateRecord;

final class CreatePowerPlant extends CreateRecord
{
    protected static string $resource = PowerPlantResource::class;
}
