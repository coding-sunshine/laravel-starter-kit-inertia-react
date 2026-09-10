<?php

declare(strict_types=1);

use App\Actions\ApplyWeighmentPenaltiesAction;
use App\Models\AppliedPenalty;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RakeCharge;
use App\Models\RakeWagonWeighment;
use App\Models\RakeWeighment;
use App\Models\Wagon;

beforeEach(function (): void {
    PenaltyType::factory()->create(['code' => 'POL1', 'default_rate' => 1500.0, 'is_active' => true]);
    $this->action = resolve(ApplyWeighmentPenaltiesAction::class);
});

it('charges POL1 on net minus CC, ignoring a corrupted over_load_mt column', function (): void {
    $rake = Rake::factory()->create();
    $weighment = RakeWeighment::factory()->for($rake)->create();
    $wagon = Wagon::factory()->create();

    RakeWagonWeighment::factory()->for($weighment, 'rakeWeighment')->create([
        'wagon_id' => $wagon->id,
        'cc_capacity_mt' => 65.00,
        'net_weight_mt' => 65.99,
        'over_load_mt' => 99.00, // decimal-shifted by the importer
    ]);

    $this->action->handle($rake, $weighment);

    $row = AppliedPenalty::query()->where('rake_id', $rake->id)->sole();

    expect((float) $row->quantity)->toBe(0.99)
        ->and((float) $row->amount)->toBe(1485.00);
});

it('clears weighment penalties and the charge total when a corrected weighment has no overload', function (): void {
    $rake = Rake::factory()->create();
    $weighment = RakeWeighment::factory()->for($rake)->create();
    $wagon = Wagon::factory()->create();

    RakeWagonWeighment::factory()->for($weighment, 'rakeWeighment')->create([
        'wagon_id' => $wagon->id,
        'cc_capacity_mt' => 65.00,
        'net_weight_mt' => 67.00,
    ]);

    $this->action->handle($rake, $weighment);

    expect(AppliedPenalty::query()->where('rake_id', $rake->id)->count())->toBe(1);

    $weighment->rakeWagonWeighments()->update(['net_weight_mt' => 64.00]);
    $this->action->handle($rake, $weighment->fresh());

    $charge = RakeCharge::query()
        ->where('rake_id', $rake->id)
        ->where('charge_type', 'PENALTY')
        ->where('is_actual_charges', false)
        ->sole();

    expect(AppliedPenalty::query()->where('rake_id', $rake->id)->count())->toBe(0)
        ->and((float) $charge->amount)->toBe(0.0);
});
