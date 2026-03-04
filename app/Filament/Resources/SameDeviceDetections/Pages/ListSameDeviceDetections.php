<?php

declare(strict_types=1);

namespace App\Filament\Resources\SameDeviceDetections\Pages;

use App\Filament\Resources\SameDeviceDetections\SameDeviceDetectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListSameDeviceDetections extends ListRecords
{
    protected static string $resource = SameDeviceDetectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
