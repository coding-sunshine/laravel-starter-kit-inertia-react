<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyReservations;

use App\Filament\Resources\PropertyReservations\Pages\CreatePropertyReservation;
use App\Filament\Resources\PropertyReservations\Pages\EditPropertyReservation;
use App\Filament\Resources\PropertyReservations\Pages\ListPropertyReservations;
use App\Filament\Resources\PropertyReservations\Schemas\PropertyReservationForm;
use App\Filament\Resources\PropertyReservations\Tables\PropertyReservationsTable;
use App\Models\PropertyReservation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

final class PropertyReservationResource extends Resource
{
    protected static ?string $model = PropertyReservation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PropertyReservationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PropertyReservationsTable::configure($table);
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
            'index' => ListPropertyReservations::route('/'),
            'create' => CreatePropertyReservation::route('/create'),
            'edit' => EditPropertyReservation::route('/{record}/edit'),
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
