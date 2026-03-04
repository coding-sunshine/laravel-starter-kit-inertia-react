<?php

declare(strict_types=1);

namespace App\Filament\Resources\Favourites\Pages;

use App\Filament\Resources\Favourites\FavouritesResource;
use Filament\Resources\Pages\ListRecords;

final class ListFavourites extends ListRecords
{
    protected static string $resource = FavouritesResource::class;

    public function getTitle(): string
    {
        return 'Favourite Properties';
    }

    protected function getHeaderActions(): array
    {
        return [
            // No create action since this is read-only
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Add any widgets here if needed
        ];
    }
}
