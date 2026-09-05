<?php

declare(strict_types=1);

namespace App\Filament\Resources\Loaders;

use App\Filament\Resources\Loaders\Pages\CreateLoader;
use App\Filament\Resources\Loaders\Pages\EditLoader;
use App\Filament\Resources\Loaders\Pages\ListLoaders;
use App\Filament\Resources\Loaders\Schemas\LoaderForm;
use App\Filament\Resources\Loaders\Tables\LoadersTable;
use App\Models\Loader;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

final class LoaderResource extends Resource
{
    protected static ?string $model = Loader::class;

    protected static string|UnitEnum|null $navigationGroup = 'RRMCS Master Data';

    protected static ?string $recordTitleAttribute = 'loader_name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return LoaderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoadersTable::configure($table);
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
            'index' => ListLoaders::route('/'),
            'create' => CreateLoader::route('/create'),
            'edit' => EditLoader::route('/{record}/edit'),
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
