<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyManagers\Pages;

use App\Filament\Resources\PropertyManagers\PropertyManagerResource;
use Filament\Resources\Pages\ListRecords;

final class ListPropertyManagers extends ListRecords
{
    protected static string $resource = PropertyManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
