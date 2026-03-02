<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertySearches\Pages;

use App\Filament\Resources\PropertySearches\PropertySearchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListPropertySearches extends ListRecords
{
    protected static string $resource = PropertySearchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
