<?php

declare(strict_types=1);

use App\Models\AppliedPenalty;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RakeCharge;
use App\Models\SectionTimer;

beforeEach(function (): void {
    PenaltyType::factory()->create(['code' => 'DEM', 'default_rate' => 225, 'is_active' => true]);
    SectionTimer::factory()->create(['section_name' => 'loading', 'free_minutes' => 300]);
});

it('dry-run outputs CSV diff without writing to DB', function (): void {
    $rake = Rake::factory()->create([
        'placement_time' => now()->subMinutes(480),
        'loading_end_time' => now(),
        'wagon_count' => 10,
    ]);
    $charge = RakeCharge::factory()->create(['rake_id' => $rake->id]);
    AppliedPenalty::factory()->create([
        'rake_id' => $rake->id,
        'penalty_type_id' => PenaltyType::where('code', 'DEM')->first()->id,
        'rake_charge_id' => $charge->id,
        'amount' => 999.99,
        'meta' => ['source' => 'demurrage'],
    ]);

    $this->artisan('penalties:recalculate --dry-run')
        ->expectsOutputToContain('rake_id')
        ->expectsOutputToContain((string) $rake->id)
        ->assertExitCode(0);

    // Amount unchanged — dry run writes nothing
    expect(AppliedPenalty::where('rake_id', $rake->id)->value('amount'))->toBe('999.99');
});

it('applies corrected amounts when not dry-run', function (): void {
    $rake = Rake::factory()->create([
        'placement_time' => now()->subMinutes(480),
        'loading_end_time' => now(),
        'wagon_count' => 10,
    ]);
    $charge = RakeCharge::factory()->create(['rake_id' => $rake->id]);
    AppliedPenalty::factory()->create([
        'rake_id' => $rake->id,
        'penalty_type_id' => PenaltyType::where('code', 'DEM')->first()->id,
        'rake_charge_id' => $charge->id,
        'amount' => 999.99,
        'meta' => ['source' => 'demurrage'],
    ]);

    $this->artisan('penalties:recalculate')->assertExitCode(0);

    // 3 hours × 225 × 1 × 10 wagons = 6750
    $updated = AppliedPenalty::where('rake_id', $rake->id)->first();
    expect((float) $updated->amount)->toBe(6750.0);
    expect($updated->meta['correction_reason'])->toBe('formula_fix_2026-04-29');
});

it('limits to single rake with --rake option', function (): void {
    $rakeA = Rake::factory()->create([
        'placement_time' => now()->subMinutes(480),
        'loading_end_time' => now(),
        'wagon_count' => 10,
    ]);
    $rakeB = Rake::factory()->create([
        'placement_time' => now()->subMinutes(480),
        'loading_end_time' => now(),
        'wagon_count' => 10,
    ]);
    $type = PenaltyType::where('code', 'DEM')->first();
    $chargeA = RakeCharge::factory()->create(['rake_id' => $rakeA->id]);
    $chargeB = RakeCharge::factory()->create(['rake_id' => $rakeB->id]);
    AppliedPenalty::factory()->create(['rake_id' => $rakeA->id, 'penalty_type_id' => $type->id, 'rake_charge_id' => $chargeA->id, 'amount' => 1, 'meta' => ['source' => 'demurrage']]);
    AppliedPenalty::factory()->create(['rake_id' => $rakeB->id, 'penalty_type_id' => $type->id, 'rake_charge_id' => $chargeB->id, 'amount' => 1, 'meta' => ['source' => 'demurrage']]);

    $this->artisan("penalties:recalculate --rake={$rakeA->id}")->assertExitCode(0);

    expect((float) AppliedPenalty::where('rake_id', $rakeB->id)->value('amount'))->toBe(1.0);
});
