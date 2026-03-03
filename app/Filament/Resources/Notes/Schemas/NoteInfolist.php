<?php

declare(strict_types=1);

namespace App\Filament\Resources\Notes\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

final class NoteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')->label('ID'),
                TextEntry::make('noteable_type')->label('Related type'),
                TextEntry::make('noteable_id')->label('Related ID'),
                TextEntry::make('author.name')->label('Author')->placeholder('-'),
                TextEntry::make('type')->placeholder('-'),
                TextEntry::make('content')->columnSpanFull(),
                TextEntry::make('created_at')->dateTime(),
                TextEntry::make('updated_at')->dateTime(),
            ]);
    }
}
