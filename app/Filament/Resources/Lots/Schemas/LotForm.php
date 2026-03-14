<?php

declare(strict_types=1);

namespace App\Filament\Resources\Lots\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class LotForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('project_id')
                    ->relationship('project', 'title')
                    ->required(),
                TextInput::make('legacy_id')
                    ->numeric(),
                TextInput::make('slug'),
                TextInput::make('title'),
                TextInput::make('land_price')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('build_price')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('stage'),
                TextInput::make('level'),
                TextInput::make('building'),
                TextInput::make('floorplan'),
                TextInput::make('car')
                    ->numeric(),
                TextInput::make('storage'),
                TextInput::make('view'),
                TextInput::make('garage')
                    ->numeric(),
                TextInput::make('aspect'),
                TextInput::make('internal')
                    ->numeric(),
                TextInput::make('external')
                    ->numeric(),
                TextInput::make('total')
                    ->numeric(),
                TextInput::make('storeys')
                    ->numeric(),
                TextInput::make('land_size')
                    ->numeric(),
                Select::make('title_status')
                    ->required()
                    ->options([
                        'available' => 'Available',
                        'reserved' => 'Reserved',
                        'sold' => 'Sold',
                    ])
                    ->default('available'),
                TextInput::make('living_area')
                    ->numeric(),
                TextInput::make('price')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('bedrooms')
                    ->numeric(),
                TextInput::make('bathrooms')
                    ->numeric(),
                TextInput::make('study')
                    ->numeric(),
                Toggle::make('mpr')
                    ->required(),
                Toggle::make('powder_room')
                    ->required(),
                TextInput::make('balcony')
                    ->numeric(),
                TextInput::make('rent_yield')
                    ->numeric(),
                TextInput::make('weekly_rent')
                    ->numeric(),
                TextInput::make('rates')
                    ->numeric(),
                TextInput::make('body_corporation')
                    ->numeric(),
                Toggle::make('is_archived')
                    ->required(),
                Toggle::make('is_nras')
                    ->required(),
                Toggle::make('is_smsf')
                    ->required(),
                Toggle::make('is_cashflow_positive')
                    ->required(),
                DatePicker::make('completion'),
                TextInput::make('created_by')
                    ->numeric(),
                TextInput::make('updated_by')
                    ->numeric(),
            ]);
    }
}
