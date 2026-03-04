<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\PropertyReservations\PropertyReservationResource;
use App\Filament\Resources\Sales\SaleResource;
use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Contact;
use App\Models\PropertyReservation;
use App\Models\Sale;
use App\Models\Task;
use App\Services\TenantContext;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class CrmStatsOverviewWidget extends BaseStatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $hasTenant = TenantContext::check();
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        if (! $hasTenant) {
            return [
                Stat::make('Contacts', '0')
                    ->description('Select an organization')
                    ->icon('heroicon-o-users')
                    ->color('gray'),
                Stat::make('Open tasks', '0')
                    ->description('Select an organization')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('gray'),
                Stat::make('Reservations (this month)', '0')
                    ->description('Select an organization')
                    ->icon('heroicon-o-calendar-days')
                    ->color('gray'),
                Stat::make('Sales (this month)', '0')
                    ->description('Select an organization')
                    ->icon('heroicon-o-banknotes')
                    ->color('gray'),
            ];
        }

        $contacts = Contact::query()->count();
        $tasksOpen = Task::query()->whereNull('completed_at')->count();
        $reservationsMonth = PropertyReservation::query()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();
        $salesMonth = Sale::query()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        return [
            Stat::make('Contacts', (string) $contacts)
                ->description('Total in organization')
                ->icon('heroicon-o-users')
                ->color('slate'),
            Stat::make('Open tasks', (string) $tasksOpen)
                ->description('Not completed')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('amber'),
            Stat::make('Reservations (this month)', (string) $reservationsMonth)
                ->url(PropertyReservationResource::getUrl('index'))
                ->description('This month')
                ->icon('heroicon-o-calendar-days')
                ->color('blue'),
            Stat::make('Sales (this month)', (string) $salesMonth)
                ->url(SaleResource::getUrl('index'))
                ->description('This month')
                ->icon('heroicon-o-banknotes')
                ->color('emerald'),
        ];
    }

    public static function canView(): bool
    {
        return true;
    }
}
