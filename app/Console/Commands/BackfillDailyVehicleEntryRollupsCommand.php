<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\RecalculateDailySidingVehicleDispatchRollups;
use App\Actions\RecalculateDailyVehicleEntryRollups;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Backfills {@see \App\Models\DailyVehicleEntryRollup} from {@see \App\Models\DailyVehicleEntry} road-dispatch rows only.
 *
 * Defaults to **1 April** (current Indian FY start) through **today** (app timezone).
 *
 * Usage:
 *   php artisan rollup:backfill-daily-vehicle-entries
 *   php artisan rollup:backfill-daily-vehicle-entries --from=2026-04-01 --to=2026-05-01
 *   php artisan rollup:backfill-daily-vehicle-entries --from=2026-05-01 --to=2026-05-01 --siding_id=3
 *   php artisan rollup:backfill-daily-vehicle-entries --dry-run
 */
final class BackfillDailyVehicleEntryRollupsCommand extends Command
{
    protected $signature = 'rollup:backfill-daily-vehicle-entries
                            {--from= : Start calendar date (Y-m-d); defaults to current FY start}
                            {--to= : End calendar date (Y-m-d); defaults to today}
                            {--siding_id= : Limit rebuild to one siding_id}
                            {--skip-fy-validation : Allow --from/--to outside current FY (operator escape hatch)}
                            {--dry-run : Show aggregated rows that would be inserted; do not delete or write}';

    protected $description = 'Rebuild daily road-dispatch vehicle entry aggregates into daily_vehicle_entry_rollups';

    public function handle(RecalculateDailyVehicleEntryRollups $rollup): int
    {
        $tz = config('app.timezone', 'UTC');
        $today = CarbonImmutable::now($tz)->startOfDay();
        $fyStartYear = $today->month >= 4 ? $today->year : $today->year - 1;
        $fyAprilFirst = CarbonImmutable::parse(sprintf('%d-04-01', $fyStartYear), $tz)->startOfDay();
        $fyLabel = sprintf('%d-%s', $fyStartYear, mb_substr((string) ($fyStartYear + 1), -2));

        $anchor = $today;

        $fromInput = $this->option('from');
        $toInput = $this->option('to');

        $fromDate = $fromInput !== null && $fromInput !== ''
            ? CarbonImmutable::parse((string) $fromInput)->toDateString()
            : $fyAprilFirst->toDateString();

        $toDate = $toInput !== null && $toInput !== ''
            ? CarbonImmutable::parse((string) $toInput)->toDateString()
            : $today->toDateString();

        if (! $this->option('skip-fy-validation')) {
            RecalculateDailySidingVehicleDispatchRollups::assertDatesWithinIndianFiscalYear($anchor, $fromDate, $toDate);
        }

        $sidingOption = $this->option('siding_id');
        $sidingId = $sidingOption !== null && $sidingOption !== ''
            ? (int) $sidingOption
            : null;

        if ($this->option('dry-run')) {
            $fetchStart = microtime(true);
            $preview = $rollup->preview($fromDate, $toDate, $sidingId);
            $fetchMs = round((microtime(true) - $fetchStart) * 1000, 2);

            $tableRows = $preview->map(fn (object $r): array => [
                (string) $r->rollup_day,
                (string) $r->siding_id,
                (string) $r->shift,
                (string) $r->entries_count,
                (string) $r->completed_entries_count,
                (string) $r->pending_entries_count,
                (string) $r->completed_net_wt_mt,
                (string) $r->pending_gross_wt_mt,
            ])->all();

            $renderStart = microtime(true);
            $this->table(
                [
                    'rollup_day',
                    'siding_id',
                    'shift',
                    'entries_count',
                    'completed_entries_count',
                    'pending_entries_count',
                    'completed_net_wt_mt',
                    'pending_gross_wt_mt',
                ],
                $tableRows,
            );
            $renderMs = round((microtime(true) - $renderStart) * 1000, 2);

            $totalMs = round($fetchMs + $renderMs, 2);

            $this->components->info(sprintf(
                'Dry run FY %s · aggregate rows: %d · fetch %s ms · render table %s ms · total ~%s ms (%s → %s)%s.',
                $fyLabel,
                count($tableRows),
                $fetchMs,
                $renderMs,
                $totalMs,
                $fromDate,
                $toDate,
                $sidingId !== null ? sprintf(' · siding_id=%d', $sidingId) : '',
            ));

            return self::SUCCESS;
        }

        $rows = $rollup->handle($fromDate, $toDate, $sidingId);

        $this->components->info(sprintf(
            'FY label %s · rebuilt rollup rows inserted: %d (%s → %s)%s.',
            $fyLabel,
            $rows,
            $fromDate,
            $toDate,
            $sidingId !== null ? sprintf(' · siding_id=%d', $sidingId) : '',
        ));

        return self::SUCCESS;
    }
}
