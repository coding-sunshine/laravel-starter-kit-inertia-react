<?php

declare(strict_types=1);

namespace App\Filament\Resources\PropertyReservations;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\PropertyReservations\Pages\CreatePropertyReservation;
use App\Filament\Resources\PropertyReservations\Pages\EditPropertyReservation;
use App\Filament\Resources\PropertyReservations\Pages\ListPropertyReservations;
use App\Filament\Resources\PropertyReservations\Pages\ViewPropertyReservation;
use App\Filament\Resources\PropertyReservations\Schemas\PropertyReservationForm;
use App\Filament\Resources\PropertyReservations\Schemas\PropertyReservationInfolist;
use App\Filament\Resources\PropertyReservations\Tables\PropertyReservationsTable;
use App\Models\PropertyReservation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class PropertyReservationResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = PropertyReservation::class;

    protected static string|UnitEnum|null $navigationGroup = 'Sales & Reservations';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'id';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function form(Schema $schema): Schema
    {
        return PropertyReservationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PropertyReservationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PropertyReservationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPropertyReservations::route('/'),
            'create' => CreatePropertyReservation::route('/create'),
            'view' => ViewPropertyReservation::route('/{record}'),
            'edit' => EditPropertyReservation::route('/{record}/edit'),
        ];
    }
}
