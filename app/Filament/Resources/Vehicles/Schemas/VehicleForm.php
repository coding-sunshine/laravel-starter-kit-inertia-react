<?php

declare(strict_types=1);

namespace App\Filament\Resources\Vehicles\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class VehicleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('vehicle_number')
                    ->required(),
                TextInput::make('rfid_tag'),
                TextInput::make('permitted_capacity_mt')
                    ->required()
                    ->numeric(),
                TextInput::make('tare_weight_mt')
                    ->required()
                    ->numeric(),
                TextInput::make('owner_name')
                    ->required(),
                TextInput::make('vehicle_type')
                    ->required(),
                TextInput::make('gps_device_id'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
