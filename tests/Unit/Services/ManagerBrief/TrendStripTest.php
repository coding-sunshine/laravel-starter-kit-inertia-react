<?php

declare(strict_types=1);

use App\Models\Siding;
use App\Models\SidingPerformance;
use App\Services\ManagerBrief\TrendStrip;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Seed daily SidingPerformance rows for $siding going back $days days from today.
 *
 * $values is a map of field -> per-day values (index 0 = oldest, index $days-1 = yesterday).
 * Missing fields fall back to their schema defaults (0).
 *
 * NOTE: on_time_pct is NOT a real column in siding_performance — the table has no
 * such field at time of writing. Tests for on_time_dispatch_pct therefore assert
 * that the service returns zeros (current=0, prior=0, delta_pct=0, spark=[0×7]).
 *
 * @param  array<string, list<float>>  $values
 */
function seedSidingPerformance(Siding $siding, int $days, array $values = []): void
{
    $today = CarbonImmutable::today();

    for ($i = $days - 1; $i >= 0; $i--) {
        $date = $today->subDays($i);
        $idx = $days - 1 - $i;   // 0 = oldest day

        SidingPerformance::create([
            'siding_id' => $siding->id,
            'as_of_date' => $date->toDateString(),
            'total_penalty_amount' => $values['total_penalty_amount'][$idx] ?? 0,
            'closing_stock_mt' => $values['closing_stock_mt'][$idx] ?? 0,
            'rakes_processed' => $values['rakes_processed'][$idx] ?? 0,
            'penalty_incidents' => $values['penalty_incidents'][$idx] ?? 0,
            'average_demurrage_hours' => $values['average_demurrage_hours'][$idx] ?? 0,
            'overload_incidents' => $values['overload_incidents'][$idx] ?? 0,
        ]);
    }
}

beforeEach(function (): void {
    $this->service = app(TrendStrip::class);
    Cache::flush();
});

// ---------------------------------------------------------------------------
// 6.2 — penalty_rs week over week
// ---------------------------------------------------------------------------

it('returns penalty rs week over week', function (): void {
    $siding = Siding::factory()->create();

    // Prior week (days 14→8 ago): each day = 100 Rs → prior total = 700
    // Current week (days 7→1 ago): each day = 200 Rs → current total = 1400
    $penalties = array_merge(array_fill(0, 7, 100.0), array_fill(0, 7, 200.0));

    seedSidingPerformance($siding, 14, ['total_penalty_amount' => $penalties]);

    $result = $this->service->handle((int) $siding->id);

    expect($result)->toHaveKey('penalty_rs');

    $p = $result['penalty_rs'];

    expect($p['current'])->toBe(1400.0);
    expect($p['prior'])->toBe(700.0);

    // delta_pct = round(((1400 - 700) / 700) * 100, 1) = 100.0
    expect($p['delta_pct'])->toBe(100.0);

    // Spark should have 7 entries (current week, oldest → newest)
    expect($p['spark'])->toHaveCount(7);
    expect($p['spark'])->each->toEqual(200.0);
});

// ---------------------------------------------------------------------------
// 6.3 — throughput_mt week over week
// ---------------------------------------------------------------------------

it('returns throughput mt week over week', function (): void {
    $siding = Siding::factory()->create();

    // Prior week: 500 MT/day → 3500 total; current week: 600 MT/day → 4200 total
    $stocks = array_merge(array_fill(0, 7, 500.0), array_fill(0, 7, 600.0));

    seedSidingPerformance($siding, 14, ['closing_stock_mt' => $stocks]);

    $result = $this->service->handle((int) $siding->id);

    expect($result)->toHaveKey('throughput_mt');

    $t = $result['throughput_mt'];

    expect($t['current'])->toBe(4200.0);
    expect($t['prior'])->toBe(3500.0);

    // delta_pct = round(((4200 - 3500) / 3500) * 100, 1) = 20.0
    expect($t['delta_pct'])->toBe(20.0);

    expect($t['spark'])->toHaveCount(7);
    expect($t['spark'])->each->toEqual(600.0);
});

// ---------------------------------------------------------------------------
// 6.4 — on_time_dispatch_pct (no column → always zeros)
// ---------------------------------------------------------------------------

it('returns zeros for on time dispatch percentage because column does not exist', function (): void {
    // NOTE: The siding_performance table has no on_time_pct column.
    // The service hard-codes zeros for on_time_dispatch_pct until the schema
    // is extended. This test documents that contract.
    $siding = Siding::factory()->create();

    seedSidingPerformance($siding, 14);

    $result = $this->service->handle((int) $siding->id);

    expect($result)->toHaveKey('on_time_dispatch_pct');

    $o = $result['on_time_dispatch_pct'];

    expect($o['current'])->toBe(0.0);
    expect($o['prior'])->toBe(0.0);
    expect($o['delta_pct'])->toBe(0.0);
    expect($o['spark'])->toBe([0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0]);
});

// ---------------------------------------------------------------------------
// 6.5 — caches result for 6 hours per siding
// ---------------------------------------------------------------------------

