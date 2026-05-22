<?php

declare(strict_types=1);

namespace App\Actions\ManagerBrief;

use App\DataTransferObjects\ManagerBrief\Signal;
use App\Models\LoadingOverride;
use App\Models\LoadriteEvent;
use App\Models\Rake;
use App\Models\SidingPerformance;
use App\Services\ForceMajeure\Contracts\DowntimePenaltyMatcherContract;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Collects all 7 operational signal types for a given siding and returns
 * them as an unsorted list of Signal DTOs ready for downstream ranking.
 *
 * Signal types emitted:
 *   1. overload_exposure   – wagons currently being loaded over PCC capacity
 *   2. operator_anomaly    – loader whose this-week overload rate ≥ 2× their 30-day baseline
 *   3. force_majeure       – DEM reconciliation with a qualifying downtime overlap
 *   4. scale_silence       – Loadrite scale silent for > 2 h while an active rake is present
 *   5. pending_override    – oldest unreviewed loading override has been waiting > 2 h
 *   6. underloading_trend  – closing_stock_mt week-over-week drop > 10 %
 *   7. demurrage_risk      – rake within 3 h of the 12-hour SLA from placement_time
 */
final readonly class CollectSignals
{
    /**
     * Demurrage SLA from placement_time in hours.
     */
    private const int SLA_HOURS = 12;

    /**
     * How many hours ahead of the SLA deadline to start emitting a demurrage_risk signal.
     */
    private const int DEMURRAGE_WARN_HOURS = 3;

    public function __construct(
        private DowntimePenaltyMatcherContract $matcher,
    ) {}

    /**
     * Collect all signals for the given siding.
     *
     * @return list<Signal>
     */
    public function handle(int $sidingId): array
    {
        return array_merge(
            $this->collectOverloadExposure($sidingId),
            $this->collectOperatorAnomaly($sidingId),
            $this->collectForceMajeure($sidingId),
            $this->collectScaleSilence($sidingId),
            $this->collectPendingOverride($sidingId),
            $this->collectUnderloadingTrend($sidingId),
            $this->collectDemurrageRisk($sidingId),
        );
    }

    /**
     * Signal 1: overload_exposure
     *
     * One signal per currently-loading rake at the siding where computed overload
     * (loaded_quantity_mt − pcc_weight_mt, excluding weighbridge source) is positive.
     * Severity depends on the magnitude of the overload:
     *   ≥ 5 MT → critical, ≥ 2 MT → high, otherwise → medium.
     *
     * @return list<Signal>
     */
    private function collectOverloadExposure(int $sidingId): array
    {
        $rsPerMt = (float) config('penalties.overload.rs_per_mt', 1000);

        $activeRakes = Rake::query()
            ->where('siding_id', $sidingId)
            ->whereIn('state', ['loading', 'placed'])
            ->get(['id', 'rake_number']);

        if ($activeRakes->isEmpty()) {
            return [];
        }

        $signals = [];

        foreach ($activeRakes as $rake) {
            $rakeId = (int) $rake->id;

            // Ghost rake guard — no loading rows AND no loadrite events → skip.
            $hasActivity = DB::table('wagon_loading')->where('rake_id', $rakeId)->exists()
                || DB::table('loadrite_events')->where('rake_id', $rakeId)->exists();

            if (! $hasActivity) {
                continue;
            }

            // Compute net overload (exclude weighbridge source).
            $overloadMt = (float) (DB::table('wagon_loading')
                ->join('wagons', 'wagons.id', '=', 'wagon_loading.wagon_id')
                ->where('wagon_loading.rake_id', $rakeId)
                ->where(function ($q): void {
                    $q->whereNull('wagon_loading.weight_source')
                        ->orWhere('wagon_loading.weight_source', '!=', 'weighbridge');
                })
                ->whereNotNull('wagon_loading.loaded_quantity_mt')
                ->whereNotNull('wagons.pcc_weight_mt')
                ->selectRaw(
                    'SUM(CASE WHEN wagon_loading.loaded_quantity_mt > wagons.pcc_weight_mt'
                    .' THEN wagon_loading.loaded_quantity_mt - wagons.pcc_weight_mt ELSE 0 END)'
                    .' AS overload_sum'
                )
                ->value('overload_sum') ?? 0.0);

            if ($overloadMt <= 0.0) {
                continue;
            }

            // Recency: minutes since the most-recent loadrite_event for this rake.
            $latestEvent = LoadriteEvent::query()
                ->where('rake_id', $rakeId)
                ->max('event_time');

            $recencyMinutes = $latestEvent !== null
                ? (int) round(now()->diffInMinutes($latestEvent, true))
                : 0;

            $severity = match (true) {
                $overloadMt >= 5.0 => 'critical',
                $overloadMt >= 2.0 => 'high',
                default => 'medium',
            };

            $signals[] = new Signal(
                type: 'overload_exposure',
                severity: $severity,
                rsAtStake: round($overloadMt * $rsPerMt, 2),
                recencyMinutes: $recencyMinutes,
                actionability: 0.9,
                payload: [
                    'rake_id' => $rakeId,
                    'rake_number' => (string) $rake->rake_number,
                    'overload_mt' => round($overloadMt, 3),
                ],
            );
        }

        return $signals;
    }

    /**
     * Signal 2: operator_anomaly
     *
     * For each operator who has loaded ≥ 10 wagons at this siding in the current
     * ISO week, compare their this-week overload rate (overloaded wagons / total wagons)
     * to their trailing-30-day baseline rate. Emit when this-week-rate ≥ 2× baseline.
     * Severity: ≥ 3× → high, otherwise → medium.
     *
     * @return list<Signal>
     */
    private function collectOperatorAnomaly(int $sidingId): array
    {
        $rsPerMt = (float) config('penalties.overload.rs_per_mt', 1000);

        $now = CarbonImmutable::now();
        $weekStart = $now->startOfWeek()->toDateString();
        $weekEnd = $now->endOfWeek()->toDateString();
        $baselineStart = $now->subDays(30)->toDateString();

        $driver = DB::getDriverName();
        $dateCast = $driver === 'pgsql' ? '::date' : '';

        // This-week stats per operator.
        $thisWeek = DB::table('wagon_loading as wl')
            ->join('rakes as r', 'r.id', '=', 'wl.rake_id')
            ->join('wagons as w', 'w.id', '=', 'wl.wagon_id')
            ->where('r.siding_id', $sidingId)
            ->whereNotNull('wl.loader_operator_name')
            ->where('wl.loader_operator_name', '!=', '')
            ->whereNotNull('wl.loaded_quantity_mt')
            ->whereNotNull('r.loading_date')
            ->whereBetween(DB::raw("DATE(r.loading_date{$dateCast})"), [$weekStart, $weekEnd])
            ->selectRaw(
                'TRIM(wl.loader_operator_name) as operator_name,'
                .' COUNT(*) as total_wagons,'
                .' SUM(CASE WHEN wl.loaded_quantity_mt > COALESCE(wl.cc_capacity_mt, w.pcc_weight_mt)'
                .'     AND COALESCE(wl.cc_capacity_mt, w.pcc_weight_mt) IS NOT NULL'
                .'     AND COALESCE(wl.cc_capacity_mt, w.pcc_weight_mt) > 0'
                .'     THEN 1 ELSE 0 END) as overloaded_wagons,'
                .' SUM(CASE WHEN wl.loaded_quantity_mt > COALESCE(wl.cc_capacity_mt, w.pcc_weight_mt)'
                .'     AND COALESCE(wl.cc_capacity_mt, w.pcc_weight_mt) IS NOT NULL'
                .'     AND COALESCE(wl.cc_capacity_mt, w.pcc_weight_mt) > 0'
                .'     THEN wl.loaded_quantity_mt - COALESCE(wl.cc_capacity_mt, w.pcc_weight_mt)'
                .'     ELSE 0 END) as overload_mt_sum'
            )
            ->groupByRaw('TRIM(wl.loader_operator_name)')
            ->get();

        if ($thisWeek->isEmpty()) {
            return [];
        }

        // Trailing-30-day baseline per operator.
        $baseline = DB::table('wagon_loading as wl')
            ->join('rakes as r', 'r.id', '=', 'wl.rake_id')
            ->join('wagons as w', 'w.id', '=', 'wl.wagon_id')
            ->where('r.siding_id', $sidingId)
            ->whereNotNull('wl.loader_operator_name')
            ->where('wl.loader_operator_name', '!=', '')
            ->whereNotNull('wl.loaded_quantity_mt')
            ->whereNotNull('r.loading_date')
            ->whereBetween(DB::raw("DATE(r.loading_date{$dateCast})"), [$baselineStart, $weekEnd])
            ->selectRaw(
                'TRIM(wl.loader_operator_name) as operator_name,'
                .' COUNT(*) as total_wagons,'
                .' SUM(CASE WHEN wl.loaded_quantity_mt > COALESCE(wl.cc_capacity_mt, w.pcc_weight_mt)'
                .'     AND COALESCE(wl.cc_capacity_mt, w.pcc_weight_mt) IS NOT NULL'
                .'     AND COALESCE(wl.cc_capacity_mt, w.pcc_weight_mt) > 0'
                .'     THEN 1 ELSE 0 END) as overloaded_wagons'
            )
            ->groupByRaw('TRIM(wl.loader_operator_name)')
            ->get()
            ->keyBy('operator_name');

        $signals = [];

        foreach ($thisWeek as $row) {
            $wagonsThisWeek = (int) $row->total_wagons;

            if ($wagonsThisWeek < 10) {
                continue;
            }

            $thisWeekRate = $wagonsThisWeek > 0
                ? (int) $row->overloaded_wagons / $wagonsThisWeek
                : 0.0;

            $baselineRow = $baseline->get($row->operator_name);
            $baselineTotal = $baselineRow ? (int) $baselineRow->total_wagons : 0;
            $baselineRate = $baselineTotal > 0
                ? (int) $baselineRow->overloaded_wagons / $baselineTotal
                : 0.0;

            // Must be at least 2× baseline.
            if ($baselineRate <= 0.0 || $thisWeekRate < 2.0 * $baselineRate) {
                continue;
            }

            $ratio = $thisWeekRate / $baselineRate;
            $severity = $ratio >= 3.0 ? 'high' : 'medium';

            $rsAtStake = round((float) $row->overload_mt_sum * $rsPerMt, 2);

            $signals[] = new Signal(
                type: 'operator_anomaly',
                severity: $severity,
                rsAtStake: $rsAtStake,
                recencyMinutes: 60,
                actionability: 0.7,
                payload: [
                    'operator_name' => (string) $row->operator_name,
                    'this_week_rate' => round($thisWeekRate, 4),
                    'baseline_rate' => round($baselineRate, 4),
                    'wagons_this_week' => $wagonsThisWeek,
                ],
            );
        }

        return $signals;
    }

    /**
     * Signal 3: force_majeure
     *
     * Delegates to DowntimePenaltyMatcher::candidates() and wraps each candidate
     * in a Signal. All force-majeure signals are severity 'medium' (recovery opportunity,
     * not urgent).
     *
     * @return list<Signal>
     */
    private function collectForceMajeure(int $sidingId): array
    {
        $candidates = $this->matcher->candidates($sidingId);

        $signals = [];

        foreach ($candidates as $candidate) {
            // Recency: minutes since the downtime event's start_local_time.
            // We re-query the event to get the start time.
            $eventId = $candidate['downtime_event_id'] ?? null;
            $recencyMinutes = 0;

            if ($eventId !== null) {
                $startTime = DB::table('loadrite_downtime_events')
                    ->where('id', $eventId)
                    ->value('start_local_time');

                if ($startTime !== null) {
                    $recencyMinutes = (int) round(now()->diffInMinutes($startTime, true));
                }
            }

            // Recovery value approximation: (overlap_minutes / 60) × rs_per_hour.
            // Using the demurrage rate (Rs/hour) is dimensionally correct;
            // the old formula (overlap_minutes × rs_per_mt) mixed minutes with Rs/MT.
            $rsPerHour = (float) config('penalties.demurrage.rs_per_hour', 5000);
            $rsAtStake = round(($candidate['overlap_minutes'] / 60.0) * $rsPerHour, 2);

            $signals[] = new Signal(
                type: 'force_majeure',
                severity: 'medium',
                rsAtStake: $rsAtStake,
                recencyMinutes: $recencyMinutes,
                actionability: 0.6,
                payload: [
                    'rake_id' => (int) $candidate['rake_id'],
                    'reconciliation_id' => (int) $candidate['reconciliation_id'],
                    'overlap_minutes' => (int) $candidate['overlap_minutes'],
                    'reason' => (string) $candidate['reason'],
                ],
            );
        }

        return $signals;
    }

    /**
     * Signal 4: scale_silence
     *
     * For each distinct scale_id that appears in loadrite_events for this siding
     * within the last 24 hours, check the most-recent event time. If the gap from
     * now is > 2 hours AND there is an active rake (loading/placed) at the siding,
     * emit a high-severity signal.
     *
     * @return list<Signal>
     */
    private function collectScaleSilence(int $sidingId): array
    {
        $hasActiveRake = Rake::query()
            ->where('siding_id', $sidingId)
            ->whereIn('state', ['loading', 'placed'])
            ->exists();

        if (! $hasActiveRake) {
            return [];
        }

        $scales = DB::table('loadrite_events')
            ->where('siding_id', $sidingId)
            ->whereNotNull('scale_id')
            ->where('event_time', '>=', now()->subHours(24))
            ->selectRaw('scale_id, MAX(event_time) as last_event_at')
            ->groupBy('scale_id')
            ->get();

        $signals = [];

        foreach ($scales as $scale) {
            $lastEventAt = $scale->last_event_at;
            $gapMinutes = (int) round(now()->diffInMinutes($lastEventAt, true));

            if ($gapMinutes <= 120) {
                continue;
            }

            $signals[] = new Signal(
                type: 'scale_silence',
                severity: 'high',
                rsAtStake: 0.0,
                recencyMinutes: $gapMinutes,
                actionability: 0.5,
                payload: [
                    'scale_id' => (string) $scale->scale_id,
                    'last_event_at' => (string) $lastEventAt,
                    'gap_minutes' => $gapMinutes,
                ],
            );
        }

        return $signals;
    }

    /**
     * Signal 5: pending_override
     *
     * Reads the pending-override queue via PendingQueue. If the oldest pending
     * loading override has been waiting more than 120 minutes, emit one signal.
     * Severity: medium if > 120 min, high if > 480 min.
     *
     * @return list<Signal>
     */
    private function collectPendingOverride(int $sidingId): array
    {
        $pending = LoadingOverride::query()
            ->whereNull('supervisor_review_at')
            ->whereHas('rake', fn ($q) => $q->where('siding_id', $sidingId))
            ->orderBy('created_at')
            ->get(['id', 'created_at']);

        $count = $pending->count();

        if ($count === 0) {
            return [];
        }

        $oldestMinutes = (int) round($pending->first()->created_at->diffInMinutes(now(), true));

        if ($oldestMinutes <= 120) {
            return [];
        }

        $severity = $oldestMinutes > 480 ? 'high' : 'medium';

        return [
            new Signal(
                type: 'pending_override',
                severity: $severity,
                rsAtStake: 0.0,
                recencyMinutes: $oldestMinutes,
                actionability: 0.8,
                payload: [
                    'pending_count' => $count,
                    'oldest_minutes' => $oldestMinutes,
                ],
            ),
        ];
    }

    /**
     * Signal 6: underloading_trend
     *
     * Compares last-week vs prior-week SUM(closing_stock_mt) from SidingPerformance.
     * Emits when the delta is negative and the drop exceeds 10 %.
     * Severity: high if drop ≥ 25 %, medium if ≥ 10 %.
     *
     * @return list<Signal>
     */
    private function collectUnderloadingTrend(int $sidingId): array
    {
        $rsPerMt = (float) config('penalties.underload.rs_per_mt', 500);

        $today = CarbonImmutable::today();

        $currentStart = $today->subDays(6)->toDateString();
        $currentEnd = $today->toDateString();
        $priorStart = $today->subDays(13)->toDateString();
        $priorEnd = $today->subDays(7)->toDateString();

        $rows = SidingPerformance::query()
            ->where('siding_id', $sidingId)
            ->whereDate('as_of_date', '>=', $priorStart)
            ->whereDate('as_of_date', '<=', $currentEnd)
            ->get(['as_of_date', 'closing_stock_mt']);

        $thisWeekMt = 0.0;
        $priorWeekMt = 0.0;

        foreach ($rows as $row) {
            $dateStr = $row->as_of_date->toDateString();
            $mt = (float) $row->closing_stock_mt;

            if ($dateStr >= $currentStart && $dateStr <= $currentEnd) {
                $thisWeekMt += $mt;
            } elseif ($dateStr >= $priorStart && $dateStr <= $priorEnd) {
                $priorWeekMt += $mt;
            }
        }

        if ($priorWeekMt <= 0.0) {
            return [];
        }

        $deltaPct = (($thisWeekMt - $priorWeekMt) / $priorWeekMt) * 100.0;

        if ($deltaPct >= -10.0) {
            return [];
        }

        $dropPct = abs($deltaPct);
        $severity = $dropPct >= 25.0 ? 'high' : 'medium';

        $rsAtStake = round(abs($thisWeekMt - $priorWeekMt) * $rsPerMt, 2);

        return [
            new Signal(
                type: 'underloading_trend',
                severity: $severity,
                rsAtStake: $rsAtStake,
                recencyMinutes: 60,
                actionability: 0.4,
                payload: [
                    'this_week_mt' => round($thisWeekMt, 2),
                    'prior_week_mt' => round($priorWeekMt, 2),
                    'delta_pct' => round($deltaPct, 1),
                ],
            ),
        ];
    }

    /**
     * Signal 7: demurrage_risk
     *
     * For each rake at the siding in loading or placed state, compute hours-to-deadline
     * (12-hour SLA from placement_time). Emit when hours-to-deadline < 3.
     * Severity: critical if hours-to-deadline ≤ 1, high otherwise.
     * rs_at_stake = hours_overdue × rs_per_hour (0 if not yet overdue).
     *
     * @return list<Signal>
     */
    private function collectDemurrageRisk(int $sidingId): array
    {
        $rsPerHour = (float) config('penalties.demurrage.rs_per_hour', 5000);

        $activeRakes = Rake::query()
            ->where('siding_id', $sidingId)
            ->whereIn('state', ['loading', 'placed'])
            ->whereNotNull('placement_time')
            ->get(['id', 'rake_number', 'placement_time']);

        $signals = [];

        foreach ($activeRakes as $rake) {
            $deadline = $rake->placement_time->addHours(self::SLA_HOURS);
            $hoursToDeadline = now()->diffInHours($deadline, false); // negative = already overdue

            if ($hoursToDeadline >= self::DEMURRAGE_WARN_HOURS) {
                continue;
            }

            $severity = $hoursToDeadline <= 1 ? 'critical' : 'high';

            $hoursOverdue = $hoursToDeadline < 0 ? abs($hoursToDeadline) : 0;
            $rsAtStake = $hoursOverdue > 0 ? round($hoursOverdue * $rsPerHour, 2) : 0.0;

            $signals[] = new Signal(
                type: 'demurrage_risk',
                severity: $severity,
                rsAtStake: $rsAtStake,
                recencyMinutes: 5,
                actionability: 0.9,
                payload: [
                    'rake_id' => (int) $rake->id,
                    'rake_number' => (string) $rake->rake_number,
                    'hours_remaining' => max(0, (float) round($hoursToDeadline, 2)),
                    'hours_overdue' => (float) $hoursOverdue,
                ],
            );
        }

        return $signals;
    }
}
