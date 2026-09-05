<?php

declare(strict_types=1);

namespace App\Filament\Resources\FreightRateMasters;

use App\Filament\Resources\FreightRateMasters\Pages\CreateFreightRateMaster;
use App\Filament\Resources\FreightRateMasters\Pages\EditFreightRateMaster;
use App\Filament\Resources\FreightRateMasters\Pages\ListFreightRateMasters;
use App\Filament\Resources\FreightRateMasters\Schemas\FreightRateMasterForm;
use App\Filament\Resources\FreightRateMasters\Tables\FreightRateMastersTable;
use App\Models\FreightRateMaster;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

final class FreightRateMasterResource extends Resource
{
    protected static ?string $model = FreightRateMaster::class;

    protected static string|UnitEnum|null $navigationGroup = 'RRMCS Master Data';

    protected static ?string $recordTitleAttribute = 'commodity_name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return FreightRateMasterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FreightRateMastersTable::configure($table);
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
            'index' => ListFreightRateMasters::route('/'),
            'create' => CreateFreightRateMaster::route('/create'),
            'edit' => EditFreightRateMaster::route('/{record}/edit'),
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
