<?php

namespace App\Filament\Resources\BrochureProcessings\Pages;

use App\Filament\Resources\BrochureProcessings\BrochureProcessingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBrochureProcessing extends EditRecord
{
    protected static string $resource = BrochureProcessingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
