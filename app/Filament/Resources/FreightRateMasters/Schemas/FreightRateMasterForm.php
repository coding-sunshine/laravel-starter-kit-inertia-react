<?php

declare(strict_types=1);

namespace App\Filament\Resources\FreightRateMasters\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class FreightRateMasterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('commodity_code')
                    ->required(),
                TextInput::make('commodity_name')
                    ->required(),
                TextInput::make('class_code')
                    ->required(),
                TextInput::make('risk_rate'),
                TextInput::make('distance_from_km')
                    ->required()
                    ->numeric(),
                TextInput::make('distance_to_km')
                    ->required()
                    ->numeric(),
                TextInput::make('rate_per_mt')
                    ->required()
                    ->numeric(),
                TextInput::make('gst_percent')
                    ->required()
                    ->numeric()
                    ->default(5),
                DatePicker::make('effective_from')
                    ->required(),
                DatePicker::make('effective_to'),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
