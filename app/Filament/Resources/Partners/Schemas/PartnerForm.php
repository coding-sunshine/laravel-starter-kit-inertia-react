<?php

declare(strict_types=1);

namespace App\Filament\Resources\Partners\Schemas;

use App\Models\Contact;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class PartnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('contact_id')
                    ->label('Contact')
                    ->relationship('contact', 'first_name', fn ($q) => $q->orderBy('first_name')->orderBy('last_name'))
                    ->getOptionLabelFromRecordUsing(fn (Contact $c) => $c->full_name)
                    ->searchable(['first_name', 'last_name'])
                    ->required(),
                TextInput::make('role')
                    ->maxLength(255),
                TextInput::make('status')
                    ->maxLength(255),
            ]);
    }
}
