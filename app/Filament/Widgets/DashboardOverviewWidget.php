<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Actions\GetDashboardMetrics;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class DashboardOverviewWidget extends BaseStatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $metrics = resolve(GetDashboardMetrics::class)->handle();
        $kpis = $metrics['kpis'];

        return [
            Stat::make('Total Projects', number_format($kpis['total_projects']))
                ->description('Active development projects')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success'),

            Stat::make('Total Lots', number_format($kpis['total_lots']))
                ->description('Available for sale')
                ->descriptionIcon('heroicon-m-home')
                ->color('info'),

            Stat::make('Total Revenue', '$' . number_format($kpis['total_revenue'] / 1000000, 1) . 'M')
                ->description($kpis['monthly_growth'] > 0 ?
                    '↗ ' . number_format($kpis['monthly_growth'], 1) . '% vs last month' :
                    ($kpis['monthly_growth'] < 0 ?
                        '↘ ' . number_format(abs($kpis['monthly_growth']), 1) . '% vs last month' :
                        'No change vs last month'))
                ->descriptionIcon($kpis['monthly_growth'] > 0 ? 'heroicon-m-arrow-trending-up' :
                    ($kpis['monthly_growth'] < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-minus'))
                ->color($kpis['monthly_growth'] > 0 ? 'success' :
                    ($kpis['monthly_growth'] < 0 ? 'danger' : 'gray')),

            Stat::make('Pipeline Value', '$' . number_format($kpis['pipeline_value'] / 1000000, 1) . 'M')
                ->description('Potential revenue')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('Total Contacts', number_format($kpis['total_contacts']))
                ->description('Leads and customers')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Avg Deal Value', '$' . number_format($kpis['average_deal_value'] / 1000) . 'K')
                ->description('Per transaction')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Conversion Rate', number_format($kpis['conversion_rate'], 1) . '%')
                ->description('Leads to sales')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($kpis['conversion_rate'] > 10 ? 'success' : 'warning'),

            Stat::make('Total Sales', number_format($kpis['total_sales']))
                ->description('Completed transactions')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info'),
        ];
    }
}