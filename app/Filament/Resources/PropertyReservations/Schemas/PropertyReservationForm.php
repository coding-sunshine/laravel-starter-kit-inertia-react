<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyReservations\Schemas;

use Filament\Schemas\Schema;

final class PropertyReservationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}
