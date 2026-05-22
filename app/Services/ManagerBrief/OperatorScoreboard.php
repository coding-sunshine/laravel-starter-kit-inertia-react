<?php

declare(strict_types=1);

namespace App\Services\ManagerBrief;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Computes the top-5 and bottom-5 loader operators by accuracy percentage over a
 * configurable day window. Drives the "Operator Scoreboard" widget on the manager-brief page.
 *
 * Accuracy: 100 × (wagons in 95–100 % PCC band) / (total wagons loaded by operator in window).
 * Noise filter: operators with fewer than 10 wagons in the window are excluded.
 * Rs caused: sum of (loaded_quantity_mt − pcc_weight_mt) × 1000 for overloaded wagons only.
 */
final readonly class OperatorScoreboard
{
    /**
     * Minimum number of wagons an operator must have loaded in the window to be eligible.
     */
    private const int MINIMUM_WAGONS = 10;

    /**
     * Inclusive lower bound of the "good" accuracy band as a fraction of PCC (95 %).
     */
    private const float BAND_LOW_FRACTION = 0.95;

    /**
     * Default rupee penalty per MT of overload when no config key is present.
     */
    private const float DEFAULT_RS_PER_MT = 1000.0;

    /**
     * @return array{top: list<array{name: string, wagons: int, accuracy_pct: float, rs_caused: float}>, bottom: list<array{name: string, wagons: int, accuracy_pct: float, rs_caused: float}>}
     */
    public function handle(int $sidingId, int $windowDays = 7): array
    {
        $rsPerMt = (float) config('penalties.overload.rs_per_mt', self::DEFAULT_RS_PER_MT);

        $from = Carbon::now()->subDays($windowDays)->toDateString();
        $to = Carbon::now()->toDateString();

        // Effective CC: use the wagon_loading override if set, otherwise fall back to the
        // wagon's PCC weight. This mirrors the pattern used in LoaderOverloadMetricsService.
        $ccEff = 'COALESCE(wl.cc_capacity_mt, w.pcc_weight_mt)';

        // In-band: loaded is between 95 % and 100 % of effective CC (inclusive on both ends).
        // Wagons with no loaded weight or no PCC data are excluded from both numerator and
        // denominator by the WHERE clause below.
        $inBandCase = 'SUM(CASE WHEN wl.loaded_quantity_mt IS NOT NULL'
            ." AND {$ccEff} IS NOT NULL AND {$ccEff} > 0"
            ." AND wl.loaded_quantity_mt >= {$ccEff} * ".self::BAND_LOW_FRACTION
            ." AND wl.loaded_quantity_mt <= {$ccEff}"
            .' THEN 1 ELSE 0 END) as in_band_count';

        // Overload excess in MT per wagon — used to compute Rs caused.
        // Only wagons actually over the effective CC contribute.
        $overloadMtCase = 'SUM(CASE WHEN wl.loaded_quantity_mt IS NOT NULL'
            ." AND {$ccEff} IS NOT NULL AND {$ccEff} > 0"
            ." AND wl.loaded_quantity_mt > {$ccEff}"
            ." THEN wl.loaded_quantity_mt - {$ccEff}"
            .' ELSE 0 END) as overload_mt_sum';

        $driver = DB::getDriverName();

        // Use portable date range: DATE(column) for SQLite/MySQL, column::date for PostgreSQL.
        if ($driver === 'pgsql') {
            $dateBetween = '(r.loading_date)::date BETWEEN ? AND ?';
        } else {
            $dateBetween = 'DATE(r.loading_date) BETWEEN ? AND ?';
        }

        $rows = DB::table('wagon_loading as wl')
            ->join('rakes as r', 'r.id', '=', 'wl.rake_id')
            ->join('wagons as w', 'w.id', '=', 'wl.wagon_id')
            ->where('r.siding_id', $sidingId)
            ->whereNotNull('r.loading_date')
            ->whereRaw($dateBetween, [$from, $to])
            ->whereNotNull('wl.loader_operator_name')
            ->where('wl.loader_operator_name', '!=', '')
            ->whereNotNull('wl.loaded_quantity_mt')
            ->selectRaw(
                'TRIM(wl.loader_operator_name) as operator_name,'
                .' COUNT(*) as total_wagons,'
                ." {$inBandCase},"
                ." {$overloadMtCase}",
            )
            ->groupByRaw('TRIM(wl.loader_operator_name)')
            ->get();

        // Filter to eligible operators (noise floor) and shape into our wire format.
        $eligible = $rows
            ->filter(fn (object $row): bool => (int) $row->total_wagons >= self::MINIMUM_WAGONS)
            ->map(function (object $row) use ($rsPerMt): array {
                $total = (int) $row->total_wagons;
                $inBand = (int) $row->in_band_count;
                $overloadMt = (float) $row->overload_mt_sum;

                return [
                    'name' => (string) $row->operator_name,
                    'wagons' => $total,
                    'accuracy_pct' => $total > 0 ? round(100.0 * $inBand / $total, 2) : 0.0,
                    'rs_caused' => round($overloadMt * $rsPerMt, 2),
                ];
            })
            ->values();

        $n = $eligible->count();

        if ($n === 0) {
            return ['top' => [], 'bottom' => []];
        }

        // Sort descending for top, ascending for bottom.
        $sorted = $eligible->sortByDesc('accuracy_pct')->values();

        // When fewer than 10 eligible operators exist, return up to floor(N/2) per side.
        $limit = $n >= 10 ? 5 : (int) floor($n / 2);

        if ($limit === 0) {
            return ['top' => [], 'bottom' => []];
        }

        $top = $sorted->take($limit)->values()->all();
        $bottom = $sorted->reverse()->values()->take($limit)->sortBy('accuracy_pct')->values()->all();

        return ['top' => $top, 'bottom' => $bottom];
    }
}
