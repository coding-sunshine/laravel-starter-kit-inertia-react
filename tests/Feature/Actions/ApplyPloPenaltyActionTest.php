<?php

declare(strict_types=1);

use App\Actions\ApplyPloPenaltyAction;
use App\Events\AppliedPenaltyPersisted;
use App\Models\AppliedPenalty;
use App\Models\CommodityUtilisationThreshold;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RakeCharge;
use App\Models\RakeWagonWeighment;
use App\Models\RakeWeighment;
use App\Models\Wagon;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    PenaltyType::factory()->create(['code' => 'PLO', 'default_rate' => 100.0]);
    CommodityUtilisationThreshold::factory()->create([
        'commodity_grade' => 'G2',
        'utilisation_threshold' => 0.95,
    ]);
    $this->action = resolve(ApplyPloPenaltyAction::class);
});

it('persists an AppliedPenalty row when a shortfall exists', function (): void {
    Event::fake([AppliedPenaltyPersisted::class]);

    $rake = Rake::factory()->create(['commodity_grade' => 'G2']);
    $wagons = Wagon::factory()->count(58)->create();
    $weighment = RakeWeighment::factory()->for($rake)->create();
    foreach ($wagons as $w) {
        RakeWagonWeighment::factory()->for($weighment, 'rakeWeighment')->create([
            'wagon_id' => $w->id,
            'cc_capacity_mt' => 70.0,
            'net_weight_mt' => 60.0,
        ]);
    }

    $this->action->handle($rake, $weighment);

    $row = AppliedPenalty::query()
        ->where('rake_id', $rake->id)
        ->whereHas('penaltyType', fn ($q) => $q->where('code', 'PLO'))
        ->sole();

    expect((float) $row->amount)->toBe(37700.00)
        ->and($row->meta['source'])->toBe('plo')
        ->and((float) $row->meta['shortfall_mt'])->toBe(377.00);

    Event::assertDispatched(AppliedPenaltyPersisted::class, fn ($e) => $e->source === 'plo');
});

it('does not persist a row when there is no shortfall', function (): void {
    $rake = Rake::factory()->create(['commodity_grade' => 'G2']);
    $wagons = Wagon::factory()->count(10)->create();
    $weighment = RakeWeighment::factory()->for($rake)->create();
    foreach ($wagons as $w) {
        RakeWagonWeighment::factory()->for($weighment, 'rakeWeighment')->create([
            'wagon_id' => $w->id,
            'cc_capacity_mt' => 70.0,
            'net_weight_mt' => 66.5, // exactly chargeable
        ]);
    }

    $this->action->handle($rake, $weighment);

    expect(AppliedPenalty::query()->whereHas('penaltyType', fn ($q) => $q->where('code', 'PLO'))->count())->toBe(0);
});

it('updates an existing PLO row instead of duplicating on re-run (idempotent)', function (): void {
    $rake = Rake::factory()->create(['commodity_grade' => 'G2']);
    $wagons = Wagon::factory()->count(58)->create();
    $weighment = RakeWeighment::factory()->for($rake)->create();
    foreach ($wagons as $w) {
        RakeWagonWeighment::factory()->for($weighment, 'rakeWeighment')->create([
            'wagon_id' => $w->id,
            'cc_capacity_mt' => 70.0,
            'net_weight_mt' => 60.0,
        ]);
    }

    $this->action->handle($rake, $weighment);
    $this->action->handle($rake, $weighment);

    expect(AppliedPenalty::query()
        ->whereHas('penaltyType', fn ($q) => $q->where('code', 'PLO'))
        ->where('rake_id', $rake->id)
        ->count()
    )->toBe(1);
});

it('recalculates the parent RakeCharge total when applied', function (): void {
    $rake = Rake::factory()->create(['commodity_grade' => 'G2']);
    $wagons = Wagon::factory()->count(58)->create();
    $weighment = RakeWeighment::factory()->for($rake)->create();
    foreach ($wagons as $w) {
        RakeWagonWeighment::factory()->for($weighment, 'rakeWeighment')->create([
            'wagon_id' => $w->id,
            'cc_capacity_mt' => 70.0,
            'net_weight_mt' => 60.0,
        ]);
    }

    $this->action->handle($rake, $weighment);

    $charge = RakeCharge::query()
        ->where('rake_id', $rake->id)
        ->where('charge_type', 'PENALTY')
        ->where('is_actual_charges', false)
        ->sole();

    expect((float) $charge->amount)->toBeGreaterThanOrEqual(37700.00);
});
