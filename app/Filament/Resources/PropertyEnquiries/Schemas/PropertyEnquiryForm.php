<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyEnquiries\Schemas;

use App\Models\Contact;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class PropertyEnquiryForm
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
                Toggle::make('preapproval'),
                Toggle::make('cash_purchase'),
                DateTimePicker::make('inspection_date'),
                TextInput::make('inspection_time'),
            ]);
    }
}
