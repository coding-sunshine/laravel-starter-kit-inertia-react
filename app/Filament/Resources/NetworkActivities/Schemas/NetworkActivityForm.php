<?php

declare(strict_types=1);

namespace App\Filament\Resources\NetworkActivities\Schemas;

use Filament\Schemas\Schema;

final class NetworkActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}
