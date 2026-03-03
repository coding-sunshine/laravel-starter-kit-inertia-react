<?php

declare(strict_types=1);

namespace App\Filament\Resources\Relationships\Schemas;

use App\Models\Contact;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class RelationshipForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('account_contact_id')
                    ->label('Account contact')
                    ->relationship('accountContact', 'first_name', fn ($q) => $q->orderBy('first_name')->orderBy('last_name'))
                    ->getOptionLabelFromRecordUsing(fn (Contact $c) => $c->full_name)
                    ->searchable(['first_name', 'last_name'])
                    ->required(),
                Select::make('relation_contact_id')
                    ->label('Related contact')
                    ->relationship('relationContact', 'first_name', fn ($q) => $q->orderBy('first_name')->orderBy('last_name'))
                    ->getOptionLabelFromRecordUsing(fn (Contact $c) => $c->full_name)
                    ->searchable(['first_name', 'last_name'])
                    ->required(),
                TextInput::make('type')
                    ->maxLength(255),
            ]);
    }
}
