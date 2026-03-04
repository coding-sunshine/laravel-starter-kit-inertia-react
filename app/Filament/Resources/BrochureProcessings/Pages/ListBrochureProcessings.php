<?php

namespace App\Filament\Resources\BrochureProcessings\Pages;

use App\Filament\Resources\BrochureProcessings\BrochureProcessingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBrochureProcessings extends ListRecords
{
    protected static string $resource = BrochureProcessingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
