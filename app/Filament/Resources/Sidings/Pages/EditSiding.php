<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sidings\Pages;

use App\Filament\Resources\Sidings\SidingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

final class EditSiding extends EditRecord
{
    protected static string $resource = SidingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
