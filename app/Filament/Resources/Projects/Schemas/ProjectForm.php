<?php

namespace App\Filament\Resources\Projects\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('legacy_project_id')
                    ->numeric(),
                Select::make('organization_id')
                    ->relationship('organization', 'name'),
                TextInput::make('title')
                    ->required(),
                TextInput::make('stage'),
                TextInput::make('estate')
                    ->required(),
                TextInput::make('total_lots')
                    ->numeric(),
                TextInput::make('storeys')
                    ->numeric(),
                TextInput::make('min_landsize')
                    ->numeric(),
                TextInput::make('max_landsize')
                    ->numeric(),
                TextInput::make('min_living_area')
                    ->numeric(),
                TextInput::make('max_living_area')
                    ->numeric(),
                TextInput::make('bedrooms'),
                TextInput::make('bathrooms'),
                TextInput::make('min_bedrooms'),
                TextInput::make('max_bedrooms'),
                TextInput::make('min_bathrooms'),
                TextInput::make('max_bathrooms'),
                TextInput::make('garage')
                    ->numeric(),
                TextInput::make('min_rent')
                    ->numeric(),
                TextInput::make('max_rent')
                    ->numeric(),
                TextInput::make('avg_rent')
                    ->numeric(),
                TextInput::make('min_rent_yield')
                    ->numeric(),
                TextInput::make('max_rent_yield')
                    ->numeric(),
                TextInput::make('avg_rent_yield')
                    ->numeric(),
                TextInput::make('rent_to_sell_yield')
                    ->numeric(),
                Toggle::make('is_hot_property')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('min_price')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('max_price')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('avg_price')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('body_corporate_fees')
                    ->numeric(),
                TextInput::make('min_body_corporate_fees')
                    ->numeric(),
                TextInput::make('max_body_corporate_fees')
                    ->numeric(),
                TextInput::make('rates_fees')
                    ->numeric(),
                TextInput::make('min_rates_fees')
                    ->numeric(),
                TextInput::make('max_rates_fees')
                    ->numeric(),
                TextInput::make('sub_agent_comms')
                    ->numeric(),
                Toggle::make('is_archived')
                    ->required(),
                Toggle::make('is_hidden')
                    ->required(),
                DatePicker::make('start_at'),
                DatePicker::make('end_at'),
                Toggle::make('is_smsf')
                    ->required(),
                Toggle::make('is_firb')
                    ->required(),
                Toggle::make('is_ndis')
                    ->required(),
                Toggle::make('is_cashflow_positive')
                    ->required(),
                TextInput::make('build_time'),
                TextInput::make('historical_growth')
                    ->numeric(),
                TextInput::make('land_info'),
                Select::make('developer_id')
                    ->relationship('developer', 'id'),
                Select::make('projecttype_id')
                    ->relationship('projecttype', 'title'),
                Toggle::make('is_featured')
                    ->required(),
                TextInput::make('trust_details'),
                Textarea::make('property_conditions')
                    ->columnSpanFull(),
                Toggle::make('is_co_living')
                    ->required(),
                Toggle::make('is_rooming')
                    ->required(),
                Toggle::make('is_rent_to_sell')
                    ->required(),
                Toggle::make('is_flexi')
                    ->required(),
                Toggle::make('is_exclusive')
                    ->required(),
                TextInput::make('created_by')
                    ->numeric(),
                TextInput::make('updated_by')
                    ->numeric(),
            ]);
    }
}
