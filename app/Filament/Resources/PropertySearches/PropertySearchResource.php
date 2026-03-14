<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertySearches;

use App\Filament\Resources\PropertySearches\Pages\CreatePropertySearch;
use App\Filament\Resources\PropertySearches\Pages\EditPropertySearch;
use App\Filament\Resources\PropertySearches\Pages\ListPropertySearches;
use App\Filament\Resources\PropertySearches\Schemas\PropertySearchForm;
use App\Filament\Resources\PropertySearches\Tables\PropertySearchesTable;
use App\Models\PropertySearch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

final class PropertySearchResource extends Resource
{
    protected static ?string $model = PropertySearch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PropertySearchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PropertySearchesTable::configure($table);
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
            'index' => ListPropertySearches::route('/'),
            'create' => CreatePropertySearch::route('/create'),
            'edit' => EditPropertySearch::route('/{record}/edit'),
        ];
    }
}
