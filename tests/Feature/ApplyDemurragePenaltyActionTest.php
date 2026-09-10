<?php

declare(strict_types=1);

use App\Actions\ApplyDemurragePenaltyAction;
use App\Models\AppliedPenalty;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\SectionTimer;

beforeEach(function (): void {
    PenaltyType::factory()->create(['code' => 'DEM', 'default_rate' => 225, 'is_active' => true]);
    SectionTimer::factory()->create(['section_name' => 'loading', 'free_minutes' => 300]);
});

it('returns null and removes penalty when placement_time is null', function (): void {
    $rake = Rake::factory()->create(['placement_time' => null, 'loading_end_time' => now()]);
    $result = app(ApplyDemurragePenaltyAction::class)->handle($rake);
    expect($result)->toBeNull();
});

it('returns null and removes penalty when loading_end_time is null', function (): void {
    $rake = Rake::factory()->create(['placement_time' => now()->subHours(2), 'loading_end_time' => null]);
    $result = app(ApplyDemurragePenaltyAction::class)->handle($rake);
    expect($result)->toBeNull();
});

it('returns applied false when rake is within free window', function (): void {
    $rake = Rake::factory()->create([
        'placement_time' => now()->subMinutes(200),
        'loading_end_time' => now(),
        'wagon_count' => 10,
    ]);
    $result = app(ApplyDemurragePenaltyAction::class)->handle($rake);
    expect($result['applied'])->toBeFalse();
    expect(AppliedPenalty::where('rake_id', $rake->id)->where('meta->source', 'demurrage')->exists())->toBeFalse();
});

it('applies tier 1 rate for 3 excess hours with wagon_count multiplier', function (): void {
    // 300 free + 180 excess = 480 total minutes, ceil(180/60)=3 excess hours, tier 1 = 1x
    $rake = Rake::factory()->create([
        'placement_time' => now()->subMinutes(480),
        'loading_end_time' => now(),
        'wagon_count' => 59,
    ]);
    $result = app(ApplyDemurragePenaltyAction::class)->handle($rake);
    expect($result['applied'])->toBeTrue();
    expect($result['chargedHours'])->toBe(3);
    expect($result['rateMultiplier'])->toBe(1);
    // 3 hours × 225 × 1 multiplier × 59 wagons = 39,825
    expect($result['amount'])->toBe(39825.0);
});

it('applies tier 2 rate (2x) for 8 excess hours', function (): void {
    // 300 free + 480 excess = 780 total minutes, 8 excess hours, tier 2 = 2x
    $rake = Rake::factory()->create([
        'placement_time' => now()->subMinutes(780),
        'loading_end_time' => now(),
        'wagon_count' => 10,
    ]);
    $result = app(ApplyDemurragePenaltyAction::class)->handle($rake);
    expect($result['chargedHours'])->toBe(8);
    expect($result['rateMultiplier'])->toBe(2);
    // 8 × 225 × 2 × 10 = 36,000
    expect($result['amount'])->toBe(36000.0);
});

it('applies tier 3 rate (3x) for 15 excess hours', function (): void {
    $rake = Rake::factory()->create([
        'placement_time' => now()->subMinutes(300 + 900),
        'loading_end_time' => now(),
        'wagon_count' => 10,
    ]);
    $result = app(ApplyDemurragePenaltyAction::class)->handle($rake);
    expect($result['chargedHours'])->toBe(15);
    expect($result['rateMultiplier'])->toBe(3);
    // 15 × 225 × 3 × 10 = 101,250
    expect($result['amount'])->toBe(101250.0);
});

it('uses placement_time not loading_start_time for window start', function (): void {
    // placement_time is 10h ago, loading_start_time is 8h ago — window must start at placement_time
    $rake = Rake::factory()->create([
        'placement_time' => now()->subHours(10),
        'loading_start_time' => now()->subHours(8),
        'loading_end_time' => now(),
        'wagon_count' => 1,
    ]);
    $result = app(ApplyDemurragePenaltyAction::class)->handle($rake);
    // 10h total - 5h free = 5h excess, tier 1
    expect($result['chargedHours'])->toBe(5);
});

it('applies tier 4 rate (4x) for 30 excess hours', function (): void {
    $rake = Rake::factory()->create([
        'placement_time' => now()->subMinutes(300 + 1800),
        'loading_end_time' => now(),
        'wagon_count' => 10,
    ]);
    $result = app(ApplyDemurragePenaltyAction::class)->handle($rake);
    expect($result['chargedHours'])->toBe(30);
    expect($result['rateMultiplier'])->toBe(4);
    // 30 × 225 × 4 × 10 = 270,000
    expect($result['amount'])->toBe(270000.0);
});

it('applies tier 5 rate (6x) for 60 excess hours', function (): void {
    $rake = Rake::factory()->create([
        'placement_time' => now()->subMinutes(300 + 3600),
        'loading_end_time' => now(),
        'wagon_count' => 10,
    ]);
    $result = app(ApplyDemurragePenaltyAction::class)->handle($rake);
    expect($result['chargedHours'])->toBe(60);
    expect($result['rateMultiplier'])->toBe(6);
    // 60 × 225 × 6 × 10 = 810,000
    expect($result['amount'])->toBe(810000.0);
});

it('stores expanded meta snapshot', function (): void {
    $rake = Rake::factory()->create([
        'placement_time' => now()->subMinutes(480),
        'loading_end_time' => now(),
        'wagon_count' => 59,
    ]);
    app(ApplyDemurragePenaltyAction::class)->handle($rake);
    $penalty = AppliedPenalty::where('rake_id', $rake->id)->where('meta->source', 'demurrage')->first();
    expect($penalty->meta)->toHaveKey('placement_time')
        ->toHaveKey('loading_end_time')
        ->toHaveKey('wagon_count')
        ->toHaveKey('rate_multiplier')
        ->toHaveKey('base_rate')
        ->toHaveKey('excess_hours');
});
