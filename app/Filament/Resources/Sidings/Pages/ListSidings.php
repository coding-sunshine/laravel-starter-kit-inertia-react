<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sidings\Pages;

use App\Filament\Resources\Sidings\SidingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListSidings extends ListRecords
{
    protected static string $resource = SidingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
