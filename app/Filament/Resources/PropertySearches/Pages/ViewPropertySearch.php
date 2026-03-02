<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertySearches\Pages;

use App\Filament\Resources\PropertySearches\PropertySearchResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewPropertySearch extends ViewRecord
{
    protected static string $resource = PropertySearchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
