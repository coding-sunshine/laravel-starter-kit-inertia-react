<?php

declare(strict_types=1);

namespace App\Filament\Resources\CommodityUtilisationThresholds\Pages;

use App\Filament\Resources\CommodityUtilisationThresholds\CommodityUtilisationThresholdResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListCommodityUtilisationThresholds extends ListRecords
{
    protected static string $resource = CommodityUtilisationThresholdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
