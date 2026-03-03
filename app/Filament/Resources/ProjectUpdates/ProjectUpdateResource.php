<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProjectUpdates;

use App\Filament\Resources\ProjectUpdates\Pages\ListProjectUpdates;
use App\Models\ProjectUpdate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class ProjectUpdateResource extends Resource
{
    protected static ?string $model = ProjectUpdate::class;

    protected static string|UnitEnum|null $navigationGroup = 'Properties';

    protected static ?int $navigationSort = 84;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static ?string $navigationLabel = 'Project Updates';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return Tables\ProjectUpdatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjectUpdates::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
