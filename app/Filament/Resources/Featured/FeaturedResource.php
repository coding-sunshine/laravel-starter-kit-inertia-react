<?php

declare(strict_types=1);

namespace App\Filament\Resources\Featured;

use App\Filament\Resources\Featured\Pages\ListFeatured;
use App\Filament\Resources\Featured\Tables\FeaturedTable;
use App\Models\Project;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

final class FeaturedResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|UnitEnum|null $navigationGroup = 'Property Portal';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Featured';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_featured', true);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return FeaturedTable::configure($table);
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
            'index' => ListFeatured::route('/'),
        ];
    }
}
