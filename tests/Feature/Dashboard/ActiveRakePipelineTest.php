<?php

declare(strict_types=1);

use App\Http\Controllers\Dashboard\ExecutiveDashboardController;
use App\Models\Rake;
use App\Models\Siding;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function invokeBuildActiveRakePipeline(array $sidingIds): array
{
    $controller = app(ExecutiveDashboardController::class);
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('buildActiveRakePipeline');
    $method->setAccessible(true);

    return $method->invoke($controller, $sidingIds);
}

test('rakes bucket into the four pipeline columns by timestamp', function (): void {
    $siding = Siding::factory()->create();

    $awaitingPlacement = Rake::factory()->create([
        'siding_id' => $siding->id,
        'state' => 'pending',
    ]);

    $loading = Rake::factory()->create([
        'siding_id' => $siding->id,
        'placement_time' => now()->subHours(2),
        'loading_start_time' => now()->subHour(),
    ]);

    $awaitingDispatch = Rake::factory()->create([
        'siding_id' => $siding->id,
        'loading_start_time' => now()->subHours(4),
        'loading_end_time' => now()->subHour(),
    ]);

    $dispatchedToday = Rake::factory()->create([
        'siding_id' => $siding->id,
        'loading_end_time' => now()->subHours(3),
        'dispatch_time' => now()->subMinutes(30),
    ]);

    Rake::factory()->create([
        'siding_id' => $siding->id,
        'dispatch_time' => now()->subDays(2),
    ]);

    $pipeline = invokeBuildActiveRakePipeline([$siding->id]);

    expect($pipeline)->toHaveKeys(['awaiting_placement', 'loading', 'awaiting_dispatch', 'dispatched']);

    expect(collect($pipeline['awaiting_placement'])->pluck('rake_id'))
        ->toContain($awaitingPlacement->id);

    expect(collect($pipeline['loading'])->pluck('rake_id'))
        ->toContain($loading->id);

    expect(collect($pipeline['awaiting_dispatch'])->pluck('rake_id'))
        ->toContain($awaitingDispatch->id);

    expect(collect($pipeline['dispatched'])->pluck('rake_id'))
        ->toContain($dispatchedToday->id);

    $allCardIds = collect($pipeline)->flatten(1)->pluck('rake_id')->all();
    expect($allCardIds)->toHaveCount(4);
});

test('cards expose stage value and label for the frontend', function (): void {
    $siding = Siding::factory()->create();

    Rake::factory()->create([
        'siding_id' => $siding->id,
        'loading_start_time' => now()->subHour(),
    ]);

    $pipeline = invokeBuildActiveRakePipeline([$siding->id]);

    $card = $pipeline['loading'][0] ?? null;

    expect($card)->not->toBeNull()
        ->and($card['stage'])->toBe('loading')
        ->and($card['stage_label'])->toBe('Loading');
});

test('legacy completed and closed rakes are excluded from active pipeline', function (): void {
    $siding = Siding::factory()->create();

    Rake::factory()->create([
        'siding_id' => $siding->id,
        'state' => 'completed',
    ]);

    Rake::factory()->create([
        'siding_id' => $siding->id,
        'state' => 'closed',
    ]);

    $pipeline = invokeBuildActiveRakePipeline([$siding->id]);

    $allCards = collect($pipeline)->flatten(1);
    expect($allCards)->toBeEmpty();
});
