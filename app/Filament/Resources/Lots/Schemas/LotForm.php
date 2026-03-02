<?php

namespace App\Filament\Resources\Lots\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LotForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('legacy_lot_id')
                    ->numeric(),
                Select::make('project_id')
                    ->relationship('project', 'title')
                    ->required(),
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
                TextInput::make('storage')
                    ->numeric(),
                TextInput::make('view'),
                TextInput::make('garage'),
                TextInput::make('aspect'),
                TextInput::make('internal')
                    ->numeric(),
                TextInput::make('external')
                    ->numeric(),
                TextInput::make('total')
                    ->numeric(),
                TextInput::make('storyes')
                    ->numeric(),
                TextInput::make('land_size'),
                TextInput::make('title_status'),
                TextInput::make('living_area')
                    ->numeric(),
                TextInput::make('price')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('bedrooms'),
                TextInput::make('bathrooms'),
                TextInput::make('study')
                    ->numeric(),
                TextInput::make('mpr')
                    ->numeric(),
                TextInput::make('powder_room')
                    ->numeric(),
                TextInput::make('balcony')
                    ->numeric(),
                TextInput::make('rent_yield')
                    ->numeric(),
                TextInput::make('weekly_rent')
                    ->numeric(),
                TextInput::make('rent_to_sell_yield')
                    ->numeric(),
                TextInput::make('rates')
                    ->numeric(),
                TextInput::make('five_percent_share_price')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('sub_agent_comms')
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
                TextInput::make('completion'),
                TextInput::make('uuid')
                    ->label('UUID'),
                TextInput::make('created_by')
                    ->numeric(),
                TextInput::make('updated_by')
                    ->numeric(),
            ]);
    }
}
