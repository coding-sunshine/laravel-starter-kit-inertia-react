<?php

declare(strict_types=1);

namespace App\Filament\Resources\SameDeviceDetections\Pages;

use App\Filament\Resources\SameDeviceDetections\SameDeviceDetectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditSameDeviceDetection extends EditRecord
{
    protected static string $resource = SameDeviceDetectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
