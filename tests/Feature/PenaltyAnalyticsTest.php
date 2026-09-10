<?php

declare(strict_types=1);

use App\Http\Controllers\RailwayReceipts\PenaltyController;
use App\Models\Rake;
use App\Models\RrPenaltySnapshot;
use App\Models\Siding;
use Illuminate\Http\Request;

/**
 * Calls the private builder methods directly via reflection rather than the full
 * analytics() endpoint. analytics() also invokes several untouched legacy
 * Penalty-model builders (buildDisputeAnalysis, buildPenaltyTypeTrend, ...) that
 * hardcode MySQL-only SQL (DATEDIFF/YEAR/MONTH) unrelated to this rr_penalty_snapshots
 * migration and unsupported by sqlite — a pre-existing gap, out of scope here.
 */
function callPenaltyControllerMethod(string $method, array $sidingIds, Request $request): array
{
    $controller = app(PenaltyController::class);
    $reflection = new ReflectionMethod($controller, $method);

    return $reflection->invoke($controller, $sidingIds, $request);
}

it('buildAnalyticsSummary totals rr_penalty_snapshots honoring filter[penalty_date]', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create(['siding_id' => $siding->id]);

    $inRange = RrPenaltySnapshot::factory()->create(['rake_id' => $rake->id, 'penalty_code' => 'DEM', 'amount' => 1000]);
    $inRange->forceFill(['created_at' => '2026-01-15'])->save();

    $outOfRange = RrPenaltySnapshot::factory()->create(['rake_id' => $rake->id, 'penalty_code' => 'DEM', 'amount' => 2000]);
    $outOfRange->forceFill(['created_at' => '2025-01-15'])->save();

    $request = Request::create('/', 'GET', ['filter' => ['penalty_date' => 'between:2026-01-01,2026-01-31']]);

    $summary = callPenaltyControllerMethod('buildAnalyticsSummary', [$siding->id], $request);

    expect($summary['total_penalties'])->toBe(1);
    expect($summary['total_amount'])->toBe(1000.0);
    expect($summary['avg_penalty'])->toBe(1000.0);
});

it('buildByType groups by penalty_code from rr_penalty_snapshots', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create(['siding_id' => $siding->id]);
    RrPenaltySnapshot::factory()->create(['rake_id' => $rake->id, 'penalty_code' => 'DEM', 'amount' => 1000]);

    $byType = callPenaltyControllerMethod('buildByType', [$siding->id], new Request);

    expect($byType)->toHaveCount(1);
    expect($byType[0]['name'])->toBe('DEM');
    expect($byType[0]['value'])->toBe(1000.0);
});

it('buildBySiding groups by siding name from rr_penalty_snapshots', function (): void {
    $siding = Siding::factory()->create(['name' => 'Test Siding']);
    $rake = Rake::factory()->create(['siding_id' => $siding->id]);
    RrPenaltySnapshot::factory()->create(['rake_id' => $rake->id, 'penalty_code' => 'DEM', 'amount' => 5000]);

    $bySiding = callPenaltyControllerMethod('buildBySiding', [$siding->id], new Request);

    expect($bySiding[0]['name'])->toBe('Test Siding');
    expect($bySiding[0]['total'])->toBe(5000.0);
});
