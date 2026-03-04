<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertySearches;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\PropertySearches\Pages\CreatePropertySearch;
use App\Filament\Resources\PropertySearches\Pages\EditPropertySearch;
use App\Filament\Resources\PropertySearches\Pages\ListPropertySearches;
use App\Filament\Resources\PropertySearches\Pages\ViewPropertySearch;
use App\Filament\Resources\PropertySearches\Schemas\PropertySearchForm;
use App\Filament\Resources\PropertySearches\Schemas\PropertySearchInfolist;
use App\Filament\Resources\PropertySearches\Tables\PropertySearchesTable;
use App\Models\PropertySearch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class PropertySearchResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = PropertySearch::class;

    protected static string|UnitEnum|null $navigationGroup = 'Online Forms';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationLabel = 'Property Search Request';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    public static function form(Schema $schema): Schema
    {
        return PropertySearchForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PropertySearchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PropertySearchesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPropertySearches::route('/'),
            'create' => CreatePropertySearch::route('/create'),
            'view' => ViewPropertySearch::route('/{record}'),
            'edit' => EditPropertySearch::route('/{record}/edit'),
        ];
    }
}
