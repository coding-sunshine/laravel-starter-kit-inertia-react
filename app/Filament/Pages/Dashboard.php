<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardOverviewWidget;
use App\Filament\Widgets\PipelineChartWidget;
use App\Filament\Widgets\TopProjectsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

final class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'CRM Dashboard';

    public function getWidgets(): array
    {
        return [
            DashboardOverviewWidget::class,
            PipelineChartWidget::class,
            TopProjectsWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 1;
    }
}
