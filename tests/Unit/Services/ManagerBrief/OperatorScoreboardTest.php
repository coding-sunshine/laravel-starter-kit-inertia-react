<?php

declare(strict_types=1);

use App\Models\Rake;
use App\Models\Siding;
use App\Models\Wagon;
use App\Models\WagonLoading;
use App\Services\ManagerBrief\OperatorScoreboard;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->scoreboard = app(OperatorScoreboard::class);
});

/**
 * Helper: create a rake in the window with wagons and operator-tagged wagon_loading rows.
 *
 * @param  array{name: string, total: int, in_band: int, overload_mt_each?: float}  $spec
 */
function seedOperator(Siding $siding, array $spec, ?int $daysAgo = 1): void
{
    $rake = Rake::factory()->create([
        'siding_id' => $siding->id,
        'loading_date' => Carbon::now()->subDays($daysAgo)->toDateString(),
    ]);

    $pcc = 68.0;
    $inBand = $spec['in_band'];
    $total = $spec['total'];
    $overloadMtEach = $spec['overload_mt_each'] ?? 0.0;

    for ($i = 0; $i < $total; $i++) {
        $wagon = Wagon::factory()->create([
            'rake_id' => $rake->id,
            'pcc_weight_mt' => $pcc,
        ]);

        $isInBand = $i < $inBand;
        $loaded = $isInBand
            ? $pcc * 0.97          // 97 % of PCC — well within 95-100 % band
            : $pcc + ($overloadMtEach > 0.0 ? $overloadMtEach : 1.0);  // overloaded

        WagonLoading::factory()->create([
            'rake_id' => $rake->id,
            'wagon_id' => $wagon->id,
            'loader_operator_name' => $spec['name'],
            'loaded_quantity_mt' => $loaded,
            'cc_capacity_mt' => null, // use pcc_weight_mt from wagons table
        ]);
    }
}

it('returns top 5 and bottom 5 by accuracy in 7 day window', function (): void {
    $siding = Siding::factory()->create();

    // 10 operators, each with ≥10 wagons, varying accuracy:
    // accuracy_pct = 100 * in_band / total
    // Operator A: 10/10 = 100 %
    // Operator B: 9/10  = 90 %
    // Operator C: 8/10  = 80 %
    // Operator D: 7/10  = 70 %
    // Operator E: 6/10  = 60 %
    // Operator F: 5/10  = 50 %
    // Operator G: 4/10  = 40 %
    // Operator H: 3/10  = 30 %
    // Operator I: 2/10  = 20 %
    // Operator J: 1/10  = 10 %
    $specs = [
        ['name' => 'Op A', 'total' => 10, 'in_band' => 10],
        ['name' => 'Op B', 'total' => 10, 'in_band' => 9],
        ['name' => 'Op C', 'total' => 10, 'in_band' => 8],
        ['name' => 'Op D', 'total' => 10, 'in_band' => 7],
        ['name' => 'Op E', 'total' => 10, 'in_band' => 6],
        ['name' => 'Op F', 'total' => 10, 'in_band' => 5],
        ['name' => 'Op G', 'total' => 10, 'in_band' => 4],
        ['name' => 'Op H', 'total' => 10, 'in_band' => 3],
        ['name' => 'Op I', 'total' => 10, 'in_band' => 2],
        ['name' => 'Op J', 'total' => 10, 'in_band' => 1],
    ];

    foreach ($specs as $spec) {
        seedOperator($siding, $spec);
    }

    $result = $this->scoreboard->handle((int) $siding->id, 7);

    expect($result)->toHaveKeys(['top', 'bottom']);
    expect($result['top'])->toHaveCount(5);
    expect($result['bottom'])->toHaveCount(5);

    // Top sorted descending by accuracy
    expect($result['top'][0]['accuracy_pct'])->toBeGreaterThanOrEqual($result['top'][1]['accuracy_pct']);
    expect($result['top'][1]['accuracy_pct'])->toBeGreaterThanOrEqual($result['top'][2]['accuracy_pct']);

    // Bottom sorted ascending by accuracy
    expect($result['bottom'][0]['accuracy_pct'])->toBeLessThanOrEqual($result['bottom'][1]['accuracy_pct']);
    expect($result['bottom'][1]['accuracy_pct'])->toBeLessThanOrEqual($result['bottom'][2]['accuracy_pct']);

    // Best operator is Op A at 100 %
    expect($result['top'][0]['name'])->toBe('Op A');
    expect($result['top'][0]['accuracy_pct'])->toBe(100.0);

    // Worst operator is Op J at 10 % (bottom[0] = ascending order = worst first)
    expect($result['bottom'][0]['name'])->toBe('Op J');
    expect($result['bottom'][0]['accuracy_pct'])->toBe(10.0);

    // Each entry has all required keys
    foreach ([...$result['top'], ...$result['bottom']] as $entry) {
        expect($entry)->toHaveKeys(['name', 'wagons', 'accuracy_pct', 'rs_caused']);
    }
});

