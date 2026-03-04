<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class StatsOverviewWidget extends BaseStatsOverviewWidget
{
    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        return [
            Stat::make('Users', (string) User::query()->count())
                ->description('Total platform users')
                ->icon('heroicon-o-user-group')
                ->color('slate')
                ->url(UserResource::getUrl('index')),
        ];
    }
}
