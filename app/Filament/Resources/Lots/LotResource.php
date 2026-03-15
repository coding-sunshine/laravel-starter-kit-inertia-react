<?php

declare(strict_types=1);

namespace App\Filament\Resources\Lots;

use App\Filament\Resources\Lots\Pages\CreateLot;
use App\Filament\Resources\Lots\Pages\EditLot;
use App\Filament\Resources\Lots\Pages\ListLots;
use App\Filament\Resources\Lots\Pages\ViewLot;
use App\Filament\Resources\Lots\Schemas\LotForm;
use App\Filament\Resources\Lots\Schemas\LotInfolist;
use App\Filament\Resources\Lots\Tables\LotsTable;
use App\Models\Lot;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

final class LotResource extends Resource
{
    protected static ?string $model = Lot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|UnitEnum|null $navigationGroup = 'Properties';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return LotForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LotInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LotsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLots::route('/'),
            'create' => CreateLot::route('/create'),
            'view' => ViewLot::route('/{record}'),
            'edit' => EditLot::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
