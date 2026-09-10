<?php

declare(strict_types=1);

use App\Events\RakeWagonLoadingUpdated;
use App\Models\Rake;
use App\Models\RakeWagonLoading;
use App\Models\Siding;
use App\Models\Wagon;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Event;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('event broadcasts on both rake-load and siding channels', function (): void {
    Event::fake();

    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create(['siding_id' => $siding->id]);
    $wagon = Wagon::factory()->create(['rake_id' => $rake->id, 'pcc_weight_mt' => 65]);
    $loading = RakeWagonLoading::query()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $wagon->id,
        'loaded_quantity_mt' => 30,
        'weight_source' => 'manual',
    ]);

    event(new RakeWagonLoadingUpdated($rake, $loading, 'updated'));

    Event::assertDispatched(RakeWagonLoadingUpdated::class, function (RakeWagonLoadingUpdated $e) use ($rake, $siding): bool {
        $channels = $e->broadcastOn();
        $names = array_map(fn (PrivateChannel $c): string => $c->name, $channels);

        return in_array('private-rake-load.'.$rake->id, $names, true)
            && in_array('private-siding.'.$siding->id, $names, true);
    });
});

test('event payload includes derived live status fields', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create(['siding_id' => $siding->id]);
    $wagon = Wagon::factory()->create(['rake_id' => $rake->id, 'pcc_weight_mt' => 65, 'is_unfit' => false]);
    $loading = RakeWagonLoading::query()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $wagon->id,
        'loaded_quantity_mt' => 70,
        'weight_source' => 'manual',
    ]);

    $event = new RakeWagonLoadingUpdated($rake->fresh(), $loading->fresh(), 'updated');
    $payload = $event->broadcastWith();

    expect($payload)->toHaveKeys([
        'rake_id', 'siding_id', 'rake_number', 'action',
        'wagon_loading', 'wagon_id',
        'live_status', 'live_status_label', 'live_status_color', 'broadcast_at',
    ]);
    expect($payload['live_status'])->toBe('overload');
});
