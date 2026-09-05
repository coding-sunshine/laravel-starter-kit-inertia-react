<?php

declare(strict_types=1);

use App\Enums\RakeLifecycleStage;
use App\Models\Rake;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('rake with no timestamps and no state falls back to awaiting placement', function (): void {
    $rake = Rake::factory()->create();

    expect($rake->pipelineStage())->toBe(RakeLifecycleStage::AwaitingPlacement);
});

test('rake with placement_time only is awaiting placement', function (): void {
    $rake = Rake::factory()->create([
        'placement_time' => now()->subHour(),
    ]);

    expect($rake->pipelineStage())->toBe(RakeLifecycleStage::AwaitingPlacement);
});

test('rake with loading_start_time set is loading', function (): void {
    $rake = Rake::factory()->create([
        'placement_time' => now()->subHour(),
        'loading_start_time' => now()->subMinutes(30),
    ]);

    expect($rake->pipelineStage())->toBe(RakeLifecycleStage::Loading);
});

test('rake with loading_end_time set is awaiting dispatch', function (): void {
    $rake = Rake::factory()->create([
        'loading_start_time' => now()->subHours(2),
        'loading_end_time' => now()->subMinutes(15),
    ]);

    expect($rake->pipelineStage())->toBe(RakeLifecycleStage::AwaitingDispatch);
});

test('rake with dispatch_time set but no rr is dispatched', function (): void {
    $rake = Rake::factory()->create([
        'loading_end_time' => now()->subHours(3),
        'dispatch_time' => now()->subHour(),
    ]);

    expect($rake->pipelineStage())->toBe(RakeLifecycleStage::Dispatched);
});

test('rake with dispatch_time and rr_actual_date is delivered', function (): void {
    $rake = Rake::factory()->create([
        'dispatch_time' => now()->subDays(2),
        'rr_actual_date' => now()->subDay(),
    ]);

    expect($rake->pipelineStage())->toBe(RakeLifecycleStage::Delivered);
});

test('legacy state completed maps to delivered when no timestamps', function (): void {
    $rake = Rake::factory()->create([
        'state' => 'completed',
    ]);

    expect($rake->pipelineStage())->toBe(RakeLifecycleStage::Delivered);
});

test('legacy state closed maps to delivered when no timestamps', function (): void {
    $rake = Rake::factory()->create([
        'state' => 'closed',
    ]);

    expect($rake->pipelineStage())->toBe(RakeLifecycleStage::Delivered);
});

test('legacy state delivered maps to delivered', function (): void {
    $rake = Rake::factory()->create([
        'state' => 'delivered',
    ]);

    expect($rake->pipelineStage())->toBe(RakeLifecycleStage::Delivered);
});

test('legacy state in_transit maps to in transit', function (): void {
    $rake = Rake::factory()->create([
        'state' => 'in_transit',
    ]);

    expect($rake->pipelineStage())->toBe(RakeLifecycleStage::InTransit);
});

test('legacy state departed maps to dispatched when no timestamps', function (): void {
    $rake = Rake::factory()->create([
        'state' => 'departed',
    ]);

    expect($rake->pipelineStage())->toBe(RakeLifecycleStage::Dispatched);
});

test('legacy state staged maps to awaiting dispatch', function (): void {
    $rake = Rake::factory()->create([
        'state' => 'staged',
    ]);

    expect($rake->pipelineStage())->toBe(RakeLifecycleStage::AwaitingDispatch);
});

test('legacy state loading_completed maps to awaiting dispatch', function (): void {
    $rake = Rake::factory()->create([
        'state' => 'loading_completed',
    ]);

    expect($rake->pipelineStage())->toBe(RakeLifecycleStage::AwaitingDispatch);
});

test('legacy state loading maps to loading', function (): void {
    $rake = Rake::factory()->create([
        'state' => 'loading',
    ]);

    expect($rake->pipelineStage())->toBe(RakeLifecycleStage::Loading);
});

test('legacy state pending maps to awaiting placement', function (): void {
    $rake = Rake::factory()->create([
        'state' => 'pending',
    ]);

    expect($rake->pipelineStage())->toBe(RakeLifecycleStage::AwaitingPlacement);
});

test('timestamps take precedence over legacy state', function (): void {
    $rake = Rake::factory()->create([
        'state' => 'pending',
        'placement_time' => now()->subHours(2),
        'loading_start_time' => now()->subHour(),
    ]);

    expect($rake->pipelineStage())->toBe(RakeLifecycleStage::Loading);
});

test('unknown legacy state with no timestamps falls back to awaiting placement', function (): void {
    $rake = Rake::factory()->create([
        'state' => 'something_unknown',
    ]);

    expect($rake->pipelineStage())->toBe(RakeLifecycleStage::AwaitingPlacement);
});
