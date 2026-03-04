<?php

declare(strict_types=1);

namespace App\Filament\Resources\Favourites;

use App\Filament\Resources\Favourites\Pages\ListFavourites;
use App\Filament\Resources\Favourites\Tables\FavouritesTable;
use App\Models\Project;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

final class FavouritesResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|UnitEnum|null $navigationGroup = 'Property Portal';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Favourites';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    public static function getEloquentQuery(): Builder
    {
        // TODO: Replace this with actual favorites logic when favorites system is implemented
        // This could be a pivot table with users, or a favorites field on projects/lots
        // For now, returning projects that are hot properties as a placeholder
        return parent::getEloquentQuery()->where('is_hot_property', true);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return FavouritesTable::configure($table);
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
            'index' => ListFavourites::route('/'),
        ];
    }
}
