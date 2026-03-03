<?php

declare(strict_types=1);

namespace App\Filament\Resources\MailLists\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

final class MailListInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')->label('ID'),
                TextEntry::make('name'),
                TextEntry::make('ownerContact.full_name')->label('Owner')->placeholder('-'),
                TextEntry::make('created_at')->dateTime(),
                TextEntry::make('updated_at')->dateTime(),
            ]);
    }
}
