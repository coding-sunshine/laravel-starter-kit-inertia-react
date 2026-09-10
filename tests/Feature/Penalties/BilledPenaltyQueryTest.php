<?php

declare(strict_types=1);

use App\Actions\GeneratePenaltyInsightsAction;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RrPenaltySnapshot;
use App\Models\Siding;
use App\Services\PrismService;
use App\Support\BilledPenaltyQuery;
use Prism\Prism\Facades\Prism;

function billedPenalty(Siding $siding, string $code, float $amount, string $loadingDate): RrPenaltySnapshot
{
    $rake = Rake::factory()->create([
        'siding_id' => $siding->id,
        'loading_date' => $loadingDate,
    ]);

    return RrPenaltySnapshot::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => $code,
        'amount' => $amount,
    ]);
}

it('aggregates billed penalties from snapshots, not the legacy penalties table', function (): void {
    $siding = Siding::factory()->create(['name' => 'Alpha Siding']);
    PenaltyType::factory()->create(['code' => 'DEM', 'name' => 'Demurrage']);

    billedPenalty($siding, 'DEM', 10000, now()->subDays(3)->toDateString());
    billedPenalty($siding, 'DEM', 5000, now()->subDays(2)->toDateString());
    billedPenalty($siding, 'POL1', 2500, now()->subDay()->toDateString());

    $query = fn () => BilledPenaltyQuery::between([$siding->id], now()->subDays(30));

    expect(BilledPenaltyQuery::total($query()))->toBe(17500.0);

    $byType = BilledPenaltyQuery::totalsByType($query());
    expect($byType[0])->toMatchArray(['type' => 'Demurrage', 'count' => 2, 'total' => 15000.0]);

    $bySiding = BilledPenaltyQuery::totalsBySiding($query());
    expect($bySiding)->toHaveCount(1)
        ->and($bySiding[0]['siding'])->toBe('Alpha Siding')
        ->and($bySiding[0]['count'])->toBe(3);

    expect(BilledPenaltyQuery::dated($query()))->toHaveCount(3);
});

it('excludes penalties outside the window and other sidings', function (): void {
    $siding = Siding::factory()->create();
    $other = Siding::factory()->create();

    billedPenalty($siding, 'DEM', 1000, now()->subDays(2)->toDateString());
    billedPenalty($siding, 'DEM', 9999, now()->subMonths(6)->toDateString());
    billedPenalty($other, 'DEM', 7777, now()->subDays(2)->toDateString());

    expect(BilledPenaltyQuery::total(BilledPenaltyQuery::between([$siding->id], now()->subDays(30))))
        ->toBe(1000.0);
});

it('returns null instead of prompting the model when no penalties are billed', function (): void {
    $siding = Siding::factory()->create();

    config(['prism.providers.openrouter.api_key' => 'test-key']);
    $fake = Prism::fake([]);

    $insights = (new GeneratePenaltyInsightsAction(app(PrismService::class)))->handle([$siding->id]);

    expect($insights)->toBeNull();
    $fake->assertCallCount(0);
});