it('caches result for 6 hours per siding', function (): void {
    $siding = Siding::factory()->create();
    $sidingId = (int) $siding->id;

    seedSidingPerformance($siding, 14, [
        'total_penalty_amount' => array_fill(0, 14, 50.0),
    ]);

    $cacheKey = "manager-brief:trend-strip:{$sidingId}:v1";

    // Before first call the key must not exist.
    expect(Cache::has($cacheKey))->toBeFalse();

    // First call: cache miss — inner closure fires and result is stored.
    $result1 = $this->service->handle($sidingId);

    // After first call the key must exist.
    expect(Cache::has($cacheKey))->toBeTrue();

    // Second call must return the SAME (identical) value from cache.
    // Verify by asserting the closure is NOT re-invoked: temporarily replace
    // the cached value with a sentinel; if the service reads the cache correctly
    // it returns the sentinel, not a freshly-computed array.
    Cache::put($cacheKey, ['sentinel' => true], 21600);

    $result2 = $this->service->handle($sidingId);

    // If cache is honoured, the sentinel is returned.
    expect($result2)->toBe(['sentinel' => true]);

    // Restore real value and confirm idempotent results.
    Cache::forget($cacheKey);
    $result3 = $this->service->handle($sidingId);
    Cache::put($cacheKey, $result3, 21600);
    $result4 = $this->service->handle($sidingId);

    expect($result4)->toBe($result3);
});

it('uses correct cache key and 6 hour ttl', function (): void {
    $siding = Siding::factory()->create();
    $sidingId = (int) $siding->id;

    $expectedKey = "manager-brief:trend-strip:{$sidingId}:v1";
    $expectedTtl = 21600;

    $captured = [];

    Cache::shouldReceive('remember')
        ->once()
        ->withArgs(function (string $key, int $ttl, Closure $callback) use (&$captured): bool {
            $captured['key'] = $key;
            $captured['ttl'] = $ttl;
            // Call closure to satisfy the mock return contract.
            $captured['result'] = $callback();

            return true;
        })
        ->andReturnUsing(function (string $key, int $ttl, Closure $callback) {
            return $callback();
        });

    $this->service->handle($sidingId);

    expect($captured['key'])->toBe($expectedKey);
    expect($captured['ttl'])->toBe($expectedTtl);
});

// ---------------------------------------------------------------------------
// 6.6 — graceful zeros when no data exists
// ---------------------------------------------------------------------------

it('returns zeros gracefully when no data exists', function (): void {
    $siding = Siding::factory()->create();
    // No SidingPerformance rows at all.

    $result = $this->service->handle((int) $siding->id);

    $zeroSpark = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];

    foreach (['penalty_rs', 'throughput_mt', 'on_time_dispatch_pct'] as $metric) {
        expect($result[$metric]['current'])->toBe(0.0, "{$metric}.current should be 0");
        expect($result[$metric]['prior'])->toBe(0.0, "{$metric}.prior should be 0");
        expect($result[$metric]['delta_pct'])->toBe(0.0, "{$metric}.delta_pct should be 0");
        expect($result[$metric]['spark'])->toBe($zeroSpark, "{$metric}.spark should be all zeros");
    }
});

// ---------------------------------------------------------------------------
// Additional: delta_pct edge cases
// ---------------------------------------------------------------------------

it('returns 100 delta pct when prior is zero and current is positive', function (): void {
    $siding = Siding::factory()->create();

    // Prior 7 days: 0, current 7 days: 500 Rs/day
    $penalties = array_merge(array_fill(0, 7, 0.0), array_fill(0, 7, 500.0));

    seedSidingPerformance($siding, 14, ['total_penalty_amount' => $penalties]);

    $result = $this->service->handle((int) $siding->id);

    expect($result['penalty_rs']['delta_pct'])->toBe(100.0);
});

it('returns negative delta pct when performance declined', function (): void {
    $siding = Siding::factory()->create();

    // Prior 7 days: 1000/day → 7000 total; current 7 days: 500/day → 3500 total
    $penalties = array_merge(array_fill(0, 7, 1000.0), array_fill(0, 7, 500.0));

    seedSidingPerformance($siding, 14, ['total_penalty_amount' => $penalties]);

    $result = $this->service->handle((int) $siding->id);

    // delta = round(((3500 - 7000) / 7000) * 100, 1) = -50.0
    expect($result['penalty_rs']['delta_pct'])->toBe(-50.0);
});

it('sparkline has seven values oldest to newest for current week', function (): void {
    $siding = Siding::factory()->create();

    // Give each of the 14 days a unique penalty so we can verify ordering.
    // Day 0 (oldest = 14 days ago) = 10, day 13 (most recent) = 140
    $penalties = array_map(fn (int $i): float => (float) (($i + 1) * 10), range(0, 13));

    seedSidingPerformance($siding, 14, ['total_penalty_amount' => $penalties]);

    $result = $this->service->handle((int) $siding->id);

    $spark = $result['penalty_rs']['spark'];

    expect($spark)->toHaveCount(7);

    // Current week = days 7..13 (indices in penalties array), i.e. values 80–140.
    expect($spark[0])->toBe(80.0);  // oldest of current week
    expect($spark[6])->toBe(140.0); // most recent day
    expect($spark[6])->toBeGreaterThan($spark[0]);
});
