<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyReservations\Pages;

use App\Filament\Resources\PropertyReservations\PropertyReservationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListPropertyReservations extends ListRecords
{
    protected static string $resource = PropertyReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
