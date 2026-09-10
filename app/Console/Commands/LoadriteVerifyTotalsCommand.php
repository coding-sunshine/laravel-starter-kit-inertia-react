<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reconcile what the dashboard shows against what Loadrite actually emitted.
 *
 * Per rake (with at least one loadrite-sourced wagon_loading row in the
 * window), compare:
 *   - sum(wagon_loading.loaded_quantity_mt where weight_source='loadrite')
 *     — the value the UI surfaces
 *   - sum(loadrite_events.weight_mt where event_type='Short Total'
 *           and weight_mt >= MIN_VALID_SHORT_TOTAL_MT, de-duplicated)
 *     — Loadrite's source of truth
 *
 * Mismatch &gt; --tolerance MT is reported. Exit code is non-zero when any
 * mismatch is found so a scheduled invocation can alert on drift.
 */
final class LoadriteVerifyTotalsCommand extends Command
{
    protected $signature = 'loadrite:verify-totals
                            {--since= : Limit to rakes loaded on/after this date (YYYY-MM-DD); default = last 14 days}
                            {--siding= : Limit to one siding id}
                            {--tolerance=0.5 : Acceptable difference in MT}
                            {--log : Also log mismatches via the logging stack (for scheduled runs)}';

    protected $description = 'Reconcile wagon_loading.loaded_quantity_mt against loadrite_events Short Total weights per rake.';

    public function handle(): int
    {
        $since = $this->option('since') ?: now()->subDays(14)->toDateString();
        $sidingFilter = $this->option('siding') ? (int) $this->option('siding') : null;
        $tolerance = (float) $this->option('tolerance');
        $logMismatches = (bool) $this->option('log');

        $minValid = \App\Actions\SyncLoadriteEvent::MIN_VALID_SHORT_TOTAL_MT;

        // Rake-level wagon_loading totals (loadrite-sourced only).
        $loadingTotals = DB::table('wagon_loading')
            ->join('rakes', 'rakes.id', '=', 'wagon_loading.rake_id')
            ->where('wagon_loading.weight_source', 'loadrite')
            ->where('rakes.loading_date', '>=', $since)
            ->when($sidingFilter, fn ($q) => $q->where('rakes.siding_id', $sidingFilter))
            ->groupBy('wagon_loading.rake_id', 'rakes.rake_number', 'rakes.siding_id', 'rakes.loading_date')
            ->selectRaw('
                wagon_loading.rake_id,
                rakes.rake_number,
                rakes.siding_id,
                rakes.loading_date,
                ROUND(SUM(wagon_loading.loaded_quantity_mt::numeric), 2) AS wl_sum,
                COUNT(*) AS wl_rows
            ')
            ->get()
            ->keyBy('rake_id');

        if ($loadingTotals->isEmpty()) {
            $this->info('No loadrite-sourced wagon_loading rows in the window — nothing to verify.');

            return self::SUCCESS;
        }

        // Rake-level Loadrite Short Total totals (de-duplicated by event_id,
        // filtered to valid weights only).
        $eventTotals = DB::table('loadrite_events')
            ->where('event_type', 'Short Total')
            ->whereRaw('weight_mt::numeric >= ?', [$minValid])
            ->whereIn('rake_id', $loadingTotals->keys()->all())
            ->groupBy('rake_id')
            ->selectRaw('rake_id, ROUND(SUM(weight_mt::numeric), 2) AS ev_sum, COUNT(*) AS ev_rows')
            ->get()
            ->keyBy('rake_id');

        $rows = [];
        $mismatches = 0;

        foreach ($loadingTotals as $rakeId => $lt) {
            $ev = $eventTotals[$rakeId] ?? null;
            $evSum = $ev?->ev_sum ?? 0;
            $evRows = $ev?->ev_rows ?? 0;
            $delta = round((float) $lt->wl_sum - (float) $evSum, 2);
            $ok = abs($delta) <= $tolerance;

            $rows[] = [
                $rakeId,
                $lt->siding_id,
                $lt->rake_number,
                $lt->loading_date,
                number_format((float) $lt->wl_sum, 2),
                number_format((float) $evSum, 2),
                $delta >= 0 ? '+'.number_format($delta, 2) : number_format($delta, 2),
                $lt->wl_rows.' / '.$evRows,
                $ok ? 'OK' : 'MISMATCH',
            ];

            if (! $ok) {
                $mismatches++;
                if ($logMismatches) {
                    Log::warning('loadrite verify mismatch', [
                        'rake_id' => (int) $rakeId,
                        'siding_id' => (int) $lt->siding_id,
                        'rake_number' => $lt->rake_number,
                        'loading_date' => $lt->loading_date,
                        'wl_sum_mt' => (float) $lt->wl_sum,
                        'ev_sum_mt' => (float) $evSum,
                        'delta_mt' => $delta,
                        'tolerance_mt' => $tolerance,
                    ]);
                }
            }
        }

        $this->table(
            ['rake_id', 'siding', 'rake_no', 'loading_date', 'wl_sum_mt', 'ev_sum_mt', 'delta', 'rows (wl/ev)', 'status'],
            $rows,
        );

        if ($mismatches > 0) {
            $this->warn("MISMATCHES: {$mismatches} (tolerance {$tolerance} MT)");

            return self::FAILURE;
        }

        $this->info('All rakes within tolerance.');

        return self::SUCCESS;
    }
}
