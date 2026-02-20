<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\User;
use Database\Seeders\Essential\RakeManagementRolePermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->seed(RakeManagementRolePermissionSeeder::class);
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
    $user->assignRole('siding_operator');
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
            ->has('rakes')
            ->has('rakes.data')
        );
});

test('super admin sees all rakes', function (): void {
    Role::query()->firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

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
    ]);
    Rake::query()->create([
        'siding_id' => $siding->id,
        'rake_number' => 'SA-002',
        'state' => 'loading',
        'wagon_count' => 12,
    ]);

    $this->actingAs($user)
        ->get(route('rakes.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('rakes/index')
            ->has('rakes.data')
            ->where('rakes.data', fn ($data): bool => count($data) === 2)
        );
});
