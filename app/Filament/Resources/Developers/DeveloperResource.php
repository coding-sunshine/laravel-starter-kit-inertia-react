<?php

declare(strict_types=1);

namespace App\Filament\Resources\Developers;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\Developers\Pages\ListDevelopers;
use App\Models\Developer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class DeveloperResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = Developer::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Accounts';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Developers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return Tables\DevelopersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDevelopers::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
