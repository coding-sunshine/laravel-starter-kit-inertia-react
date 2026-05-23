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
 * Collects all 11 operational signal types for a given siding and returns
 * them as an unsorted list of Signal DTOs ready for downstream ranking.
 *
 * Signal types emitted (operational):
 *   1. overload_exposure          – wagons currently being loaded over PCC capacity
 *   2. operator_anomaly           – loader whose this-week overload rate ≥ 2× their 30-day baseline
 *   3. force_majeure              – DEM reconciliation with a qualifying downtime overlap
 *   4. scale_silence              – Loadrite scale silent for > 2 h while an active rake is present
 *   5. pending_override           – oldest unreviewed loading override has been waiting > 2 h
 *   6. underloading_trend         – closing_stock_mt week-over-week drop > 10 %
 *   7. demurrage_risk             – rake within 3 h of the 12-hour SLA from placement_time
 *
 * Signal types emitted (forecast — mine historical data):
 *   8. operator_recurring_risk    – operator with ≥ 10 wagons and persistent overload rate ≥ 2 %
 *   9. penalty_trajectory         – penalty Rs growing month-on-month for ≥ 2 consecutive months
 *  10. demurrage_turnaround_risk  – avg rake turnaround exceeds 13 h over last 30 days
 *  11. pcc_drift                  – wagon type whose median loaded_quantity_mt drifts ≥ 0.5 MT from PCC
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
            $this->collectOperatorRecurringRisk($sidingId),
            $this->collectPenaltyTrajectory($sidingId),
            $this->collectDemurrageTurnaroundRisk($sidingId),
            $this->collectPccDrift($sidingId),
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

    /**
     * Signal 8: operator_recurring_risk (forecast)
     *
     * For each operator at the siding with ≥ 10 wagons loaded in the last 30 days
     * and an overload rate (wagons over PCC / total wagons) ≥ 2 %, emit ONE signal.
     * Severity: critical ≥ 10 %, high ≥ 5 %, medium ≥ 2 %.
     *
     * @return list<Signal>
     */
    private function collectOperatorRecurringRisk(int $sidingId): array
    {
        $rsPerMt = (float) config('penalties.overload.rs_per_mt', 1000);

        $cutoff = CarbonImmutable::now()->subDays(30)->toDateString();

        $driver = DB::getDriverName();
        $dateCast = $driver === 'pgsql' ? '::date' : '';

        $rows = DB::table('wagon_loading as wl')
            ->join('rakes as r', 'r.id', '=', 'wl.rake_id')
            ->join('wagons as w', 'w.id', '=', 'wl.wagon_id')
            ->where('r.siding_id', $sidingId)
            ->whereNotNull('wl.loader_operator_name')
            ->where('wl.loader_operator_name', '!=', '')
            ->whereNotNull('wl.loaded_quantity_mt')
            ->whereNotNull('w.pcc_weight_mt')
            ->where(DB::raw("DATE(r.loading_date{$dateCast})"), '>=', $cutoff)
            ->selectRaw(
                'TRIM(wl.loader_operator_name) as operator_name,'
                .' COUNT(*) as total_wagons,'
                .' SUM(CASE WHEN wl.loaded_quantity_mt > w.pcc_weight_mt'
                .'     AND (wl.weight_source IS NULL OR wl.weight_source != \'weighbridge\')'
                .'     THEN 1 ELSE 0 END) as overloaded_wagons,'
                .' SUM(CASE WHEN wl.loaded_quantity_mt > w.pcc_weight_mt'
                .'     AND (wl.weight_source IS NULL OR wl.weight_source != \'weighbridge\')'
                .'     THEN wl.loaded_quantity_mt - w.pcc_weight_mt ELSE 0 END) as overload_mt_sum,'
                .' SUM(CASE WHEN wl.loaded_quantity_mt > w.pcc_weight_mt'
                .'     AND (wl.weight_source IS NULL OR wl.weight_source != \'weighbridge\')'
                .'     THEN 1 ELSE 0 END) as overloaded_count'
            )
            ->groupByRaw('TRIM(wl.loader_operator_name)')
            ->get();

        $signals = [];

        foreach ($rows as $row) {
            $totalWagons = (int) $row->total_wagons;

            if ($totalWagons < 10) {
                continue;
            }

            $overloadedCount = (int) $row->overloaded_wagons;
            $overloadRate = $totalWagons > 0 ? ($overloadedCount / $totalWagons) : 0.0;

            if ($overloadRate < 0.02) {
                continue;
            }

            $severity = match (true) {
                $overloadRate >= 0.10 => 'critical',
                $overloadRate >= 0.05 => 'high',
                default => 'medium',
            };

            $avgOverloadMt = $overloadedCount > 0
                ? (float) $row->overload_mt_sum / $overloadedCount
                : 0.0;

            // projected_next_rake_rs: rate × 59 wagons/rake × avg_overload_mt × rs_per_mt
            $projectedNextRakeRs = round($overloadRate * 59.0 * $avgOverloadMt * $rsPerMt, 2);

            $signals[] = new Signal(
                type: 'operator_recurring_risk',
                severity: $severity,
                rsAtStake: $projectedNextRakeRs,
                recencyMinutes: 60,
                actionability: 0.6,
                payload: [
                    'operator_name' => (string) $row->operator_name,
                    'overload_rate_pct' => round($overloadRate * 100.0, 2),
                    'wagons_observed' => $totalWagons,
                    'avg_overload_mt' => round($avgOverloadMt, 3),
                    'projected_next_rake_rs' => $projectedNextRakeRs,
                ],
            );
        }

        return $signals;
    }

    /**
     * Signal 9: penalty_trajectory (forecast)
     *
     * Computes siding-level penalty Rs for each of the last 3 calendar months
     * from SidingPerformance.total_penalty_amount. Emits one signal when
     * month-on-month growth ≥ 10 % and the trend spans ≥ 2 consecutive months.
     * Severity: high if growth ≥ 20 % AND 2+ months; medium if 10–20 %.
     *
     * @return list<Signal>
     */
    private function collectPenaltyTrajectory(int $sidingId): array
    {
        $today = CarbonImmutable::today();

        // Build 3 calendar-month windows: current, prior, two-months-ago.
        $months = [];

        for ($offset = 0; $offset <= 2; $offset++) {
            $monthStart = $today->subMonths($offset)->startOfMonth()->toDateString();
            $monthEnd = $today->subMonths($offset)->endOfMonth()->toDateString();

            $total = (float) SidingPerformance::query()
                ->where('siding_id', $sidingId)
                ->whereDate('as_of_date', '>=', $monthStart)
                ->whereDate('as_of_date', '<=', $monthEnd)
                ->sum('total_penalty_amount');

            $months[] = $total;
        }

        // $months[0] = current month, $months[1] = prior month, $months[2] = two months ago.
        [$currentRs, $priorRs, $twoMonthsAgoRs] = $months;

        if ($priorRs <= 0.0) {
            return [];
        }

        $momGrowth = ($currentRs - $priorRs) / $priorRs;

        if ($momGrowth < 0.10) {
            return [];
        }

        // Check for consecutive growth: current > prior AND prior > two-months-ago.
        $monthsOfGrowth = 1;

        if ($twoMonthsAgoRs > 0.0 && $priorRs > $twoMonthsAgoRs) {
            $monthsOfGrowth = 2;
        }

        if ($monthsOfGrowth < 2) {
            return [];
        }

        $severity = ($momGrowth >= 0.20 && $monthsOfGrowth >= 2) ? 'high' : 'medium';
        $rsAtStake = round($currentRs * $momGrowth, 2);

        return [
            new Signal(
                type: 'penalty_trajectory',
                severity: $severity,
                rsAtStake: $rsAtStake,
                recencyMinutes: 60,
                actionability: 0.5,
                payload: [
                    'current_month_rs' => round($currentRs, 2),
                    'previous_month_rs' => round($priorRs, 2),
                    'growth_pct' => round($momGrowth * 100.0, 1),
                    'months_of_growth' => $monthsOfGrowth,
                ],
            ),
        ];
    }

    /**
     * Signal 10: demurrage_turnaround_risk (forecast)
     *
     * Computes average (loading_end_time − placement_time) in hours across rakes
     * at the siding in the last 30 days where both timestamps are non-null and
     * state != 'cancelled'. Emits when avg > 13 h (SLA = 12 h, overrun > 1 h).
     * Severity: high if overrun > 4 h, medium if > 2 h, low if > 1 h.
     *
     * @return list<Signal>
     */
    private function collectDemurrageTurnaroundRisk(int $sidingId): array
    {
        $rsPerHour = (float) config('penalties.demurrage.rs_per_hour', 5000);

        $cutoff = CarbonImmutable::now()->subDays(30)->toDateTimeString();

        $driver = DB::getDriverName();

        // Compute average turnaround in hours; use portable TIMESTAMPDIFF or julianday.
        if ($driver === 'sqlite') {
            $diffHoursExpr = '(JULIANDAY(loading_end_time) - JULIANDAY(placement_time)) * 24';
        } else {
            $diffHoursExpr = 'TIMESTAMPDIFF(SECOND, placement_time, loading_end_time) / 3600.0';
        }

        $result = DB::table('rakes')
            ->where('siding_id', $sidingId)
            ->where('state', '!=', 'cancelled')
            ->whereNotNull('placement_time')
            ->whereNotNull('loading_end_time')
            ->where('placement_time', '>=', $cutoff)
            ->selectRaw("AVG({$diffHoursExpr}) as avg_hours, COUNT(*) as rakes_count")
            ->first();

        if ($result === null || (int) $result->rakes_count === 0) {
            return [];
        }

        $avgTurnaroundHours = (float) $result->avg_hours;
        $rakesObserved = (int) $result->rakes_count;

        $slaHours = 12.0;
        $overrunHours = $avgTurnaroundHours - $slaHours;

        if ($overrunHours <= 1.0) {
            return [];
        }

        $severity = match (true) {
            $overrunHours > 4.0 => 'high',
            $overrunHours > 2.0 => 'medium',
            default => 'low',
        };

        // rs_at_stake = avg_overrun_hours × rs_per_hour × rakes_per_month (observed cadence)
        $rsAtStake = round($overrunHours * $rsPerHour * $rakesObserved, 2);

        return [
            new Signal(
                type: 'demurrage_turnaround_risk',
                severity: $severity,
                rsAtStake: $rsAtStake,
                recencyMinutes: 60,
                actionability: 0.7,
                payload: [
                    'avg_turnaround_hours' => round($avgTurnaroundHours, 2),
                    'sla_hours' => $slaHours,
                    'overrun_hours' => round($overrunHours, 2),
                    'rakes_observed' => $rakesObserved,
                ],
            ),
        ];
    }

    /**
     * Signal 11: pcc_drift (forecast)
     *
     * For each wagon type at the siding, computes median (loaded_quantity_mt − pcc_weight_mt)
     * over the last 30 days. Only considers types with ≥ 5 loaded wagons; skips
     * weighbridge-sourced rows. Emits one signal per wagon type whose absolute
     * median drift ≥ 0.5 MT.
     * Severity: medium if |drift| ≥ 1.5 MT, low otherwise.
     *
     * @return list<Signal>
     */
    private function collectPccDrift(int $sidingId): array
    {
        $rsPerMt = (float) config('penalties.overload.rs_per_mt', 1000);

        $cutoff = CarbonImmutable::now()->subDays(30)->toDateString();
        $driver = DB::getDriverName();
        $dateCast = $driver === 'pgsql' ? '::date' : '';

        // Pull all qualifying wagon-loading rows for the window.
        $rows = DB::table('wagon_loading as wl')
            ->join('rakes as r', 'r.id', '=', 'wl.rake_id')
            ->join('wagons as w', 'w.id', '=', 'wl.wagon_id')
            ->where('r.siding_id', $sidingId)
            ->whereNotNull('wl.loaded_quantity_mt')
            ->whereNotNull('w.pcc_weight_mt')
            ->where('w.pcc_weight_mt', '>', 0)
            ->where(function ($q): void {
                $q->whereNull('wl.weight_source')
                    ->orWhere('wl.weight_source', '!=', 'weighbridge');
            })
            ->where(DB::raw("DATE(r.loading_date{$dateCast})"), '>=', $cutoff)
            ->selectRaw(
                'w.wagon_type,'
                .' (wl.loaded_quantity_mt - w.pcc_weight_mt) as drift_mt'
            )
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        // Group drifts by wagon type.
        $byType = [];
        $totalWagons = $rows->count();

        foreach ($rows as $row) {
            $type = (string) ($row->wagon_type ?? 'UNKNOWN');
            $byType[$type][] = (float) $row->drift_mt;
        }

        $signals = [];

        foreach ($byType as $wagonType => $drifts) {
            $wagonsInType = count($drifts);

            if ($wagonsInType < 5) {
                continue;
            }

            // Compute median drift.
            sort($drifts);
            $midIndex = (int) floor($wagonsInType / 2);
            $medianDrift = ($wagonsInType % 2 === 0)
                ? ($drifts[$midIndex - 1] + $drifts[$midIndex]) / 2.0
                : $drifts[$midIndex];

            if (abs($medianDrift) < 0.5) {
                continue;
            }

            $severity = abs($medianDrift) >= 1.5 ? 'medium' : 'low';

            $fractionOfFleet = $totalWagons > 0 ? ($wagonsInType / $totalWagons) : 0.0;

            // rakes_per_month = observed rakes in window (30 days / 30 = 1.0 factor → same count).
            $rakesPerMonth = max(1, (int) round($totalWagons / 59.0));

            $rsAtStake = round(
                abs($medianDrift) * $rakesPerMonth * 59.0 * $rsPerMt * $fractionOfFleet,
                2
            );

            $signals[] = new Signal(
                type: 'pcc_drift',
                severity: $severity,
                rsAtStake: $rsAtStake,
                recencyMinutes: 60,
                actionability: 0.4,
                payload: [
                    'wagon_type' => $wagonType,
                    'median_drift_mt' => round($medianDrift, 3),
                    'wagons_observed' => $wagonsInType,
                    'fraction_of_fleet_pct' => round($fractionOfFleet * 100.0, 1),
                ],
            );
        }

        return $signals;
    }
}
