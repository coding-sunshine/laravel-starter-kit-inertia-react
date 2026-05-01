<?php

declare(strict_types=1);

namespace App\Filament\Resources\CommodityUtilisationThresholds\Pages;

use App\Filament\Resources\CommodityUtilisationThresholds\CommodityUtilisationThresholdResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditCommodityUtilisationThreshold extends EditRecord
{
    protected static string $resource = CommodityUtilisationThresholdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
