<?php

declare(strict_types=1);

namespace App\Filament\Resources\PowerPlants;

use App\Filament\Resources\PowerPlants\Pages\CreatePowerPlant;
use App\Filament\Resources\PowerPlants\Pages\EditPowerPlant;
use App\Filament\Resources\PowerPlants\Pages\ListPowerPlants;
use App\Filament\Resources\PowerPlants\Schemas\PowerPlantForm;
use App\Filament\Resources\PowerPlants\Tables\PowerPlantsTable;
use App\Models\PowerPlant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class PowerPlantResource extends Resource
{
    protected static ?string $model = PowerPlant::class;

    protected static string|UnitEnum|null $navigationGroup = 'RRMCS Master Data';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PowerPlantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PowerPlantsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPowerPlants::route('/'),
            'create' => CreatePowerPlant::route('/create'),
            'edit' => EditPowerPlant::route('/{record}/edit'),
        ];
    }
}
