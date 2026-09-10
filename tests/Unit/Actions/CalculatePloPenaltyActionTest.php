<?php

declare(strict_types=1);

use App\Actions\CalculatePloPenaltyAction;
use App\Models\CommodityUtilisationThreshold;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RakeWagonWeighment;
use App\Models\RakeWeighment;
use App\Models\Wagon;

beforeEach(function (): void {
    $this->action = resolve(CalculatePloPenaltyAction::class);
});

it('returns zero amount when total loaded equals chargeable weight', function (): void {
    $rake = Rake::factory()->create(['commodity_grade' => 'G2']);
    CommodityUtilisationThreshold::factory()->create([
        'commodity_grade' => 'G2',
        'utilisation_threshold' => 0.95,
    ]);
    $wagons = Wagon::factory()->count(58)->create(['rake_id' => $rake->id]);
    $weighment = RakeWeighment::factory()->for($rake)->create();
    foreach ($wagons as $w) {
        RakeWagonWeighment::factory()->for($weighment, 'rakeWeighment')->create([
            'wagon_id' => $w->id,
            'cc_capacity_mt' => 70.0,
            'net_weight_mt' => 66.5, // 95% of 70
        ]);
    }

    $result = $this->action->handle($rake, $weighment);

    expect($result->shortfallMt)->toBe(0.0)
        ->and($result->amount)->toBe(0.0)
        ->and($result->isApplicable())->toBeFalse();
});

it('computes a positive amount when total loaded falls short of chargeable weight', function (): void {
    PenaltyType::factory()->create([
        'code' => 'PLO',
        'name' => 'Penal Loading Overcharge',
        'default_rate' => 100.0,
    ]);

    $rake = Rake::factory()->create(['commodity_grade' => 'G2']);
    CommodityUtilisationThreshold::factory()->create([
        'commodity_grade' => 'G2',
        'utilisation_threshold' => 0.95,
    ]);
    $wagons = Wagon::factory()->count(58)->create(['rake_id' => $rake->id]);
    $weighment = RakeWeighment::factory()->for($rake)->create();
    foreach ($wagons as $w) {
        RakeWagonWeighment::factory()->for($weighment, 'rakeWeighment')->create([
            'wagon_id' => $w->id,
            'cc_capacity_mt' => 70.0,
            'net_weight_mt' => 60.0,
        ]);
    }

    $result = $this->action->handle($rake, $weighment);

    // 58 × (66.5 − 60) = 377 MT shortfall × ₹100 = ₹37,700
    expect(round($result->shortfallMt, 2))->toBe(377.00)
        ->and(round($result->amount, 2))->toBe(37700.00)
        ->and($result->isApplicable())->toBeTrue();
});

it('falls back to default 0.95 utilisation when no row matches the commodity grade', function (): void {
    $rake = Rake::factory()->create(['commodity_grade' => 'NEW_GRADE']);
    $wagons = Wagon::factory()->count(10)->create(['rake_id' => $rake->id]);
    $weighment = RakeWeighment::factory()->for($rake)->create();
    foreach ($wagons as $w) {
        RakeWagonWeighment::factory()->for($weighment, 'rakeWeighment')->create([
            'wagon_id' => $w->id,
            'cc_capacity_mt' => 70.0,
            'net_weight_mt' => 66.5,
        ]);
    }

    $result = $this->action->handle($rake, $weighment);

    // 10 wagons × 70 × 0.95 = 665 MT chargeable; loaded = 665 → no shortfall
    expect($result->chargeableWeightMt)->toBe(665.0)
        ->and($result->shortfallMt)->toBe(0.0);
});
