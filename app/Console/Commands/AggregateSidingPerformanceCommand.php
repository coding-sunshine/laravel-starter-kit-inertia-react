<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\AggregateSidingPerformance;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Scheduled command that aggregates daily siding performance metrics.
 *
 * Usage:
 *   php artisan rrmcs:aggregate-performance              # Yesterday (default)
 *   php artisan rrmcs:aggregate-performance --from=2026-01-01 --to=2026-01-31  # Backfill range
 */
final class AggregateSidingPerformanceCommand extends Command
{
    protected $signature = 'rrmcs:aggregate-performance
                            {--from= : Start date (Y-m-d) for backfill}
                            {--to= : End date (Y-m-d) for backfill}';

    protected $description = 'Aggregate daily siding performance metrics (penalties, demurrage, overloads, stock)';

    public function handle(AggregateSidingPerformance $action): int
    {
        $from = $this->option('from')
            ? CarbonImmutable::parse($this->option('from'))
            : null;

        $to = $this->option('to')
            ? CarbonImmutable::parse($this->option('to'))
            : null;

        // Backfill mode: iterate date range
        if ($from) {
            $to ??= CarbonImmutable::yesterday();
            $totalRecords = 0;
            $current = $from;

            while ($current->lte($to)) {
                $count = $action->handle($current);
                $totalRecords += $count;
                $current = $current->addDay();
            }

            $days = $from->diffInDays($to) + 1;
            $this->info("Backfilled {$totalRecords} records across {$days} days ({$from->toDateString()} to {$to->toDateString()}).");

            return self::SUCCESS;
        }

        // Default: aggregate yesterday
        $count = $action->handle();
        $this->info("Aggregated performance for {$count} sidings (yesterday).");

        return self::SUCCESS;
    }
}
