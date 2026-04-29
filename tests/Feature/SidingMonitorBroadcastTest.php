<?php

declare(strict_types=1);

use App\Events\WagonWeightUpdated;
use App\Jobs\SyncLoadriteWeightJob;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\User;
use App\Models\Wagon;
use App\Models\WagonLoading;
use Illuminate\Support\Facades\Event;

it('WagonWeightUpdated is dispatched after SyncLoadriteWeightJob updates a wagon', function (): void {
    Event::fake([WagonWeightUpdated::class]);

    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create(['siding_id' => $siding->id, 'state' => 'loading']);
    $wagon = Wagon::factory()->create(['wagon_number' => 1, 'rake_id' => $rake->id]);
    WagonLoading::factory()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $wagon->id,
        'cc_capacity_mt' => 68,
        'weight_source' => 'manual',
        'loadrite_override' => false,
    ]);

    (new SyncLoadriteWeightJob(['Sequence' => 1, 'Weight' => 50.0, 'Timestamp' => now()->toIso8601String()], $siding->id))->handle();

    Event::assertDispatched(WagonWeightUpdated::class, fn ($e) => $e->sidingId === $siding->id && $e->loadriteWeightMt === 50.0);
});

it('authenticated user can subscribe to siding channel when siding exists', function (): void {
    $siding = Siding::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/broadcasting/auth', [
            'channel_name' => "private-siding.{$siding->id}",
            'socket_id' => '1234.5678',
        ])
        ->assertSuccessful();
});

it('unauthenticated user cannot subscribe to siding channel', function (): void {
    $siding = Siding::factory()->create();

    config([
        'broadcasting.default' => 'pusher',
        'broadcasting.connections.pusher.key' => 'test-key',
        'broadcasting.connections.pusher.secret' => 'test-secret',
        'broadcasting.connections.pusher.app_id' => 'test-app-id',
    ]);

    app()->forgetInstance('Illuminate\Broadcasting\BroadcastManager');
    app()->forgetInstance('Illuminate\Contracts\Broadcasting\Broadcaster');

    $this->post('/broadcasting/auth', [
        'channel_name' => "private-siding.{$siding->id}",
        'socket_id' => '1234.5678',
    ])
        ->assertStatus(403);
});
