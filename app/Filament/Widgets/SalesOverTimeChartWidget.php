<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Sale;
use App\Services\TenantContext;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

final class SalesOverTimeChartWidget extends ChartWidget
{
    public function getHeading(): ?string
    {
        $suffix = TenantContext::check() ? '' : ' — Select an organization to see data';

        return 'Sales (last 30 days)' . $suffix;
    }

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected function getMaxHeight(): ?string
    {
        return '220px';
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        if (! TenantContext::check()) {
            return [
                'datasets' => [['label' => 'Sales', 'data' => [0], 'borderColor' => 'rgb(148, 163, 184)', 'backgroundColor' => 'rgba(148, 163, 184, 0.1)', 'fill' => true, 'tension' => 0.3]],
                'labels' => ['Select an organization'],
            ];
        }

        $days = 30;
        $driver = DB::getDriverName();
        $dateExpression = $driver === 'pgsql' ? 'DATE(created_at)' : 'DATE(created_at)';

        $range = collect(range(0, $days - 1))->map(fn ($i) => now()->subDays($days - 1 - $i)->format('Y-m-d'));

        $counts = Sale::query()
            ->select(DB::raw("{$dateExpression} as date"), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy(DB::raw($dateExpression))
            ->orderBy(DB::raw($dateExpression))
            ->get()
            ->pluck('count', 'date')
            ->all();

        $data = $range->map(fn ($d) => (int) ($counts[$d] ?? 0))->values()->all();
        $labels = $range->map(fn ($d) => Carbon::parse($d)->format('M j'))->values()->all();

        return [
            'datasets' => [
                [
                    'label' => 'Sales',
                    'data' => $data,
                    'borderColor' => 'rgb(51, 65, 85)',
                    'backgroundColor' => 'rgba(51, 65, 85, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    public static function canView(): bool
    {
        return true;
    }
}
