<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DemurrageThresholdCrossed;
use App\Models\Siding;
use App\Models\User;
use App\Notifications\DemurrageAlertNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Sends role-based demurrage notifications when a threshold is crossed.
 *
 * Escalation:
 *  - demurrage_60 → siding operators on that siding
 *  - demurrage_30 → siding in-charge on that siding
 *  - demurrage_0  → management + all siding users
 */
final class SendDemurrageEscalation
{
    public function handle(DemurrageThresholdCrossed $event): void
    {
        $siding = $event->rake->siding;
        if (! $siding instanceof Siding) {
            return;
        }

        $recipients = $this->resolveRecipients($event->threshold, $siding);
        if ($recipients === []) {
            return;
        }

        // Filter out users already notified for this rake + threshold in the last hour
        $recipients = $this->filterDuplicates($recipients, $event);

        if ($recipients === []) {
            return;
        }

        $notification = new DemurrageAlertNotification(
            $event->rake,
            $event->threshold,
            $event->remainingMinutes,
            $event->projectedPenalty,
        );

        Notification::send($recipients, $notification);
    }

    /**
     * @return array<User>
     */
    private function resolveRecipients(string $threshold, Siding $siding): array
    {
        $sidingUsers = $siding->users()->get();

        return match ($threshold) {
            'demurrage_60' => $sidingUsers
                ->filter(fn (User $u): bool => $u->hasRole('siding_operator'))
                ->values()
                ->all(),

            'demurrage_30' => $sidingUsers
                ->filter(fn (User $u): bool => $u->hasRole('siding_in_charge'))
                ->values()
                ->all(),

            'demurrage_0' => collect()
                ->merge($sidingUsers)
                ->merge(User::query()->role('management')->get())
                ->unique('id')
                ->values()
                ->all(),

            default => [],
        };
    }

    /**
     * Skip users who were already notified for this rake + threshold in the last hour.
     *
     * @param  array<User>  $recipients
     * @return array<User>
     */
    private function filterDuplicates(array $recipients, DemurrageThresholdCrossed $event): array
    {
        $rakeId = $event->rake->id;
        $threshold = $event->threshold;
        $oneHourAgo = now()->subHour();

        return array_values(array_filter($recipients, fn (User $user): bool => ! $user->notifications()
            ->where('type', DemurrageAlertNotification::class)
            ->where('created_at', '>=', $oneHourAgo)
            ->whereJsonContains('data->rake_id', $rakeId)
            ->whereJsonContains('data->threshold', $threshold)
            ->exists()));
    }
}
