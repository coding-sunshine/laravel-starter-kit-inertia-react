<?php

declare(strict_types=1);

namespace App\Filament\Resources\CommodityUtilisationThresholds\Pages;

use App\Filament\Resources\CommodityUtilisationThresholds\CommodityUtilisationThresholdResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCommodityUtilisationThreshold extends CreateRecord
{
    protected static string $resource = CommodityUtilisationThresholdResource::class;
}
