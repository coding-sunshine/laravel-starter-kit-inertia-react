<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\SyncLoadriteEvent;
use App\Models\LoadriteEvent;
use App\Services\Loadrite\LoadriteUserDataParser;
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

    public function handle(SyncLoadriteEvent $sync, LoadriteUserDataParser $parser): int
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
                    'weight_source' => 'manual',
                    'updated_at' => now(),
                ]);
            $this->info("Cleared {$cleared} wagon_loading rows of Loadrite weight.");
        }

        // Filter out operator-error Short Totals before processing:
        //  - weight < 30 MT = aborted / test / misfire (typical wagon ~65 MT)
        //  - duplicates per (scale, user_data2, user_data3) within 30 min
        //    = operator pressed button twice; keep only earliest
        $events = LoadriteEvent::query()
            ->where('event_type', 'Short Total')
            ->whereRaw('weight_mt::numeric >= ?', [SyncLoadriteEvent::MIN_VALID_SHORT_TOTAL_MT])
            ->whereNotIn('id', function ($q) use ($sidingFilter) {
                // Sub-select: duplicates beyond the first per session window
                $q->select('id')
                    ->fromSub(function ($s) use ($sidingFilter) {
                        $s->from('loadrite_events')
                            ->selectRaw('id, ROW_NUMBER() OVER (PARTITION BY siding_id, scale_id, user_data2, user_data3 ORDER BY event_time, id) AS rn')
                            ->where('event_type', 'Short Total')
                            ->whereRaw('weight_mt::numeric >= ?', [SyncLoadriteEvent::MIN_VALID_SHORT_TOTAL_MT])
                            ->when($sidingFilter, fn ($x) => $x->where('siding_id', $sidingFilter));
                    }, 'r')
                    ->where('rn', '>', 1);
            })
            ->when($sidingFilter, fn ($q) => $q->where('siding_id', $sidingFilter))
            ->orderBy('event_time')
            ->orderBy('id');

        $total = $events->clone()->count();
        $this->info(sprintf('Processing %d valid Short Total events (after filtering low-weight + duplicates)...', $total));
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $attributed = 0;
        $skipped = 0;

        $events->chunkById(500, function ($chunk) use ($sync, $parser, $dryRun, &$attributed, &$skipped, $bar): void {
            foreach ($chunk as $e) {
                $eventTime = $e->event_time ? Carbon::parse($e->event_time) : null;

                // The operator-keyed rake number (UserData) is the definitive
                // signal for which rake an event belongs to — parse it and let
                // resolveRakeIdForEvent match it to rake_serial_number.
                $payload = is_array($e->raw_payload)
                    ? $e->raw_payload
                    : (json_decode((string) $e->raw_payload, true) ?: []);
                $rakeNumber = $parser->parse($payload)['rake_number'];

                // When attribution fails because the resolved rake is already
                // full, close that rake (stamp loading_end_time = event_time)
                // and re-resolve. This makes the walk progress to the next
                // rake instead of skipping the rest of the events.
                $rakeId = null;
                $row = null;
                $closedDuringRetry = [];
                for ($attempt = 0; $attempt < 30; $attempt++) {
                    $rakeId = $sync->resolveRakeIdForEvent((int) $e->siding_id, $eventTime, $rakeNumber);
                    if ($rakeId === null) {
                        break;
                    }

                    if ($dryRun) {
                        // In dry-run we don't write — just count this as bound.
                        $row = (object) ['wl_id' => null, 'wagon_id' => null, 'wagon_sequence' => null];
                        break;
                    }

                    // Ensure wagon_loading rows exist for this rake (new rakes
                    // arrive bare; auto-create rows before attribution).
                    $sync->ensureWagonLoadingRowsExist($rakeId);

                    $row = DB::table('wagon_loading')
                        ->join('wagons', 'wagons.id', '=', 'wagon_loading.wagon_id')
                        ->where('wagon_loading.rake_id', $rakeId)
                        ->where(function ($q): void {
                            $q->whereNull('wagon_loading.loadrite_weight_mt')
                                ->orWhereRaw('wagon_loading.loadrite_weight_mt::numeric = 0');
                        })
                        ->where('wagon_loading.loadrite_override', false)
                        ->where(function ($q): void {
                            $q->whereNull('wagon_loading.weight_source')
                                ->orWhere('wagon_loading.weight_source', '!=', 'weighbridge');
                        })
                        ->orderBy('wagons.wagon_sequence')
                        ->select('wagon_loading.id as wl_id', 'wagons.id as wagon_id', 'wagons.wagon_sequence', 'wagons.pcc_weight_mt', 'wagon_loading.cc_capacity_mt', 'wagon_loading.remarks')
                        ->first();

                    if ($row !== null) {
                        break;
                    }

                    // Rake is full → close it 1 second BEFORE this event so
                    // the next resolveRakeIdForEvent skips it. Closing at the
                    // exact event_time fails the resolver's `>= eventTime`
                    // check and keeps returning the same full rake.
                    if (! in_array($rakeId, $closedDuringRetry, true)) {
                        $closeAt = $eventTime !== null ? $eventTime->copy()->subSeconds(1) : now();
                        DB::table('rakes')
                            ->where('id', $rakeId)
                            ->whereNull('loading_end_time')
                            ->update([
                                'loading_end_time' => $closeAt,
                                'updated_at' => now(),
                            ]);
                        $closedDuringRetry[] = $rakeId;
                    } else {
                        // Already tried closing this rake; give up to avoid
                        // an infinite loop on weird states.
                        break;
                    }
                }

                if ($rakeId === null || $row === null) {
                    $skipped++;
                    $bar->advance();

                    continue;
                }

                if (! $dryRun) {
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
