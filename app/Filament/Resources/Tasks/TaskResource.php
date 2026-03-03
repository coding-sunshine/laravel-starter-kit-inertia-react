<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tasks;

use App\Filament\Concerns\ScopesToCurrentTenant;
use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Models\Task;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class TaskResource extends Resource
{
    use ScopesToCurrentTenant;

    protected static ?string $model = Task::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|UnitEnum|null $navigationGroup = 'Tasks & Marketing';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Tasks';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return \App\Filament\Resources\Tasks\Tables\TasksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTasks::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
