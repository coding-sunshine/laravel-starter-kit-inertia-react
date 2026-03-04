<?php

declare(strict_types=1);

namespace App\Filament\Resources\Featured\Pages;

use App\Filament\Resources\Featured\FeaturedResource;
use Filament\Resources\Pages\ListRecords;

final class ListFeatured extends ListRecords
{
    protected static string $resource = FeaturedResource::class;

    public function getTitle(): string
    {
        return 'Featured Properties';
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
