<?php

declare(strict_types=1);

namespace App\Filament\Resources\FreightRateMasters\Pages;

use App\Filament\Resources\FreightRateMasters\FreightRateMasterResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

final class EditFreightRateMaster extends EditRecord
{
    protected static string $resource = FreightRateMasterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
