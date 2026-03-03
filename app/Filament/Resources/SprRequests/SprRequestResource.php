<?php

declare(strict_types=1);

namespace App\Filament\Resources\SprRequests;

use App\Filament\Resources\SprRequests\Pages\ListSprRequests;
use App\Models\SprRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class SprRequestResource extends Resource
{
    protected static ?string $model = SprRequest::class;

    protected static string|UnitEnum|null $navigationGroup = 'Properties';

    protected static ?int $navigationSort = 85;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentArrowUp;

    protected static ?string $navigationLabel = 'SPR Requests';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return Tables\SprRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSprRequests::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
