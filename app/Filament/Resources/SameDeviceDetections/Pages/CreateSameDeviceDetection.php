<?php

declare(strict_types=1);

namespace App\Filament\Resources\SameDeviceDetections\Pages;

use App\Filament\Resources\SameDeviceDetections\SameDeviceDetectionResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSameDeviceDetection extends CreateRecord
{
    protected static string $resource = SameDeviceDetectionResource::class;
}
