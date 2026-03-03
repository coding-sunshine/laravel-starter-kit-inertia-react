<?php

declare(strict_types=1);

namespace App\Filament\Resources\Relationships\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

final class RelationshipInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')->label('ID'),
                TextEntry::make('accountContact.full_name')->label('Account contact'),
                TextEntry::make('relationContact.full_name')->label('Related contact'),
                TextEntry::make('type')->placeholder('-'),
                TextEntry::make('created_at')->dateTime(),
                TextEntry::make('updated_at')->dateTime(),
            ]);
    }
}
