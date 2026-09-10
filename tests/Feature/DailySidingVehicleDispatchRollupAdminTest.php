<?php

declare(strict_types=1);

use App\Models\DailySidingVehicleDispatchRollup;
use App\Models\Siding;
use App\Models\SidingVehicleDispatch;
use App\Models\User;
use Database\Seeders\Essential\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('daily siding dispatch rollups admin requires authentication', function (): void {
    $this->get(route('daily-siding-dispatch-rollups.index'))
        ->assertRedirect();
});

test('daily siding dispatch rollups admin forbids non-super-admin users', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'onboarding_completed' => true,
    ]);
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('daily-siding-dispatch-rollups.index'))
        ->assertForbidden();
});

test('super-admin can view daily siding dispatch rollups index', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'onboarding_completed' => true,
    ]);
    $user->assignRole('super-admin');

    $this->actingAs($user)
        ->get(route('daily-siding-dispatch-rollups.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('DailySidingDispatchRollups/Index')
            ->has('days.data')
            ->has('filters.date_from')
            ->has('filters.date_to'));
});

test('recalculate rejects dates with no siding_vehicle_dispatches rows', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'onboarding_completed' => true,
    ]);
    $user->assignRole('super-admin');

    $this->actingAs($user)
        ->post(route('daily-siding-dispatch-rollups.recalculate'), [
            'date' => '2099-01-01',
            'date_from' => '2099-01-01',
            'date_to' => '2099-01-01',
        ])
        ->assertInvalid(['date']);
});

test('super-admin can recalculate rollups for one day when database is postgresql', function (): void {
    if (DB::connection()->getDriverName() !== 'pgsql') {
        $this->markTestSkipped('Rollup aggregate SQL is PostgreSQL-only.');
    }

    $user = User::factory()->withoutTwoFactor()->create([
        'onboarding_completed' => true,
    ]);
    $user->assignRole('super-admin');

    $siding = Siding::factory()->create();

    SidingVehicleDispatch::query()->create([
        'siding_id' => $siding->id,
        'permit_no' => 'P-ROLL-'.uniqid('', true),
        'pass_no' => 'PASS-ROLL-'.uniqid('', true),
        'truck_regd_no' => 'TRK1',
        'mineral' => 'COAL',
        'mineral_weight' => 10,
        'issued_on' => '2026-06-01 12:00:00',
        'shift' => '1st',
    ]);

    $this->actingAs($user)
        ->post(route('daily-siding-dispatch-rollups.recalculate'), [
            'date' => '2026-06-01',
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-07',
        ])
        ->assertRedirect(route('daily-siding-dispatch-rollups.index', [
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-07',
        ]));

    expect(DailySidingVehicleDispatchRollup::query()->whereDate('issued_on_date', '2026-06-01')->count())->toBe(1);
});
