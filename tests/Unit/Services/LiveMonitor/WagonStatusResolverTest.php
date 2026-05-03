<?php

declare(strict_types=1);

use App\Enums\LiveWagonStatus;
use App\Models\RakeWagonLoading;
use App\Models\Wagon;
use App\Services\LiveMonitor\WagonStatusResolver;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function makeWagon(array $attrs = []): Wagon
{
    return Wagon::factory()->create(array_merge([
        'pcc_weight_mt' => 65,
        'tare_weight_mt' => 22,
        'is_unfit' => false,
    ], $attrs));
}

function makeLoading(Wagon $wagon, ?float $loadedMt): ?RakeWagonLoading
{
    if ($loadedMt === null) {
        return null;
    }

    return RakeWagonLoading::query()->create([
        'rake_id' => $wagon->rake_id,
        'wagon_id' => $wagon->id,
        'loaded_quantity_mt' => $loadedMt,
        'weight_source' => 'manual',
    ]);
}

test('unfit wagon resolves to Unfit regardless of load', function (): void {
    $wagon = makeWagon(['is_unfit' => true]);
    $loading = makeLoading($wagon, 70.0);

    expect((new WagonStatusResolver)->resolve($wagon, $loading))
        ->toBe(LiveWagonStatus::Unfit);
});

test('null loading row resolves to Empty', function (): void {
    $wagon = makeWagon();
    expect((new WagonStatusResolver)->resolve($wagon, null))
        ->toBe(LiveWagonStatus::Empty);
});

test('zero load resolves to Empty', function (): void {
    $wagon = makeWagon();
    $loading = makeLoading($wagon, 0.0);

    expect((new WagonStatusResolver)->resolve($wagon, $loading))
        ->toBe(LiveWagonStatus::Empty);
});

test('partial load resolves to Loading', function (): void {
    $wagon = makeWagon(['pcc_weight_mt' => 65]);
    $loading = makeLoading($wagon, 30.0);

    expect((new WagonStatusResolver)->resolve($wagon, $loading))
        ->toBe(LiveWagonStatus::Loading);
});

test('load within tolerance of pcc resolves to Loaded', function (): void {
    $wagon = makeWagon(['pcc_weight_mt' => 65]);
    $loading = makeLoading($wagon, 64.6);

    expect((new WagonStatusResolver)->resolve($wagon, $loading))
        ->toBe(LiveWagonStatus::Loaded);
});

test('load exceeding pcc resolves to Overload', function (): void {
    $wagon = makeWagon(['pcc_weight_mt' => 65]);
    $loading = makeLoading($wagon, 67.5);

    expect((new WagonStatusResolver)->resolve($wagon, $loading))
        ->toBe(LiveWagonStatus::Overload);
});

test('null pcc with positive load resolves to Loading', function (): void {
    $wagon = makeWagon(['pcc_weight_mt' => null]);
    $loading = makeLoading($wagon, 30.0);

    expect((new WagonStatusResolver)->resolve($wagon, $loading))
        ->toBe(LiveWagonStatus::Loading);
});

test('every status returns a non-empty label and color', function (): void {
    foreach (LiveWagonStatus::cases() as $status) {
        expect($status->label())->not->toBeEmpty();
        expect($status->color())->toMatch('/^#[0-9A-Fa-f]{6}$/');
    }
});
