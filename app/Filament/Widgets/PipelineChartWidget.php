<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Actions\GetDashboardMetrics;
use Filament\Widgets\ChartWidget;

final class PipelineChartWidget extends ChartWidget
{
    protected static ?int $sort = 1;

    public function getHeading(): ?string
    {
        return 'Sales Pipeline';
    }

    public function getDescription(): ?string
    {
        return 'Projects by development stage';
    }

    protected function getData(): array
    {
        $metrics = resolve(GetDashboardMetrics::class)->handle();
        $pipeline = $metrics['charts']['pipeline'];

        return [
            'datasets' => [
                [
                    'label' => 'Project Count',
                    'data' => array_column($pipeline, 'count'),
                    'backgroundColor' => [
                        '#3b82f6', // blue
                        '#10b981', // emerald
                        '#f59e0b', // amber
                        '#ef4444', // red
                        '#8b5cf6', // violet
                    ],
                ],
            ],
            'labels' => array_column($pipeline, 'stage'),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}