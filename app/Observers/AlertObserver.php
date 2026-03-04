<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\Fleet\CriticalAlertTriggered;
use App\Models\Fleet\Alert;
use App\Models\Organization;

final class AlertObserver
{
    public function created(Alert $alert): void
    {
        if (! in_array($alert->severity, ['critical', 'emergency'], true)) {
            return;
        }

        $organization = Organization::query()->find($alert->organization_id);
        if ($organization !== null) {
            event(new CriticalAlertTriggered($alert, $organization));
        }

        $alert->updateQuietly(['notification_sent' => true]);
    }
}
