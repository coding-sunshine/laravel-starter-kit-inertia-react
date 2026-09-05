<?php

declare(strict_types=1);

namespace App\Filament\Resources\Loaders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class LoaderForm
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
                TextInput::make('loader_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->required()
                    ->maxLength(50),
                TextInput::make('loader_type')
                    ->maxLength(50),
                TextInput::make('make_model')
                    ->maxLength(255),
                DatePicker::make('last_calibration_date'),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
