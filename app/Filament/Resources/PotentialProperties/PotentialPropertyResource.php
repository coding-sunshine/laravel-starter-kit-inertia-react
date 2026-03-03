<?php

declare(strict_types=1);

namespace App\Filament\Resources\PotentialProperties;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\PotentialProperties\Pages\ListPotentialProperties;
use App\Models\PotentialProperty;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class PotentialPropertyResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = PotentialProperty::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|UnitEnum|null $navigationGroup = 'Properties';

    protected static ?int $navigationSort = 83;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static ?string $navigationLabel = 'Potential Properties';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return Tables\PotentialPropertiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPotentialProperties::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
