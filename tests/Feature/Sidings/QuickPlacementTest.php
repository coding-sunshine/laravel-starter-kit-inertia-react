<?php

declare(strict_types=1);

use App\Models\Rake;
use App\Models\Siding;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::query()->firstOrCreate(['name' => 'siding_in_charge', 'guard_name' => 'web']);
    Role::query()->firstOrCreate(['name' => 'siding_operator', 'guard_name' => 'web']);
});

it('lets a siding-attached user mark a rake as placed', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->for($siding)->create(['placement_time' => null]);
    $user = User::factory()->create();
    $user->sidings()->attach($siding);
    $user->assignRole('siding_in_charge');

    $this->actingAs($user)
        ->post(route('sidings.quick-placement.store', $siding), [
            'rake_id' => $rake->id,
            'event' => 'placed',
        ])
        ->assertRedirect();

    expect($rake->fresh()->placement_time)->not->toBeNull();
});

it('rejects a user not attached to the siding', function (): void {
    $siding = Siding::factory()->create();
    $otherSiding = Siding::factory()->create();
    $rake = Rake::factory()->for($siding)->create(['placement_time' => null]);
    $user = User::factory()->create();
    $user->sidings()->attach($otherSiding); // attached elsewhere
    $user->assignRole('siding_operator');

    $this->actingAs($user)
        ->post(route('sidings.quick-placement.store', $siding), [
            'rake_id' => $rake->id,
            'event' => 'placed',
        ])
        ->assertForbidden();

    expect($rake->fresh()->placement_time)->toBeNull();
});

it('records loading_end_time when event=released', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->for($siding)->create([
        'placement_time' => now()->subHours(3),
        'loading_end_time' => null,
    ]);
    $user = User::factory()->create();
    $user->sidings()->attach($siding);
    $user->assignRole('siding_in_charge');

    $this->actingAs($user)
        ->post(route('sidings.quick-placement.store', $siding), [
            'rake_id' => $rake->id,
            'event' => 'released',
        ])
        ->assertRedirect();

    expect($rake->fresh()->loading_end_time)->not->toBeNull();
});
