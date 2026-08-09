<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RrPenaltySnapshot;
use App\Models\Siding;
use App\Models\User;
use Database\Seeders\Essential\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * @return array{user: User, siding: Siding, rake: Rake}
 */
function setUpPenaltyRegisterUser(): array
{
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
    $user->assignRole('super-admin');
    $user->sidings()->attach($siding->id, ['is_primary' => true]);

    $rake = Rake::query()->create([
        'siding_id' => $siding->id,
        'rake_number' => 'TEST-001',
        'state' => 'pending',
        'wagon_count' => 10,
    ]);

    return ['user' => $user, 'siding' => $siding, 'rake' => $rake];
}

test('unauthenticated user cannot access penalties index', function (): void {
    $this->get(route('penalties.index'))
        ->assertRedirect();
});

test('authenticated user with sidings sees penalties index with chart data', function (): void {
    ['user' => $user, 'rake' => $rake] = setUpPenaltyRegisterUser();

    RrPenaltySnapshot::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'DEM',
        'amount' => 5000,
    ]);

    $this->actingAs($user)
        ->get(route('penalties.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('penalties/index')
            ->has('tableData')
            ->has('tableData.data', 1)
            ->where('tableData.data.0.penalty_code', 'DEM')
            ->where('tableData.data.0.penalty_amount', '5000.00')
            ->has('chartData')
            ->has('chartData.byType')
            ->has('chartData.bySiding')
            ->has('chartData.monthlyTrend')
        );
});

test('penalty type filter options come from penalty_types table', function (): void {
    ['user' => $user] = setUpPenaltyRegisterUser();

    PenaltyType::factory()->create(['code' => 'POL1', 'name' => 'Overloading Level 1', 'is_active' => true]);
    PenaltyType::factory()->create(['code' => 'ENHC', 'name' => 'Enhanced Charge', 'is_active' => true]);

    $columns = null;
    $this->actingAs($user)
        ->get(route('penalties.index'))
        ->assertOk()
        ->assertInertia(function ($page) use (&$columns): void {
            $columns = $page->toArray()['props']['tableData']['columns'];
        });

    $typeColumn = collect($columns)->firstWhere('id', 'penalty_code');

    expect($typeColumn)->not->toBeNull();
    $optionValues = collect($typeColumn['options'])->pluck('value')->all();
    expect($optionValues)->toContain('POL1', 'ENHC');
});

test('date range filter includes rows inside range and excludes rows outside it', function (): void {
    ['user' => $user, 'rake' => $rake] = setUpPenaltyRegisterUser();

    $inRange = RrPenaltySnapshot::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'DEM',
        'amount' => 1000,
    ]);
    $inRange->forceFill(['created_at' => '2026-01-15'])->save();

    $outOfRange = RrPenaltySnapshot::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'DEM',
        'amount' => 2000,
    ]);
    $outOfRange->forceFill(['created_at' => '2025-01-15'])->save();

    $this->actingAs($user)
        ->get(route('penalties.index', ['filter' => ['penalty_date' => 'between:2026-01-01,2026-01-31']]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('penalties/index')
            ->has('tableData.data', 1)
            ->where('tableData.data.0.id', $inRange->id)
        );
});
