<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Models\LoadingOverride;
use App\Models\LoadriteDowntimeEvent;
use App\Models\LoadriteEvent;
use App\Models\PenaltyReconciliation;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\SidingPerformance;
use App\Models\Wagon;
use App\Models\WagonLoading;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Provides a rich, reproducible fixture for CollectSignals tests.
 *
 * Call seedManagerBriefFixture(int $sidingId) to seed all seven signal
 * scenarios in one shot. Returns a keyed map of the created models so
 * individual test cases can assert against specific IDs.
 */
trait SeedsManagerBriefFixture
{
    /**
     * Seed a complete fixture that exercises all 7 signal types.
     *
     * @return array{
     *     siding: Siding,
     *     overload_rake: Rake,
     *     overload_wagon: Wagon,
     *     anomaly_rake: Rake,
     *     anomaly_wagon: Wagon,
     *     fm_rake: Rake,
     *     fm_reconciliation: PenaltyReconciliation,
     *     fm_downtime_event: LoadriteDowntimeEvent,
     *     silent_scale_id: string,
     *     pending_override_rake: Rake,
     *     pending_override: LoadingOverride,
     *     perf_this_week: list<SidingPerformance>,
     *     perf_prior_week: list<SidingPerformance>,
     *     demurrage_rake: Rake,
     * }
     */
    public function seedManagerBriefFixture(int $sidingId): array
    {
        $siding = Siding::query()->findOrFail($sidingId);

        // -----------------------------------------------------------------------
        // 1. Overload exposure: one active rake with a wagon over PCC.
        // -----------------------------------------------------------------------
        $overloadRake = Rake::factory()->create([
            'siding_id' => $sidingId,
            'state' => 'loading',
            'placement_time' => now()->subHours(6),
        ]);

        $overloadWagon = Wagon::factory()->create([
            'rake_id' => $overloadRake->id,
            'pcc_weight_mt' => 68.0,
            'is_unfit' => false,
        ]);

        // 71 MT loaded → 3 MT overload (severity: high, rs = 3000 at default 1000 Rs/MT)
        WagonLoading::factory()->create([
            'rake_id' => $overloadRake->id,
            'wagon_id' => $overloadWagon->id,
            'loaded_quantity_mt' => 71.0,
            'weight_source' => 'manual',
        ]);

        // A loadrite event so the ghost-rake guard passes.
        LoadriteEvent::create([
            'event_id' => Str::uuid()->toString(),
            'siding_id' => $sidingId,
            'rake_id' => $overloadRake->id,
            'wagon_id' => $overloadWagon->id,
            'wagon_sequence' => 1,
            'event_type' => 'Add',
            'weight_mt' => 71.0,
            'event_time' => now()->subMinutes(15),
            'scale_id' => 'SCALE-A',
        ]);

        // -----------------------------------------------------------------------
        // 2. Operator anomaly: one operator whose this-week rate is ≥ 2× baseline.
        // -----------------------------------------------------------------------
        // Baseline (30 days): 20 wagons, 1 overloaded → 5 % rate.
        // This week: 10 wagons, 3 overloaded → 30 % rate → 6× baseline.
        $today = CarbonImmutable::today();
        $weekStart = $today->startOfWeek();

        $anomalyRake = Rake::factory()->create([
            'siding_id' => $sidingId,
            'state' => 'loading',
            'loading_date' => $weekStart->toDateString(),
        ]);

        $anomalyWagon = Wagon::factory()->create([
            'rake_id' => $anomalyRake->id,
            'pcc_weight_mt' => 68.0,
            'is_unfit' => false,
        ]);

        // 10 wagons this week for the anomaly operator: 3 overloaded.
        for ($i = 1; $i <= 10; $i++) {
            $w = Wagon::factory()->create(['rake_id' => $anomalyRake->id, 'pcc_weight_mt' => 68.0, 'is_unfit' => false]);
            WagonLoading::factory()->create([
                'rake_id' => $anomalyRake->id,
                'wagon_id' => $w->id,
                'loaded_quantity_mt' => $i <= 3 ? 72.0 : 66.0, // 3 overloaded
                'loader_operator_name' => 'Anomaly Operator',
                'cc_capacity_mt' => null,
            ]);
        }

        // 20 wagons in baseline period (same week included — prior weeks): 1 overloaded.
        $baselineRake = Rake::factory()->create([
            'siding_id' => $sidingId,
            'state' => 'loading',
            'loading_date' => $today->subDays(15)->toDateString(),
        ]);

        for ($i = 1; $i <= 20; $i++) {
            $w = Wagon::factory()->create(['rake_id' => $baselineRake->id, 'pcc_weight_mt' => 68.0, 'is_unfit' => false]);
            WagonLoading::factory()->create([
                'rake_id' => $baselineRake->id,
                'wagon_id' => $w->id,
                'loaded_quantity_mt' => $i === 1 ? 72.0 : 66.0, // 1 overloaded
                'loader_operator_name' => 'Anomaly Operator',
                'cc_capacity_mt' => null,
            ]);
        }

        // -----------------------------------------------------------------------
        // 3. Force-majeure: a DEM reconciliation + loadrite downtime overlap.
        // -----------------------------------------------------------------------
        $fmPlacementTime = now()->subHours(20);
        $fmLoadingEnd = now()->subHours(8);

        $fmRake = Rake::factory()->create([
            'siding_id' => $sidingId,
            'state' => 'loading',
            'placement_time' => $fmPlacementTime,
            'loading_end_time' => $fmLoadingEnd,
        ]);

        $fmReconciliation = PenaltyReconciliation::factory()->create([
            'rake_id' => $fmRake->id,
            'penalty_code' => 'DEM',
            'dispute_candidate' => false,
            'notes' => null,
            'reconciled_at' => now()->subDays(2),
        ]);

        // Downtime event that overlaps the rake's loading window by 60 minutes.
        $downtimeStart = $fmPlacementTime->addHours(2);
        $fmDowntimeEvent = LoadriteDowntimeEvent::factory()->create([
            'siding_id' => $sidingId,
            'start_local_time' => $downtimeStart,
            'end_local_time' => $downtimeStart->addMinutes(60),
            'duration_minutes' => 60,
            'reason_name' => 'Plant Stoppage',
        ]);

        // -----------------------------------------------------------------------
        // 4. Scale silence: a scale that had an event > 2 hours ago.
        // -----------------------------------------------------------------------
        $silentScaleId = 'SCALE-SILENT';

        // Last event was 3 hours ago → gap = 180 min → triggers scale_silence.
        LoadriteEvent::create([
            'event_id' => Str::uuid()->toString(),
            'siding_id' => $sidingId,
            'wagon_sequence' => 1,
            'event_type' => 'Add',
            'weight_mt' => 65.0,
            'event_time' => now()->subHours(3),
            'scale_id' => $silentScaleId,
        ]);

        // The overload_rake already seeded above acts as the "active rake" guard.

        // -----------------------------------------------------------------------
        // 5. Pending override: one override waiting > 2 hours (121 min ago).
        // -----------------------------------------------------------------------
        $pendingOverrideRake = Rake::factory()->create([
            'siding_id' => $sidingId,
            'state' => 'loading',
        ]);

        $pendingOverride = LoadingOverride::factory()->create([
            'rake_id' => $pendingOverrideRake->id,
            'supervisor_review_at' => null,
            'created_at' => now()->subMinutes(121),
            'updated_at' => now()->subMinutes(121),
        ]);

        // -----------------------------------------------------------------------
        // 6. Underloading trend: prior week high stock, this week low stock → > 10 % drop.
        // -----------------------------------------------------------------------
        // Prior week: 7 days × 1000 MT = 7000 MT total.
        // This week: 7 days × 500 MT = 3500 MT total.
        // Drop = 50 % → severity: high.
        $perfThisWeek = [];
        $perfPriorWeek = [];

        for ($i = 6; $i >= 0; $i--) {
            $perfThisWeek[] = SidingPerformance::create([
                'siding_id' => $sidingId,
                'as_of_date' => $today->subDays($i)->toDateString(),
                'closing_stock_mt' => 500.0,
                'rakes_processed' => 0,
                'total_penalty_amount' => 0,
                'penalty_incidents' => 0,
                'average_demurrage_hours' => 0,
                'overload_incidents' => 0,
            ]);
        }

        for ($i = 13; $i >= 7; $i--) {
            $perfPriorWeek[] = SidingPerformance::create([
                'siding_id' => $sidingId,
                'as_of_date' => $today->subDays($i)->toDateString(),
                'closing_stock_mt' => 1000.0,
                'rakes_processed' => 0,
                'total_penalty_amount' => 0,
                'penalty_incidents' => 0,
                'average_demurrage_hours' => 0,
                'overload_incidents' => 0,
            ]);
        }

        // -----------------------------------------------------------------------
        // 7. Demurrage risk: rake placed 10 hours ago → 2 hours left → below 3 h threshold.
        // -----------------------------------------------------------------------
        $demurrageRake = Rake::factory()->create([
            'siding_id' => $sidingId,
            'state' => 'placed',
            'placement_time' => now()->subHours(10),
        ]);

        return [
            'siding' => $siding,
            'overload_rake' => $overloadRake,
            'overload_wagon' => $overloadWagon,
            'anomaly_rake' => $anomalyRake,
            'anomaly_wagon' => $anomalyWagon,
            'fm_rake' => $fmRake,
            'fm_reconciliation' => $fmReconciliation,
            'fm_downtime_event' => $fmDowntimeEvent,
            'silent_scale_id' => $silentScaleId,
            'pending_override_rake' => $pendingOverrideRake,
            'pending_override' => $pendingOverride,
            'perf_this_week' => $perfThisWeek,
            'perf_prior_week' => $perfPriorWeek,
            'demurrage_rake' => $demurrageRake,
        ];
    }
}
