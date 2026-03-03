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
        if (! TenantContext::check()) {
            return [];
        }

        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

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
                ->description('Total in organization'),
            Stat::make('Open tasks', (string) $tasksOpen)
                ->description('Not completed'),
            Stat::make('Reservations (this month)', (string) $reservationsMonth)
                ->url(PropertyReservationResource::getUrl('index')),
            Stat::make('Sales (this month)', (string) $salesMonth)
                ->url(SaleResource::getUrl('index')),
        ];
    }

    public static function canView(): bool
    {
        return TenantContext::check();
    }
}
