<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\SyncLoadriteEvent;
use App\Models\LoadriteEvent;
use App\Models\Wagon;
use App\Models\WagonLoading;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * One-time fix for events that were attributed to the wrong rake when
 * placement_time was null. Re-runs the virtual-anchor routing per event
 * and recomputes wagon_loading.loaded_quantity_mt for every affected rake/wagon.
 */
final class LoadriteReattributeEventsCommand extends Command
{
    protected $signature = 'loadrite:reattribute
                            {--siding= : Limit to one siding}
                            {--dry-run : Show what would change without writing}';

    protected $description = 'Re-route historical loadrite_events to the correct rake using virtual anchor and recompute wagon_loading totals.';

    public function handle(SyncLoadriteEvent $sync): int
    {
        $sidingFilter = $this->option('siding') ? (int) $this->option('siding') : null;
        $dryRun = (bool) $this->option('dry-run');

        $touched = [];
        $reattributed = 0;
        $unchanged = 0;
        $orphan = 0;

        $query = LoadriteEvent::query()
            ->when($sidingFilter, fn ($q) => $q->where('siding_id', $sidingFilter))
            ->orderBy('id');

        $total = $query->clone()->count();
        $this->info(sprintf('%sScanning %d event(s)%s...', $dryRun ? '[DRY RUN] ' : '', $total, $sidingFilter ? " for siding {$sidingFilter}" : ''));

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById(1000, function ($events) use ($sync, &$touched, &$reattributed, &$unchanged, &$orphan, $dryRun, $bar): void {
            foreach ($events as $event) {
                $eventTime = $event->event_time ? Carbon::parse($event->event_time) : null;
                $correctRakeId = $sync->resolveRakeIdForEvent((int) $event->siding_id, $eventTime);

                if ($correctRakeId === null) {
                    $orphan++;
                    $bar->advance();

                    continue;
                }

                $changes = [];
                if ($event->rake_id !== $correctRakeId) {
                    $changes['rake_id'] = $correctRakeId;

                    $wagonId = Wagon::query()
                        ->where('rake_id', $correctRakeId)
                        ->where('wagon_sequence', $event->wagon_sequence)
                        ->value('id');

                    if ($wagonId !== null) {
                        $changes['wagon_id'] = $wagonId;
                    }
                }

                if ($changes !== []) {
                    $reattributed++;
                    // Capture OLD rake_id BEFORE update so its wagon_loading
                    // total also gets recomputed (drops to 0 if it no longer
                    // owns any events for this wagon_sequence).
                    $oldRakeId = $event->rake_id;
                    if (! $dryRun) {
                        $event->update($changes);
                    }
                    if ($oldRakeId !== null) {
                        $touched[$oldRakeId.'|'.$event->wagon_sequence] = true;
                    }
                    $touched[$correctRakeId.'|'.$event->wagon_sequence] = true;
                } else {
                    $unchanged++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        $this->info(sprintf('Re-attribution: changed=%d unchanged=%d orphan=%d', $reattributed, $unchanged, $orphan));

        if ($dryRun) {
            $this->warn('Dry run — no writes performed; recompute skipped.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Recomputing wagon_loading totals for %d (rake, wagon_sequence) pair(s)...', count($touched)));

        $recomputed = 0;
        $bar = $this->output->createProgressBar(count($touched));
        $bar->start();

        foreach (array_keys($touched) as $key) {
            [$rakeId, $wagonSequence] = explode('|', $key);
            $rakeId = (int) $rakeId;
            $wagonSequence = (int) $wagonSequence;

            $cumulative = (float) LoadriteEvent::query()
                ->where('rake_id', $rakeId)
                ->where('wagon_sequence', $wagonSequence)
                ->whereIn('event_type', ['Add', 'Subtract'])
                ->selectRaw("COALESCE(SUM(CASE WHEN event_type = 'Add' THEN weight_mt ELSE -weight_mt END), 0) as total")
                ->value('total');

            $wagonLoading = WagonLoading::query()
                ->where('rake_id', $rakeId)
                ->whereHas('wagon', fn ($q) => $q->where('wagon_sequence', $wagonSequence))
                ->first();

            if (! $wagonLoading) {
                $bar->advance();

                continue;
            }

            $updates = ['loadrite_weight_mt' => $cumulative, 'loadrite_last_synced_at' => now()];

            if (! $wagonLoading->loadrite_override && $wagonLoading->weight_source !== 'weighbridge') {
                $updates['weight_source'] = 'loadrite';
                $updates['loaded_quantity_mt'] = $cumulative;
            }

            $wagonLoading->update($updates);
            $recomputed++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Recomputed: '.$recomputed);

        return self::SUCCESS;
    }
}
