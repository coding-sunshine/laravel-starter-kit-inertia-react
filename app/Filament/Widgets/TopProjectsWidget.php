<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Actions\GetDashboardMetrics;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

final class TopProjectsWidget extends Widget
{
    protected static ?int $sort = 2;

    protected string $view = 'filament.widgets.top-projects';
    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $metrics = resolve(GetDashboardMetrics::class)->handle();

        return [
            'projects' => collect($metrics['top_projects'])->take(8),
        ];
    }
}