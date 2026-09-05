<?php

declare(strict_types=1);

namespace App\Filament\Resources\Loaders\Pages;

use App\Filament\Resources\Loaders\LoaderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListLoaders extends ListRecords
{
    protected static string $resource = LoaderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
