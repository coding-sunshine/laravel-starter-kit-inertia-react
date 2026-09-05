<?php

declare(strict_types=1);

use App\Actions\BuildPenaltyChartDataAction;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RrPenaltySnapshot;
use App\Models\Siding;
use Illuminate\Http\Request;

it('byType aggregates from rr_penalty_snapshots', function (): void {
    PenaltyType::factory()->create(['code' => 'DEM', 'is_active' => true]);
    $rake = Rake::factory()->create();
    RrPenaltySnapshot::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'DEM',
        'amount' => 1000,
    ]);

    $result = app(BuildPenaltyChartDataAction::class)->handle(new Request);

    expect($result['byType'])->toHaveCount(1);
    expect($result['byType'][0]['name'])->toBe('DEM');
    expect($result['byType'][0]['value'])->toBe(1000.0);
});

it('bySiding aggregates by siding name', function (): void {
    $siding = Siding::factory()->create(['name' => 'Dumka']);
    $rake = Rake::factory()->create(['siding_id' => $siding->id]);
    RrPenaltySnapshot::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'DEM',
        'amount' => 5000,
    ]);

    $result = app(BuildPenaltyChartDataAction::class)->handle(new Request);

    expect($result['bySiding'][0]['name'])->toBe('Dumka');
    expect($result['bySiding'][0]['total'])->toBe(5000.0);
});

it('monthlyTrend returns 12 months with zero-filled gaps', function (): void {
    $result = app(BuildPenaltyChartDataAction::class)->handle(new Request);
    expect($result['monthlyTrend'])->toHaveCount(12);
    expect($result['monthlyTrend'][0])->toHaveKeys(['month', 'total', 'count']);
});

it('honors filter[penalty_date] range and excludes rows outside it', function (): void {
    $rake = Rake::factory()->create();
    $inRange = RrPenaltySnapshot::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'DEM',
        'amount' => 1000,
    ]);
    $inRange->forceFill(['created_at' => '2026-01-15'])->save();
    $outOfRange = RrPenaltySnapshot::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'DEM',
        'amount' => 2000,
    ]);
    $outOfRange->forceFill(['created_at' => '2025-01-15'])->save();

    $request = Request::create('/', 'GET', [
        'filter' => ['penalty_date' => 'between:2026-01-01,2026-01-31'],
    ]);

    $result = app(BuildPenaltyChartDataAction::class)->handle($request);

    expect($result['byType'][0]['value'])->toBe(1000.0);

    // sanity: both rows exist, only in-range one counted
    expect(RrPenaltySnapshot::query()->count())->toBe(2);
    expect($inRange->exists)->toBeTrue();
    expect($outOfRange->exists)->toBeTrue();
});
