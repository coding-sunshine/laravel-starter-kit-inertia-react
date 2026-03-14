<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Commission;
use App\Models\Contact;
use App\Models\PropertyReservation;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

final class DashboardInsightJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $newContacts = Contact::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $overdueTasksCount = Task::query()
            ->where('due_at', '<', now())
            ->where('status', '!=', 'done')
            ->count();

        $activeReservations = PropertyReservation::query()
            ->whereNotIn('stage', ['settled', 'cancelled'])
            ->count();

        $commissionsDue = Commission::query()
            ->sum('amount');

        $insight = sprintf(
            '%d new contacts this week. %d tasks overdue. %d active reservations. $%s commissions total.',
            $newContacts,
            $overdueTasksCount,
            $activeReservations,
            number_format((float) $commissionsDue, 0),
        );

        Cache::put('dashboard_insight', $insight, now()->addDay());
    }
}
