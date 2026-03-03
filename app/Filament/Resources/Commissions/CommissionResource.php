<?php

declare(strict_types=1);

namespace App\Filament\Resources\Commissions;

use App\Filament\Resources\Commissions\Pages\ListCommissions;
use App\Filament\Resources\Commissions\Pages\ViewCommission;
use App\Filament\Resources\Commissions\Schemas\CommissionInfolist;
use App\Filament\Resources\Commissions\Tables\CommissionsTable;
use App\Models\Commission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class CommissionResource extends Resource
{
    protected static ?string $model = Commission::class;

    protected static string|UnitEnum|null $navigationGroup = 'Sales & Reservations';

    protected static ?int $navigationSort = 50;

    protected static ?string $recordTitleAttribute = 'id';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return CommissionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommissionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommissions::route('/'),
            'view' => ViewCommission::route('/{record}'),
        ];
    }
}
