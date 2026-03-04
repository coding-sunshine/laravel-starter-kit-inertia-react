<?php

declare(strict_types=1);

namespace App\Filament\Resources\Flyers;

use App\Filament\Resources\Flyers\Pages\ListFlyers;
use App\Models\Flyer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class FlyerResource extends Resource
{
    protected static ?string $model = Flyer::class;

    protected static ?string $navigationLabel = 'Landing Page';

    protected static string|UnitEnum|null $navigationGroup = 'Marketing Tools';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocument;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return Tables\FlyersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFlyers::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
