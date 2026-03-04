<?php

declare(strict_types=1);

namespace App\Filament\Resources\PIABAdmins;

use App\Filament\Resources\PIABAdmins\Pages\ListPIABAdmins;
use App\Filament\Resources\PIABAdmins\Tables\PIABAdminsTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class PIABAdminResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Accounts';

    protected static ?int $navigationSort = 8;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'PIAB Admins';

    public static function getModelLabel(): string
    {
        return 'PIAB Admin';
    }

    public static function getPluralModelLabel(): string
    {
        return 'PIAB Admins';
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'piab-admin')
                    ->orWhere('name', 'PIAB Admin')
                    ->orWhere('name', 'like', '%PIAB%');
            });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return PIABAdminsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPIABAdmins::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
