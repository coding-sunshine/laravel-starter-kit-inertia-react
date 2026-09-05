<?php

declare(strict_types=1);

namespace App\Filament\Resources\Routes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class RouteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('siding_id')
                    ->relationship(
                        'siding',
                        'name',
                        fn ($query) => tenant_id() ? $query->where('organization_id', tenant_id()) : $query
                    )
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('route_name')
                    ->required(),
                TextInput::make('start_location')
                    ->required(),
                TextInput::make('end_location')
                    ->required(),
                TextInput::make('expected_distance_km')
                    ->required()
                    ->numeric(),
                Textarea::make('geo_json_path_data')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
