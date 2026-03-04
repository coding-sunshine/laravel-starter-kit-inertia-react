<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Contact;
use App\Services\TenantContext;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

final class ContactsOverTimeChartWidget extends ChartWidget
{
    public function getHeading(): ?string
    {
        $suffix = TenantContext::check() ? '' : ' — Select an organization to see data';

        return 'Contacts created (last 30 days)' . $suffix;
    }

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 1;

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
                'datasets' => [['label' => 'Contacts', 'data' => [0], 'backgroundColor' => 'rgba(148, 163, 184, 0.3)', 'borderColor' => 'rgb(148, 163, 184)', 'borderWidth' => 1]],
                'labels' => ['Select an organization'],
            ];
        }

        $days = 30;
        $driver = DB::getDriverName();
        $dateExpression = $driver === 'pgsql' ? 'DATE(created_at)' : 'DATE(created_at)';

        $range = collect(range(0, $days - 1))->map(fn ($i) => now()->subDays($days - 1 - $i)->format('Y-m-d'));

        $counts = Contact::query()
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
                    'label' => 'Contacts',
                    'data' => $data,
                    'backgroundColor' => 'rgba(71, 85, 105, 0.5)',
                    'borderColor' => 'rgb(71, 85, 105)',
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
