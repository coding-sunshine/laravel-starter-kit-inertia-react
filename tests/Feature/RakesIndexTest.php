<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\User;
use Database\Seeders\Essential\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('unauthenticated user cannot access rakes index', function (): void {
    $this->get(route('rakes.index'))
        ->assertRedirect();
});

test('authenticated user with sidings can access rakes index without ambiguous column error', function (): void {
    $org = Organization::factory()->create();
    $siding = Siding::query()->create([
        'organization_id' => $org->id,
        'name' => 'Test Siding',
        'code' => 'TEST',
        'location' => 'Test',
        'station_code' => 'TST',
        'is_active' => true,
    ]);

    $user = User::factory()->withoutTwoFactor()->create([
        'onboarding_completed' => true,
    ]);
    $user->assignRole('admin');
    $user->givePermissionTo('sections.rakes.view');
    $user->sidings()->attach($siding->id, ['is_primary' => true]);

    Rake::query()->create([
        'siding_id' => $siding->id,
        'rake_number' => 'TEST-001',
        'state' => 'pending',
        'wagon_count' => 10,
    ]);

    $this->actingAs($user)
        ->get(route('rakes.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('rakes/index')
            ->has('tableData')
            ->has('tableData.data')
        );
});

test('super admin sees all rakes', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'onboarding_completed' => true,
    ]);
    $user->assignRole('super-admin');

    $org = Organization::factory()->create();
    $siding = Siding::query()->create([
        'organization_id' => $org->id,
        'name' => 'Test Siding',
        'code' => 'T2',
        'location' => 'Test',
        'station_code' => 'T2',
        'is_active' => true,
    ]);

    Rake::query()->create([
        'siding_id' => $siding->id,
        'rake_number' => 'SA-001',
        'state' => 'pending',
        'wagon_count' => 10,
        'loading_date' => now()->toDateString(),
    ]);
    Rake::query()->create([
        'siding_id' => $siding->id,
        'rake_number' => 'SA-002',
        'state' => 'loading',
        'wagon_count' => 12,
        'loading_date' => now()->toDateString(),
    ]);

    $this->actingAs($user)
        ->get(route('rakes.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('rakes/index')
            ->has('tableData.data')
            ->where('tableData.data', fn ($data): bool => count($data) === 2)
        );
});
