<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertySearches\Schemas;

use App\Models\Contact;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class PropertySearchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('client_contact_id')
                    ->label('Client contact')
                    ->relationship('clientContact', 'first_name', fn ($q) => $q->orderBy('first_name')->orderBy('last_name'))
                    ->getOptionLabelFromRecordUsing(fn (Contact $c) => $c->full_name)
                    ->searchable(['first_name', 'last_name']),
                Select::make('agent_contact_id')
                    ->label('Agent contact')
                    ->relationship('agentContact', 'first_name', fn ($q) => $q->orderBy('first_name')->orderBy('last_name'))
                    ->getOptionLabelFromRecordUsing(fn (Contact $c) => $c->full_name)
                    ->searchable(['first_name', 'last_name']),
                TextInput::make('preferred_location'),
                TextInput::make('max_capacity')->numeric(),
                TextInput::make('no_of_bedrooms')->numeric(),
                TextInput::make('no_of_bathrooms')->numeric(),
                TextInput::make('no_of_carspaces')->numeric(),
                Toggle::make('preapproval'),
            ]);
    }
}
