<?php

declare(strict_types=1);

namespace App\Filament\Resources\States;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\States\Pages\ListStates;
use App\Models\State;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class StateResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = State::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Properties';

    protected static ?int $navigationSort = 81;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return Tables\StatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStates::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
