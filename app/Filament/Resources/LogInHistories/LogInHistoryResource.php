<?php

declare(strict_types=1);

namespace App\Filament\Resources\LogInHistories;

use AlizHarb\ActivityLog\Models\Activity;
use App\Filament\Resources\LogInHistories\Pages\ListLogInHistories;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class LogInHistoryResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowRightOnRectangle;

    protected static ?string $navigationLabel = 'Log In History';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return Tables\LogInHistoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLogInHistories::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
