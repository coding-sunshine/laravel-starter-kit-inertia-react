<?php

declare(strict_types=1);

namespace App\Filament\Resources\SameDeviceDetections;

use App\Filament\Resources\SameDeviceDetections\Pages\ListSameDeviceDetections;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class SameDeviceDetectionResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static ?string $navigationLabel = 'Same Device Detection';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return Tables\SameDeviceDetectionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSameDeviceDetections::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
