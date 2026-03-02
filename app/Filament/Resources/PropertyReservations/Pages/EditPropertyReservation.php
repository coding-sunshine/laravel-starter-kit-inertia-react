<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyReservations\Pages;

use App\Filament\Resources\PropertyReservations\PropertyReservationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditPropertyReservation extends EditRecord
{
    protected static string $resource = PropertyReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
