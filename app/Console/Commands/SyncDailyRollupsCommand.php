<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\RecalculateDailySidingVehicleDispatchRollups;
use App\Actions\RecalculateDailyVehicleEntryRollups;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Rebuilds today’s rows in {@see \App\Models\DailyVehicleEntryRollup} (road dispatch only)
 * and {@see \App\Models\DailySidingVehicleDispatchRollup}.
 *
 * During the first local hours of the calendar day (current hour strictly less than the
 * configured cutoff), the date range includes yesterday so late facts still roll into prior day.
 *
 * At most two calendar days are rebuilt. Indian FY validation used by interactive backfills
 * is omitted so cross-FY mornings (e.g. 1 Apr) still refresh the prior calendar day when needed.
 *
 * Usage:
 *   php artisan rollup:sync-daily
 *   php artisan rollup:sync-daily --only=vehicle-entries
 *   php artisan rollup:sync-daily --only=siding-dispatches
 *   php artisan rollup:sync-daily --early-until-hour=8
 *   php artisan rollup:sync-daily --siding_id=3
 */
final class SyncDailyRollupsCommand extends Command
{
    private const OPT_VEHICLE_ENTRIES = 'vehicle-entries';

    private const OPT_SIDING_DISPATCHES = 'siding-dispatches';

    protected $signature = 'rollup:sync-daily
                            {--only= : Scope: '.self::OPT_VEHICLE_ENTRIES.', '.self::OPT_SIDING_DISPATCHES.', omit for both}
                            {--early-until-hour= : Local hour 0–23: if current hour is below this, also rebuild yesterday; default from rollups config}
                            {--siding_id= : Limit both rollups to one siding_id}';

    protected $description = 'Rebuild daily_vehicle_entry_rollups and/or daily_siding_vehicle_dispatch_rollups for today (and yesterday during early local hours)';

    public function handle(
        RecalculateDailyVehicleEntryRollups $vehicleEntryRollups,
        RecalculateDailySidingVehicleDispatchRollups $sidingDispatchRollups,
    ): int {
        $tz = config('app.timezone', 'UTC');
        $now = CarbonImmutable::now($tz);

        $earlyUntilOption = $this->option('early-until-hour');
        $earlyUntilHour = $earlyUntilOption !== null && $earlyUntilOption !== ''
            ? max(0, min(23, (int) $earlyUntilOption))
            : (int) config('rollups.daily_sync.early_refresh_until_hour_exclusive', 6);

        $todayStr = $now->toDateString();
        $hour = (int) $now->format('G');

        $fromStr = $todayStr;
        $includePreviousDay = $hour < $earlyUntilHour;

        if ($includePreviousDay) {
            $fromStr = $now->subDay()->toDateString();
        }

        $sidingOption = $this->option('siding_id');
        $sidingId = $sidingOption !== null && $sidingOption !== ''
            ? (int) $sidingOption
            : null;

        $onlyRaw = $this->option('only');
        $only = $onlyRaw !== null && $onlyRaw !== ''
            ? mb_strtolower(mb_trim((string) $onlyRaw))
            : null;

        if ($only !== null
            && $only !== self::OPT_VEHICLE_ENTRIES
            && $only !== self::OPT_SIDING_DISPATCHES) {
            $this->components->error(sprintf(
                '--only must be %s or %s; omit option to run both.',
                self::OPT_VEHICLE_ENTRIES,
                self::OPT_SIDING_DISPATCHES,
            ));

            return self::INVALID;
        }

        $this->components->info(sprintf(
            'Rolling up %s → %s (%s)%s · scope: %s · early cutoff hour %d · local hour %d',
            $fromStr,
            $todayStr,
            $tz,
            $includePreviousDay ? '; including previous calendar day' : '',
            $only ?? 'both',
            $earlyUntilHour,
            $hour,
        ));

        $vehicleRows = null;
        $sidingRows = null;

        if ($only === null || $only === self::OPT_VEHICLE_ENTRIES) {
            $vehicleRows = $vehicleEntryRollups->handle($fromStr, $todayStr, $sidingId);
        }

        if ($only === null || $only === self::OPT_SIDING_DISPATCHES) {
            $sidingRows = $sidingDispatchRollups->handle($fromStr, $todayStr, $sidingId);
        }

        if ($only === null) {
            $this->components->info(sprintf(
                'Inserted %d daily_vehicle_entry rollup row(s), %d daily_siding_vehicle_dispatch rollup row(s).',
                (int) $vehicleRows,
                (int) $sidingRows,
            ));
        } elseif ($vehicleRows !== null) {
            $this->components->info(sprintf(
                'Inserted %d daily_vehicle_entry rollup row(s).',
                (int) $vehicleRows,
            ));
        } else {
            $this->components->info(sprintf(
                'Inserted %d daily_siding_vehicle_dispatch rollup row(s).',
                (int) $sidingRows,
            ));
        }

        return self::SUCCESS;
    }
}
