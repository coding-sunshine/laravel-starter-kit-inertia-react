<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Penalty;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\User;
use Database\Seeders\Essential\RakeManagementRolePermissionSeeder;

beforeEach(function (): void {
    $this->seed(RakeManagementRolePermissionSeeder::class);
});

test('unauthenticated user cannot access penalties index', function (): void {
    $this->get(route('penalties.index'))
        ->assertRedirect();
});

test('authenticated user with sidings sees penalties index with chart data', function (): void {
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

    $rake = Rake::query()->create([
        'siding_id' => $siding->id,
        'rake_number' => 'TEST-001',
        'state' => 'pending',
        'wagon_count' => 10,
    ]);

    Penalty::query()->create([
        'rake_id' => $rake->id,
        'penalty_type' => 'DEM',
        'penalty_amount' => 5000,
        'penalty_status' => 'incurred',
        'penalty_date' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('penalties.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('penalties/index')
            ->has('tableData')
            ->has('tableData.data')
            ->has('chartData')
            ->has('chartData.byType')
            ->has('chartData.bySiding')
            ->has('chartData.monthlyTrend')
        );
});
