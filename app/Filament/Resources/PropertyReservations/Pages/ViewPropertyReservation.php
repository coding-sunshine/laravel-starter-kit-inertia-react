<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyReservations\Pages;

use App\Filament\Resources\PropertyReservations\PropertyReservationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewPropertyReservation extends ViewRecord
{
    protected static string $resource = PropertyReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
