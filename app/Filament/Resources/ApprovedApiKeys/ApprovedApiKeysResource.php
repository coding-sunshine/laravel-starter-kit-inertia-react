<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApprovedApiKeys;

use App\Filament\Resources\ApprovedApiKeys\Pages\ListApprovedApiKeys;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Laravel\Sanctum\PersonalAccessToken;
use UnitEnum;

final class ApprovedApiKeysResource extends Resource
{
    protected static ?string $model = PersonalAccessToken::class;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 11;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'Approved API Keys';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return Tables\ApprovedApiKeysTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApprovedApiKeys::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
