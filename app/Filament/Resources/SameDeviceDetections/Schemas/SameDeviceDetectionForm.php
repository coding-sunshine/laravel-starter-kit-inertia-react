<?php

declare(strict_types=1);

namespace App\Filament\Resources\SameDeviceDetections\Schemas;

use Filament\Schemas\Schema;

final class SameDeviceDetectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}
