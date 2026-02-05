<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

final class RoleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('guard_name')->badge(),
                TextEntry::make('permissions.name')
                    ->label('Permissions')
                    ->listWithLineBreaks()
                    ->bulleted(),
            ]);
    }
}
