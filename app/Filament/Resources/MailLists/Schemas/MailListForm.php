<?php

declare(strict_types=1);

namespace App\Filament\Resources\MailLists\Schemas;

use App\Models\Contact;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class MailListForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('owner_contact_id')
                    ->label('Owner contact')
                    ->relationship('ownerContact', 'first_name', fn ($q) => $q->orderBy('first_name')->orderBy('last_name'))
                    ->getOptionLabelFromRecordUsing(fn (Contact $c) => $c->full_name)
                    ->searchable(['first_name', 'last_name']),
            ]);
    }
}
