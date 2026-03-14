<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sales\Schemas;

use Filament\Schemas\Schema;

final class SaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}
