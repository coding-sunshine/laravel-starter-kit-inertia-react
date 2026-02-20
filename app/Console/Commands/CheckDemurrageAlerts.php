<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\SyncDemurrageAlertsAction;
use App\Events\DemurrageThresholdCrossed;
use App\Models\Rake;
use App\Models\Siding;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Scheduled command that checks all loading rakes for demurrage thresholds
 * and fires escalation events when thresholds are crossed.
 */
final class CheckDemurrageAlerts extends Command
{
    protected $signature = 'rrmcs:check-demurrage';

    protected $description = 'Check loading rakes for demurrage threshold crossings and fire escalation events';

    public function handle(SyncDemurrageAlertsAction $syncAlerts): int
    {
        $thresholds = (array) config('rrmcs.demurrage_thresholds', [60, 30, 0]);
        $rate = (float) config('rrmcs.demurrage_rate_per_mt_hour', 50);
        $allSidingIds = Siding::query()->pluck('id')->all();

        // Keep in-app alerts in sync (existing pattern from dashboard)
        $syncAlerts->handle($allSidingIds);

        $rakes = Rake::query()
            ->with('siding')
            ->where('state', 'loading')
            ->whereNotNull('loading_start_time')
            ->whereNotNull('free_time_minutes')
            ->get();

        $eventsFired = 0;

        foreach ($rakes as $rake) {
            $end = $rake->loading_start_time->copy()->addMinutes((int) $rake->free_time_minutes);
            $remainingMinutes = (int) \Illuminate\Support\Facades\Date::now()->diffInMinutes($end, false);

            $weight = $rake->loaded_weight_mt ?? $rake->predicted_weight_mt ?? 0;

            foreach ($thresholds as $thresholdMinutes) {
                if ($remainingMinutes > $thresholdMinutes) {
                    continue;
                }

                $thresholdKey = "demurrage_{$thresholdMinutes}";
                $cacheKey = "demurrage:{$rake->id}:{$thresholdKey}";

                // Don't re-fire the same threshold within 1 hour
                if (Cache::has($cacheKey)) {
                    continue;
                }

                $demurrageHours = $remainingMinutes <= 0
                    ? abs($remainingMinutes) / 60
                    : 1;
                $projectedPenalty = $demurrageHours * $weight * $rate;

                event(new DemurrageThresholdCrossed($rake, $thresholdKey, max(0, $remainingMinutes), $projectedPenalty));

                Cache::put($cacheKey, true, now()->addHour());
                $eventsFired++;
            }
        }

        $this->info("Checked {$rakes->count()} loading rakes, fired {$eventsFired} threshold events.");

        return self::SUCCESS;
    }
}
