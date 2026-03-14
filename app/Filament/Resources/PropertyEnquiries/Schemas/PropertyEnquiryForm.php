<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyEnquiries\Schemas;

use Filament\Schemas\Schema;

final class PropertyEnquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}
