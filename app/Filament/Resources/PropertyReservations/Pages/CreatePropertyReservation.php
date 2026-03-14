<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyReservations\Pages;

use App\Filament\Resources\PropertyReservations\PropertyReservationResource;
use Filament\Resources\Pages\CreateRecord;

final class CreatePropertyReservation extends CreateRecord
{
    protected static string $resource = PropertyReservationResource::class;
}
