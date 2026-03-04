<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApprovedApiKeys\Schemas;

use Filament\Schemas\Schema;

final class ApprovedApiKeysForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}
