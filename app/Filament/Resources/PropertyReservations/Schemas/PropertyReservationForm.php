<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyReservations\Schemas;

use App\Models\Contact;
use App\Models\Lot;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class PropertyReservationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('agent_contact_id')
                    ->label('Agent contact')
                    ->relationship('agentContact', 'first_name', fn ($q) => $q->orderBy('first_name')->orderBy('last_name'))
                    ->getOptionLabelFromRecordUsing(fn (Contact $c) => $c->full_name)
                    ->searchable(['first_name', 'last_name']),
                Select::make('primary_contact_id')
                    ->label('Primary contact')
                    ->relationship('primaryContact', 'first_name', fn ($q) => $q->orderBy('first_name')->orderBy('last_name'))
                    ->getOptionLabelFromRecordUsing(fn (Contact $c) => $c->full_name)
                    ->searchable(['first_name', 'last_name']),
                Select::make('secondary_contact_id')
                    ->label('Secondary contact')
                    ->relationship('secondaryContact', 'first_name', fn ($q) => $q->orderBy('first_name')->orderBy('last_name'))
                    ->getOptionLabelFromRecordUsing(fn (Contact $c) => $c->full_name)
                    ->searchable(['first_name', 'last_name']),
                Select::make('project_id')
                    ->relationship('project', 'title')
                    ->searchable()
                    ->preload(),
                Select::make('lot_id')
                    ->relationship('lot', 'id')
                    ->getOptionLabelFromRecordUsing(fn (Lot $lot) => (string) ($lot->legacy_lot_id ?? $lot->id))
                    ->searchable(),
                TextInput::make('purchase_price')->numeric()->prefix('$'),
                DatePicker::make('agree_date'),
            ]);
    }
}
