<?php

declare(strict_types=1);

namespace App\Filament\Resources\CommodityUtilisationThresholds;

use App\Models\CommodityUtilisationThreshold;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class CommodityUtilisationThresholdResource extends Resource
{
    protected static ?string $model = CommodityUtilisationThreshold::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::AdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'Penalties';

    protected static ?string $navigationLabel = 'PLO Utilisation Thresholds';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('commodity_grade')->required()->maxLength(64),
            TextInput::make('utilisation_threshold')->required()->numeric()->step(0.001),
            DateTimePicker::make('effective_from')->required(),
            DateTimePicker::make('effective_to')->nullable(),
            TextInput::make('source')->maxLength(128),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('commodity_grade')->sortable()->searchable(),
                TextColumn::make('utilisation_threshold')->numeric(3),
                TextColumn::make('effective_from')->dateTime()->sortable(),
                TextColumn::make('effective_to')->dateTime()->placeholder('Open'),
                TextColumn::make('source')->limit(40),
            ])
            ->defaultSort('effective_from', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommodityUtilisationThresholds::route('/'),
            'create' => Pages\CreateCommodityUtilisationThreshold::route('/create'),
            'edit' => Pages\EditCommodityUtilisationThreshold::route('/{record}/edit'),
        ];
    }
}
