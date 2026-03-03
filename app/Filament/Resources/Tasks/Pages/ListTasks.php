<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Filament\Resources\Tasks\Widgets\TaskStatsWidget;
use Filament\Resources\Pages\ListRecords;

final class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    public function getHeaderWidgets(): array
    {
        return [
            TaskStatsWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
