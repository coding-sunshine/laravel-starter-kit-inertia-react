<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Stamp loading_end_time on rakes that look completed but were never closed
 * by an operator. Without this, fresh Loadrite events keep gluing to a stale
 * "open" rake forever (SyncLoadriteEvent::resolveRakeIdForEvent only excludes
 * rakes whose loading_end_time is non-null and in the past).
 *
 * "Stale" = has wagon_loading rows whose latest updated_at is more than
 * --inactive-hours ago. loading_end_time is set to that last activity time so
 * the rake's window is sealed precisely where activity stopped.
 *
 * Safe to run repeatedly. Designed to be scheduled every 30 minutes.
 */
final class LoadriteCloseStaleRakesCommand extends Command
{
    protected $signature = 'loadrite:close-stale-rakes
                            {--inactive-hours=6 : Hours of no wagon_loading activity before a rake is considered stale}
                            {--siding= : Limit to a single siding id}
                            {--dry-run : Show the plan without writing}';

    protected $description = 'Stamp loading_end_time on pending rakes that have been silent for N hours so new Loadrite events route to a fresh rake.';

    public function handle(): int
    {
        $inactiveHours = (int) $this->option('inactive-hours');
        $sidingFilter = $this->option('siding') ? (int) $this->option('siding') : null;
        $dryRun = (bool) $this->option('dry-run');

        $now = CarbonImmutable::now();
        $cutoff = $now->subHours($inactiveHours);

        // Rakes that are still considered open (no loading_end_time, not in a
        // terminal state) but whose latest wagon_loading activity ended more
        // than $inactiveHours ago.
        $candidates = DB::table('rakes')
            ->leftJoin('wagon_loading', 'wagon_loading.rake_id', '=', 'rakes.id')
            ->whereNull('rakes.loading_end_time')
            ->where(function ($q): void {
                $q->whereNull('rakes.state')
                    ->orWhereNotIn('rakes.state', ['cancelled', 'dispatched', 'completed']);
            })
            ->when($sidingFilter, fn ($q) => $q->where('rakes.siding_id', $sidingFilter))
            ->groupBy('rakes.id', 'rakes.siding_id', 'rakes.rake_number', 'rakes.state')
            ->havingRaw('MAX(wagon_loading.updated_at) IS NOT NULL AND MAX(wagon_loading.updated_at) < ?', [$cutoff])
            ->select(
                'rakes.id',
                'rakes.siding_id',
                'rakes.rake_number',
                'rakes.state',
                DB::raw('MAX(wagon_loading.updated_at) AS last_activity'),
            )
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No stale rakes to close.');

            return self::SUCCESS;
        }

        $this->table(
            ['rake_id', 'siding_id', 'rake_number', 'state', 'last_activity', 'closing_at'],
            $candidates->map(fn ($r) => [
                $r->id,
                $r->siding_id,
                $r->rake_number,
                $r->state,
                $r->last_activity,
                $r->last_activity,
            ])->all(),
        );

        if ($dryRun) {
            $this->warn(sprintf('Dry run — %d rakes would be closed.', $candidates->count()));

            return self::SUCCESS;
        }

        $written = 0;
        DB::transaction(function () use ($candidates, &$written): void {
            foreach ($candidates as $r) {
                $written += DB::table('rakes')
                    ->where('id', $r->id)
                    ->whereNull('loading_end_time')
                    ->update([
                        'loading_end_time' => $r->last_activity,
                        'updated_at' => now(),
                    ]);
            }
        });

        $this->info("Closed {$written} stale rake(s).");

        return self::SUCCESS;
    }
}
