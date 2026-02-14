<?php

declare(strict_types=1);

namespace App\Observers;

use Spatie\Activitylog\Models\Activity;

final class ActivityLogObserver
{
    public function saving(Activity $activity): void
    {
        $taps = config('activitylog.activity_logger_taps', []);

        if (empty($taps)) {
            return;
        }

        $eventName = $activity->event ?? '';
        $subject = $activity->relationLoaded('subject') ? $activity->subject : null;
        $causer = $activity->relationLoaded('causer') ? $activity->causer : null;
        $properties = $activity->properties?->toArray();

        foreach ($taps as $tapClass) {
            if (is_string($tapClass) && class_exists($tapClass)) {
                $tap = resolve($tapClass);
                if (is_callable($tap)) {
                    $tap($activity, $eventName, $subject, $causer, $properties);
                }
            }
        }
    }
}
