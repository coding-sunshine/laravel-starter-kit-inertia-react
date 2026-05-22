<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\RecalculateDailySidingVehicleDispatchRollups;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Backfills {@see \App\Models\DailySidingVehicleDispatchRollup} from {@see \App\Models\SidingVehicleDispatch}.
 *
 * Defaults to **1 April** (current Indian FY start) through **today** (app timezone).
 *
 * Usage:
 *   php artisan rollup:backfill-daily-siding-vehicle-dispatches
 *   php artisan rollup:backfill-daily-siding-vehicle-dispatches --from=2026-04-01 --to=2026-05-01
 *   php artisan rollup:backfill-daily-siding-vehicle-dispatches --from=2026-05-01 --to=2026-05-01 --siding_id=3
 *   php artisan rollup:backfill-daily-siding-vehicle-dispatches --dry-run
 */
final class BackfillDailySidingVehicleDispatchRollupsCommand extends Command
{
    protected $signature = 'rollup:backfill-daily-siding-vehicle-dispatches
                            {--from= : Start calendar date (Y-m-d); defaults to current FY start}
                            {--to= : End calendar date (Y-m-d); defaults to today}
                            {--siding_id= : Limit rebuild to one siding_id}
                            {--skip-fy-validation : Allow --from/--to outside current FY (operator escape hatch)}
                            {--dry-run : Show aggregated rows that would be inserted; do not delete or write}';

    protected $description = 'Rebuild daily siding_vehicle_dispatch aggregates into daily_siding_vehicle_dispatch_rollups';

    public function handle(RecalculateDailySidingVehicleDispatchRollups $rollup): int
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
                (string) $r->issued_on_date,
                (string) $r->siding_id,
                (string) $r->shift_number,
                (string) $r->dispatches_count,
                (string) $r->qty_mineral_mt,
            ])->all();

            $renderStart = microtime(true);
            $this->table(
                ['issued_on_date', 'siding_id', 'shift_number', 'dispatches_count', 'qty_mineral_mt'],
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
