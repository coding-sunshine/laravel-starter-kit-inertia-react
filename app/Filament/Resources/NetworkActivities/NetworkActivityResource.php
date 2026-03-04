<?php

declare(strict_types=1);

namespace App\Filament\Resources\NetworkActivities;

use AlizHarb\ActivityLog\Models\Activity;
use App\Filament\Resources\NetworkActivities\Pages\ListNetworkActivities;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class NetworkActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $navigationLabel = 'Network Activity';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return Tables\NetworkActivitiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNetworkActivities::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
