<?php

declare(strict_types=1);

namespace App\Filament\Resources\CreditPacks\Schemas;

use Filament\Schemas\Schema;

final class CreditPackForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}
