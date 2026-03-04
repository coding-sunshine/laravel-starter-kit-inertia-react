<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Fleet\CriticalAlertTriggered;
use App\Models\Fleet\AlertPreference;
use App\Notifications\Fleet\CriticalFleetAlertNotification;
use Illuminate\Support\Facades\Notification;

final class SendCriticalFleetAlertNotifications
{
    public function handle(CriticalAlertTriggered $event): void
    {
        $organization = $event->organization;
        $alert = $event->alert;

        $userIds = AlertPreference::query()
            ->where('organization_id', $organization->id)
            ->where('alert_type', $alert->alert_type)
            ->where(function ($q): void {
                $q->where('email_enabled', true)
                    ->orWhere('sms_enabled', true)
                    ->orWhere('push_enabled', true)
                    ->orWhere('in_app_enabled', true);
            })
            ->pluck('user_id')
            ->unique()
            ->values()
            ->all();

        if ($userIds === []) {
            return;
        }

        $users = $organization->members()
            ->whereIn('users.id', $userIds)
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        Notification::send(
            $users,
            new CriticalFleetAlertNotification($alert, $organization)
        );
    }
}
