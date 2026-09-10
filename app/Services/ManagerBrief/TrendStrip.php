<?php

declare(strict_types=1);

namespace App\Services\ManagerBrief;

use App\Models\SidingPerformance;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Returns three week-over-week deltas and sparklines for the manager-brief trend strip.
 *
 * Metrics
 * -------
 * - penalty_rs       → SUM(total_penalty_amount) over the 7-day window
 * - throughput_mt    → SUM(closing_stock_mt) over the 7-day window
 *                      NOTE: closing_stock_mt is the only MT figure available in
 *                      siding_performance; a dedicated loaded_mt / throughput_mt column
 *                      does not yet exist. Once that column is added, swap the field here.
 * - on_time_dispatch_pct → always 0 / zeros; no on_time_pct column exists in
 *                          siding_performance at time of writing. Add the column and
 *                          update this service when the data becomes available.
 *
 * Cache
 * -----
 * Results are cached per siding for 6 hours (21 600 seconds) under the key
 * "manager-brief:trend-strip:{sidingId}:v1".
 */
final readonly class TrendStrip
{
    /**
     * Cache TTL in seconds (6 hours).
     */
    private const int CACHE_TTL = 21600;

    /**
     * Number of days in each bucket (current week + prior week).
     */
    private const int BUCKET_DAYS = 7;

    /**
     * @return array{
     *     penalty_rs: array{current: float, prior: float, delta_pct: float, spark: list<float>},
     *     throughput_mt: array{current: float, prior: float, delta_pct: float, spark: list<float>},
     *     on_time_dispatch_pct: array{current: float, prior: float, delta_pct: float, spark: list<float>},
     * }
     */
    public function handle(int $sidingId): array
    {
        return Cache::remember(
            "manager-brief:trend-strip:{$sidingId}:v1",
            self::CACHE_TTL,
            fn (): array => $this->compute($sidingId),
        );
    }

    /**
     * Core computation — separated so the cache closure is easy to test.
     *
     * @return array{
     *     penalty_rs: array{current: float, prior: float, delta_pct: float, spark: list<float>},
     *     throughput_mt: array{current: float, prior: float, delta_pct: float, spark: list<float>},
     *     on_time_dispatch_pct: array{current: float, prior: float, delta_pct: float, spark: list<float>},
     * }
     */
    private function compute(int $sidingId): array
    {
        $today = CarbonImmutable::today();

        // Current week: [today - 6, today] (7 days inclusive)
        $currentStart = $today->subDays(self::BUCKET_DAYS - 1)->toDateString();
        $currentEnd = $today->toDateString();

        // Prior week: [today - 13, today - 7] (7 days inclusive)
        $priorStart = $today->subDays((self::BUCKET_DAYS * 2) - 1)->toDateString();
        $priorEnd = $today->subDays(self::BUCKET_DAYS)->toDateString();

        // Fetch 14 days of data in a single query.
        // Use whereDate() to ensure correct behaviour across drivers (SQLite stores dates as
        // "Y-m-d H:i:s" by default; a plain whereBetween on the raw column value would
        // exclude today when the upper bound is a date-only string like "2026-05-22" because
        // "2026-05-22 00:00:00" > "2026-05-22" in lexicographic order).
        $rows = SidingPerformance::query()
            ->where('siding_id', $sidingId)
            ->whereDate('as_of_date', '>=', $priorStart)
            ->whereDate('as_of_date', '<=', $currentEnd)
            ->get(['as_of_date', 'total_penalty_amount', 'closing_stock_mt']);

        // Partition into two 7-day buckets keyed by date string.
        /** @var array<string, array{penalty: float, throughput: float}> $currentByDate */
        $currentByDate = [];
        /** @var array<string, array{penalty: float, throughput: float}> $priorByDate */
        $priorByDate = [];

        foreach ($rows as $row) {
            $dateStr = $row->as_of_date->toDateString();
            $entry = [
                'penalty' => (float) $row->total_penalty_amount,
                'throughput' => (float) $row->closing_stock_mt,
            ];

            if ($dateStr >= $currentStart && $dateStr <= $currentEnd) {
                $currentByDate[$dateStr] = $entry;
            } elseif ($dateStr >= $priorStart && $dateStr <= $priorEnd) {
                $priorByDate[$dateStr] = $entry;
            }
        }

        // Build sparkline (current week, oldest → newest, 7 values, pad missing with 0.0).
        $penaltySpark = [];
        $throughputSpark = [];

        for ($i = self::BUCKET_DAYS - 1; $i >= 0; $i--) {
            $dateStr = $today->subDays($i)->toDateString();
            $entry = $currentByDate[$dateStr] ?? null;
            $penaltySpark[] = $entry !== null ? $entry['penalty'] : 0.0;
            $throughputSpark[] = $entry !== null ? $entry['throughput'] : 0.0;
        }

        // Aggregate bucket totals.
        $penaltyCurrent = (float) array_sum(array_column($currentByDate, 'penalty'));
        $penaltyPrior = (float) array_sum(array_column($priorByDate, 'penalty'));
        $throughputCurrent = (float) array_sum(array_column($currentByDate, 'throughput'));
        $throughputPrior = (float) array_sum(array_column($priorByDate, 'throughput'));

        return [
            'penalty_rs' => [
                'current' => $penaltyCurrent,
                'prior' => $penaltyPrior,
                'delta_pct' => $this->deltaPct($penaltyCurrent, $penaltyPrior),
                'spark' => $penaltySpark,
            ],
            'throughput_mt' => [
                'current' => $throughputCurrent,
                'prior' => $throughputPrior,
                'delta_pct' => $this->deltaPct($throughputCurrent, $throughputPrior),
                'spark' => $throughputSpark,
            ],
            // TODO: on_time_dispatch_pct always returns zeros because the siding_performance
            // table has no on_time_pct column. When that column is added, replace this stub
            // with a real query (sum/avg over the 7-day window) and update the tests.
            'on_time_dispatch_pct' => [
                'current' => 0.0,
                'prior' => 0.0,
                'delta_pct' => 0.0,
                'spark' => array_fill(0, self::BUCKET_DAYS, 0.0),
            ],
        ];
    }

    /**
     * Compute week-over-week delta percentage.
     *
     * - prior > 0  : round(((current - prior) / prior) × 100, 1)
     * - prior == 0 && current > 0 : 100.0  (new activity vs zero baseline)
     * - both == 0  : 0.0
     */
    private function deltaPct(float $current, float $prior): float
    {
        if ($prior > 0.0) {
            return round((($current - $prior) / $prior) * 100.0, 1);
        }

        return $current > 0.0 ? 100.0 : 0.0;
    }
}
