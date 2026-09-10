<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sidings;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\Sidings\Pages\CreateSiding;
use App\Filament\Resources\Sidings\Pages\EditSiding;
use App\Filament\Resources\Sidings\Pages\ListSidings;
use App\Filament\Resources\Sidings\Schemas\SidingForm;
use App\Filament\Resources\Sidings\Tables\SidingsTable;
use App\Models\Siding;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

final class SidingResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = Siding::class;

    protected static string|UnitEnum|null $navigationGroup = 'RRMCS Master Data';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SidingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SidingsTable::configure($table);
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
            'index' => ListSidings::route('/'),
            'create' => CreateSiding::route('/create'),
            'edit' => EditSiding::route('/{record}/edit'),
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
