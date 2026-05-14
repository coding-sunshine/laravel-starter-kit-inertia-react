<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\SyncLoadriteEvent;
use App\Models\LoadriteEvent;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rebuild wagon_loading.loadrite_weight_mt from Short Total events.
 *
 * Loadrite emits one "Short Total" event per wagon completion with that
 * wagon's final weight (~65 MT typical). Add/Subtract events are bucket
 * dumps and their Sequence field is a 1-10 BUCKET SLOT, not a wagon
 * identifier (it resets per wagon). Summing Add-Subtract by wagon_sequence
 * mixed many wagons into one slot — wrong.
 *
 * This command:
 *  1. Zeros every wagon_loading.loadrite_weight_mt sourced from Loadrite
 *     (skipping weighbridge and operator-override rows).
 *  2. Walks Short Total events in chronological order. For each event,
 *     resolves the active rake at that siding/time, then assigns the
 *     event's weight to the next unfilled wagon in that rake.
 *  3. wagon_loading.loaded_quantity_mt mirrors loadrite_weight_mt unless
 *     an override or weighbridge value already exists.
 */
final class LoadriteReattributeEventsCommand extends Command
{
    protected $signature = 'loadrite:reattribute
                            {--siding= : Limit to one siding}
                            {--dry-run : Show counts without writing}';

    protected $description = 'Rebuild wagon_loading.loadrite_weight_mt from Short Total events.';

    public function handle(SyncLoadriteEvent $sync): int
    {
        $sidingFilter = $this->option('siding') ? (int) $this->option('siding') : null;
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun) {
            $cleared = DB::table('wagon_loading')
                ->where('weight_source', 'loadrite')
                ->where('loadrite_override', false)
                ->when($sidingFilter, fn ($q) => $q->whereIn('rake_id', function ($sub) use ($sidingFilter) {
                    $sub->select('id')->from('rakes')->where('siding_id', $sidingFilter);
                }))
                ->update([
                    'loadrite_weight_mt' => 0,
                    'loaded_quantity_mt' => 0,
                    'weight_source' => null,
                    'updated_at' => now(),
                ]);
            $this->info("Cleared {$cleared} wagon_loading rows of Loadrite weight.");
        }

        $events = LoadriteEvent::query()
            ->where('event_type', 'Short Total')
            ->when($sidingFilter, fn ($q) => $q->where('siding_id', $sidingFilter))
            ->orderBy('event_time')
            ->orderBy('id');

        $total = $events->clone()->count();
        $this->info(sprintf('Processing %d Short Total events...', $total));
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $attributed = 0;
        $skipped = 0;

        $events->chunkById(500, function ($chunk) use ($sync, $dryRun, &$attributed, &$skipped, $bar): void {
            foreach ($chunk as $e) {
                $eventTime = $e->event_time ? Carbon::parse($e->event_time) : null;
                $rakeId = $sync->resolveRakeIdForEvent((int) $e->siding_id, $eventTime);

                if ($rakeId === null) {
                    $skipped++;
                    $bar->advance();

                    continue;
                }

                if (! $dryRun) {
                    $row = DB::table('wagon_loading')
                        ->join('wagons', 'wagons.id', '=', 'wagon_loading.wagon_id')
                        ->where('wagon_loading.rake_id', $rakeId)
                        ->where(function ($q) {
                            $q->whereNull('wagon_loading.loadrite_weight_mt')
                                ->orWhereRaw('wagon_loading.loadrite_weight_mt::numeric = 0');
                        })
                        ->where('wagon_loading.loadrite_override', false)
                        ->where(function ($q) {
                            $q->whereNull('wagon_loading.weight_source')
                                ->orWhere('wagon_loading.weight_source', '!=', 'weighbridge');
                        })
                        ->orderBy('wagons.wagon_sequence')
                        ->select('wagon_loading.id as wl_id', 'wagons.id as wagon_id', 'wagons.wagon_sequence', 'wagons.pcc_weight_mt', 'wagon_loading.cc_capacity_mt', 'wagon_loading.remarks')
                        ->first();

                    if ($row) {
                        $weight = (float) $e->weight_mt;
                        DB::table('wagon_loading')
                            ->where('id', $row->wl_id)
                            ->update([
                                'loadrite_weight_mt' => $weight,
                                'loaded_quantity_mt' => $weight,
                                'weight_source' => 'loadrite',
                                'loadrite_last_synced_at' => now(),
                                'remarks' => mb_trim(($row->remarks ? $row->remarks.' ' : '').'[loadrite:'.$e->event_id.']'),
                                'updated_at' => now(),
                            ]);

                        // Update the event row with rake_id / wagon_id for audit
                        DB::table('loadrite_events')->where('id', $e->id)->update([
                            'rake_id' => $rakeId,
                            'wagon_id' => $row->wagon_id,
                            'wagon_sequence' => $row->wagon_sequence,
                        ]);

                        $attributed++;
                    } else {
                        $skipped++;
                    }
                } else {
                    $attributed++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info(sprintf('Attributed=%d skipped=%d', $attributed, $skipped));

        return self::SUCCESS;
    }
}
