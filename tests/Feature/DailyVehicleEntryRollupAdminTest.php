<?php

declare(strict_types=1);

use App\Models\DailyVehicleEntry;
use App\Models\DailyVehicleEntryRollup;
use App\Models\Siding;
use App\Models\User;
use Database\Seeders\Essential\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('daily vehicle entry rollups admin requires authentication', function (): void {
    $this->get(route('daily-vehicle-entry-rollups.index'))
        ->assertRedirect();
});

test('daily vehicle entry rollups admin forbids non-super-admin users', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'onboarding_completed' => true,
    ]);
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('daily-vehicle-entry-rollups.index'))
        ->assertForbidden();
});

test('super-admin can view daily vehicle entry rollups index', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'onboarding_completed' => true,
    ]);
    $user->assignRole('super-admin');

    $this->actingAs($user)
        ->get(route('daily-vehicle-entry-rollups.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('DailyVehicleEntryRollups/Index')
            ->has('days.data')
            ->has('filters.date_from')
            ->has('filters.date_to'));
});

test('recalculate rejects dates with no road_dispatch daily_vehicle_entries rows', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'onboarding_completed' => true,
    ]);
    $user->assignRole('super-admin');

    $this->actingAs($user)
        ->post(route('daily-vehicle-entry-rollups.recalculate'), [
            'date' => '2099-01-01',
            'date_from' => '2099-01-01',
            'date_to' => '2099-01-01',
        ])
        ->assertInvalid(['date']);
});

test('super-admin can recalculate vehicle entry rollups for one day when database is postgresql', function (): void {
    if (DB::connection()->getDriverName() !== 'pgsql') {
        $this->markTestSkipped('Rollup aggregate SQL is PostgreSQL-only.');
    }

    $user = User::factory()->withoutTwoFactor()->create([
        'onboarding_completed' => true,
    ]);
    $user->assignRole('super-admin');

    $siding = Siding::factory()->create();

    DailyVehicleEntry::query()->create([
        'siding_id' => $siding->id,
        'entry_date' => '2026-06-02',
        'shift' => 1,
        'entry_type' => DailyVehicleEntry::ENTRY_TYPE_ROAD_DISPATCH,
        'vehicle_no' => 'WB01ROLL1',
        'e_challan_no' => 'CH-R1',
        'gross_wt' => 60,
        'tare_wt' => 20,
        'net_wt' => 40,
        'status' => 'completed',
        'reached_at' => '2026-06-02 12:00:00',
    ]);

    $this->actingAs($user)
        ->post(route('daily-vehicle-entry-rollups.recalculate'), [
            'date' => '2026-06-02',
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-07',
        ])
        ->assertRedirect(route('daily-vehicle-entry-rollups.index', [
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-07',
        ]));

    expect(DailyVehicleEntryRollup::query()->whereDate('rollup_day', '2026-06-02')->count())->toBe(1);
});
