<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tasks\Widgets;

use App\Models\Task;
use App\Services\TenantContext;
use Filament\Widgets\Widget;

final class TaskStatsWidget extends Widget
{
    protected string $view = 'filament.resources.tasks.widgets.task-stats';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    public function getViewData(): array
    {
        if (! TenantContext::check()) {
            return ['open' => 0, 'overdue' => 0, 'completionRate' => 0, 'total' => 0];
        }
        $now = now();
        $open = Task::query()->whereNull('completed_at')->count();
        $overdue = Task::query()
            ->whereNull('completed_at')
            ->where('due_at', '<', $now)
            ->count();
        $total = Task::query()->count();
        $completed = Task::query()->whereNotNull('completed_at')->count();
        $completionRate = $total > 0 ? round($completed / $total * 100, 1) : 0;

        return [
            'open' => $open,
            'overdue' => $overdue,
            'completionRate' => $completionRate,
            'total' => $total,
            'completed' => $completed,
        ];
    }

    public static function canView(): bool
    {
        return TenantContext::check();
    }
}
