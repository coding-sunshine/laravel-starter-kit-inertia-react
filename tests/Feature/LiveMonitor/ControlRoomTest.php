<?php

declare(strict_types=1);

use App\Models\Loader;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\User;
use App\Models\Wagon;
use Database\Seeders\Essential\RolesAndPermissionsSeeder;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->siding = Siding::factory()->create();
    $this->rake = Rake::factory()->create([
        'siding_id' => $this->siding->id,
        'rake_number' => 'CR-001',
        'wagon_count' => 5,
        'placement_time' => now()->subMinutes(30),
        'loading_start_time' => now()->subMinutes(20),
        'loading_free_minutes' => 180,
    ]);

    foreach (range(1, 5) as $seq) {
        Wagon::factory()->create([
            'rake_id' => $this->rake->id,
            'wagon_sequence' => $seq,
            'pcc_weight_mt' => 65,
        ]);
    }

    Loader::query()->create([
        'siding_id' => $this->siding->id,
        'loader_name' => 'Loader-1',
        'code' => 'L1',
        'loader_type' => 'wheel-loader',
        'is_active' => true,
    ]);
});

test('unauthenticated user cannot access control room overview', function (): void {
    $this->get(route('control-room.index'))->assertRedirect();
});

test('authenticated user without permission cannot access control room', function (): void {
    $user = User::factory()->withoutTwoFactor()->create(['onboarding_completed' => true]);
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('control-room.index'))
        ->assertForbidden();
});

test('super admin sees control room overview with sidings payload', function (): void {
    $user = User::factory()->withoutTwoFactor()->create(['onboarding_completed' => true]);
    $user->assignRole('super-admin');

    $this->actingAs($user)
        ->get(route('control-room.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('control-room/index')
            ->has('overview')
            ->has('overview.sidings')
            ->has('overview.totals')
            ->has('subscribable_sidings')
        );
});

test('user with access sees only sidings they belong to', function (): void {
    $other = Siding::factory()->create();

    $user = User::factory()->withoutTwoFactor()->create(['onboarding_completed' => true]);
    $user->assignRole('admin');
    $user->givePermissionTo('sections.live_monitor.view');
    $user->sidings()->attach($this->siding->id, ['is_primary' => true]);

    $this->actingAs($user)
        ->get(route('control-room.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('control-room/index')
            ->where('overview.totals.sidings', 1)
            ->where('subscribable_sidings', [$this->siding->id])
        );
});

test('control room show returns deep payload for the rake', function (): void {
    $user = User::factory()->withoutTwoFactor()->create(['onboarding_completed' => true]);
    $user->assignRole('super-admin');

    $this->actingAs($user)
        ->get(route('control-room.show', ['rake' => $this->rake->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('control-room/show')
            ->has('rakeData.rake')
            ->has('rakeData.kpis')
            ->has('rakeData.wagons', 5)
            ->has('rakeData.loaders')
            ->has('rakeData.time_status')
            ->has('rakeData.loading_progress')
            ->where('rakeData.rake.rake_number', 'CR-001')
        );
});

test('user without access to a siding cannot see its rake', function (): void {
    $user = User::factory()->withoutTwoFactor()->create(['onboarding_completed' => true]);
    $user->assignRole('admin');
    $user->givePermissionTo('sections.live_monitor.view');
    // No siding attached.

    $this->actingAs($user)
        ->get(route('control-room.show', ['rake' => $this->rake->id]))
        ->assertForbidden();
});
