<?php

declare(strict_types=1);

namespace App\Services\ManagerBrief;

use App\Models\Rake;
use App\Models\Siding;
use Illuminate\Support\Facades\DB;

/**
 * Sums the projected rupee overload exposure across currently-loading rakes
 * at a given siding. Drives the "Live Rs-at-stake" ticker on the manager-brief page.
 *
 * Exclusion rules:
 *  - Ghost rakes: rakes with no wagon_loading rows AND no loadrite_events are skipped.
 *  - Weighbridge weight source: rows with weight_source = 'weighbridge' are excluded —
 *    the weighbridge is the operator-blessed final measurement, not an overload signal.
 *  - Only non-cancelled, non-dispatched, non-completed rakes at the siding are considered.
 */
final readonly class LiveExposureCalculator
{
    /**
     * Default rupee penalty per MT of overload when no config key is present.
     */
    private const float DEFAULT_RS_PER_MT_OVERLOAD = 1000.0;

    /**
     * Sum projected rupee overload across rakes currently in state
     * loading|placed at the given siding.
     *
     * @return array{total_rs: float, breakdown: list<array{rake_id: int, rake_number: string, overload_mt: float, rs: float}>}
     */
    public function handle(int $sidingId): array
    {
        $rsPerMt = (float) config('penalties.overload.rs_per_mt', self::DEFAULT_RS_PER_MT_OVERLOAD);

        $siding = Siding::query()->find($sidingId);

        if ($siding === null) {
            return ['total_rs' => 0.0, 'breakdown' => []];
        }

        // Query all active rakes at this siding (loading or placed states).
        // We do not use activeRakeForSiding (private) — instead we replicate the
        // "not cancelled/dispatched/completed" filter that LiveMonitor uses,
        // restricted to explicit loading/placed states per the spec.
        $activeRakes = Rake::query()
            ->where('siding_id', $sidingId)
            ->whereIn('state', ['loading', 'placed'])
            ->get();

        if ($activeRakes->isEmpty()) {
            return ['total_rs' => 0.0, 'breakdown' => []];
        }

        $breakdown = [];
        $totalRs = 0.0;

        foreach ($activeRakes as $rake) {
            // Ghost rake guard: skip rakes with no wagon_loading rows AND no
            // loadrite_events. This prevents phantom exposure from un-loaded rakes.
            $hasLoadingRows = DB::table('wagon_loading')
                ->where('rake_id', $rake->id)
                ->exists();

            $hasLoadriteEvents = DB::table('loadrite_events')
                ->where('rake_id', $rake->id)
                ->exists();

            if (! $hasLoadingRows && ! $hasLoadriteEvents) {
                continue;
            }

            // Compute overload for this rake:
            // - Exclude weighbridge weight source (operator-blessed final weight)
            // - Overload per wagon = MAX(0, loaded_quantity_mt - pcc_weight_mt)
            $overloadMt = DB::table('wagon_loading')
                ->join('wagons', 'wagons.id', '=', 'wagon_loading.wagon_id')
                ->where('wagon_loading.rake_id', $rake->id)
                ->where(function ($query): void {
                    $query->whereNull('wagon_loading.weight_source')
                        ->orWhere('wagon_loading.weight_source', '!=', 'weighbridge');
                })
                ->whereNotNull('wagon_loading.loaded_quantity_mt')
                ->whereNotNull('wagons.pcc_weight_mt')
                ->select(
                    DB::raw('SUM(CASE WHEN wagon_loading.loaded_quantity_mt > wagons.pcc_weight_mt THEN wagon_loading.loaded_quantity_mt - wagons.pcc_weight_mt ELSE 0 END) as overload_sum')
                )
                ->value('overload_sum');

            $overloadMt = (float) ($overloadMt ?? 0.0);

            if ($overloadMt <= 0.0) {
                continue;
            }

            $rs = round($overloadMt * $rsPerMt, 2);
            $totalRs += $rs;

            $breakdown[] = [
                'rake_id' => (int) $rake->id,
                'rake_number' => (string) $rake->rake_number,
                'overload_mt' => round($overloadMt, 2),
                'rs' => $rs,
            ];
        }

        return [
            'total_rs' => round($totalRs, 2),
            'breakdown' => $breakdown,
        ];
    }
}
