<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertySearches\Pages;

use App\Filament\Resources\PropertySearches\PropertySearchResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditPropertySearch extends EditRecord
{
    protected static string $resource = PropertySearchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