it('excludes operators with fewer than 10 wagons', function (): void {
    $siding = Siding::factory()->create();

    // One "noise" operator with only 9 wagons — all perfect (100 % accuracy)
    seedOperator($siding, ['name' => 'Noise Op', 'total' => 9, 'in_band' => 9]);

    // Fill with 8 eligible operators so we have real top/bottom
    $specs = [
        ['name' => 'Op A', 'total' => 10, 'in_band' => 10],
        ['name' => 'Op B', 'total' => 10, 'in_band' => 9],
        ['name' => 'Op C', 'total' => 10, 'in_band' => 8],
        ['name' => 'Op D', 'total' => 10, 'in_band' => 7],
        ['name' => 'Op E', 'total' => 10, 'in_band' => 6],
        ['name' => 'Op F', 'total' => 10, 'in_band' => 5],
        ['name' => 'Op G', 'total' => 10, 'in_band' => 4],
        ['name' => 'Op H', 'total' => 10, 'in_band' => 3],
    ];
    foreach ($specs as $spec) {
        seedOperator($siding, $spec);
    }

    $result = $this->scoreboard->handle((int) $siding->id, 7);

    $names = array_column($result['top'], 'name');
    expect($names)->not->toContain('Noise Op');
});

it('returns empty top and bottom when no eligible operators', function (): void {
    $siding = Siding::factory()->create();

    // Only one operator with 5 wagons — below the noise floor of 10
    seedOperator($siding, ['name' => 'Tiny Op', 'total' => 5, 'in_band' => 5]);

    $result = $this->scoreboard->handle((int) $siding->id, 7);

    expect($result)->toBe(['top' => [], 'bottom' => []]);
});

it('computes rs_caused per operator from overload', function (): void {
    $siding = Siding::factory()->create();

    $pcc = 68.0;

    // Seed 9 filler operators (10 wagons each, all perfect) so we have ≥ 10 eligible
    // operators total and Driver X appears in the scoreboard (bottom side).
    foreach (range(1, 9) as $n) {
        seedOperator($siding, ['name' => "Filler {$n}", 'total' => 10, 'in_band' => 10]);
    }

    // Driver X: 10 wagons — 8 perfect (at exactly PCC = in-band), 2 overloaded by 1 MT each.
    // accuracy = 8/10 = 80 %, rs_caused = 2 MT × Rs 1000/MT = Rs 2000
    $rake = Rake::factory()->create([
        'siding_id' => $siding->id,
        'loading_date' => Carbon::now()->subDays(1)->toDateString(),
    ]);

    for ($i = 0; $i < 10; $i++) {
        $wagon = Wagon::factory()->create([
            'rake_id' => $rake->id,
            'pcc_weight_mt' => $pcc,
        ]);

        // First 8 at exactly PCC (100 % of PCC — in band), last 2 overloaded by 1 MT each
        $loaded = $i < 8 ? $pcc : $pcc + 1.0;

        WagonLoading::factory()->create([
            'rake_id' => $rake->id,
            'wagon_id' => $wagon->id,
            'loader_operator_name' => 'Driver X',
            'loaded_quantity_mt' => $loaded,
            'cc_capacity_mt' => null,
        ]);
    }

    $result = $this->scoreboard->handle((int) $siding->id, 7);

    $driver = collect($result['top'])->firstWhere('name', 'Driver X')
        ?? collect($result['bottom'])->firstWhere('name', 'Driver X');

    expect($driver)->not->toBeNull();
    expect($driver['rs_caused'])->toBe(2000.0);
});
