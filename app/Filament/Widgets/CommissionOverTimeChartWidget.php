<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Sale;
use App\Services\TenantContext;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;

/**
 * Commission (estimated) by month for the current year — like the old CRM Commission chart.
 */
final class CommissionOverTimeChartWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        $suffix = TenantContext::check() ? '' : ' — Select an organization to see data';

        return 'Commission (estimated) — ' . now()->year . $suffix;
    }

    protected function getMaxHeight(): ?string
    {
        return '220px';
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        if (! TenantContext::check()) {
            return [
                'datasets' => [['label' => 'Commission', 'data' => [0], 'backgroundColor' => 'rgba(148, 163, 184, 0.3)', 'borderColor' => 'rgb(148, 163, 184)', 'borderWidth' => 1]],
                'labels' => ['Select an organization'],
            ];
        }

        $year = (int) now()->year;
        $start = Carbon::createFromDate($year, 1, 1)->startOfMonth();
        $end = Carbon::createFromDate($year, 12, 31)->endOfMonth();
        $months = CarbonPeriod::create($start, '1 month', $end);

        $labels = [];
        $totals = [];

        foreach ($months as $month) {
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            $labels[] = $monthStart->format('M');

            $row = Sale::query()
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->selectRaw('COALESCE(SUM(piab_comm), 0) + COALESCE(SUM(sas_fee), 0) as total')
                ->value('total');

            $totals[] = round((float) $row, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Commission ($)',
                    'data' => $totals,
                    'backgroundColor' => 'rgba(0, 207, 221, 0.5)',
                    'borderColor' => 'rgb(0, 207, 221)',
                    'borderWidth' => 1,
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
