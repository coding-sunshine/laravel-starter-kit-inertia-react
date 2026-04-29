<?php

declare(strict_types=1);

use App\Actions\BuildPenaltyChartDataAction;
use App\Models\AppliedPenalty;
use App\Models\Penalty;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RakeCharge;
use App\Models\Siding;
use Illuminate\Http\Request;

it('byType aggregates from AppliedPenalty not Penalty', function (): void {
    $type = PenaltyType::factory()->create(['code' => 'DEM', 'is_active' => true]);
    $rake = Rake::factory()->create();
    $charge = RakeCharge::factory()->create(['rake_id' => $rake->id]);
    AppliedPenalty::factory()->create([
        'penalty_type_id' => $type->id,
        'rake_id' => $rake->id,
        'rake_charge_id' => $charge->id,
        'amount' => 1000,
        'meta' => ['source' => 'demurrage'],
    ]);
    // Old Penalty record — must NOT appear in results
    Penalty::factory()->create(['rake_id' => $rake->id, 'penalty_amount' => 99999]);

    $result = app(BuildPenaltyChartDataAction::class)->handle(new Request);

    expect($result['byType'])->toHaveCount(1);
    expect($result['byType'][0]['name'])->toBe('DEM');
    expect($result['byType'][0]['value'])->toBe(1000.0);
});

it('bySiding aggregates by siding name', function (): void {
    $siding = Siding::factory()->create(['name' => 'Dumka']);
    $type = PenaltyType::factory()->create(['code' => 'DEM', 'is_active' => true]);
    $rake = Rake::factory()->create(['siding_id' => $siding->id]);
    $charge = RakeCharge::factory()->create(['rake_id' => $rake->id]);
    AppliedPenalty::factory()->create([
        'penalty_type_id' => $type->id,
        'rake_id' => $rake->id,
        'rake_charge_id' => $charge->id,
        'amount' => 5000,
        'meta' => ['source' => 'demurrage'],
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
