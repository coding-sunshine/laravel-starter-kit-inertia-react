<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Siding;
use App\Models\User;
use App\Models\VehicleWorkorder;
use Database\Seeders\Essential\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    $org = Organization::factory()->create();
    $this->siding = Siding::query()->create([
        'organization_id' => $org->id,
        'name' => 'Unique WO Siding',
        'code' => 'UWO',
        'location' => 'Test',
        'station_code' => 'UW',
        'is_active' => true,
    ]);

    $this->user = User::factory()->withoutTwoFactor()->create(['onboarding_completed' => true]);
    $this->user->assignRole('admin');
    $this->user->givePermissionTo(['sections.transport.create', 'sections.transport.update']);
    $this->user->sidings()->attach($this->siding->id, ['is_primary' => true]);
});

function validWorkorderPayload(Siding $siding, string $vehicleNo): array
{
    return [
        'siding_id' => $siding->id,
        'vehicle_no' => $vehicleNo,
        'rcd_pin_no' => 'P1',
        'transport_name' => 'Trans',
        'wo_no_2' => 'W2',
        'work_order_date' => '2026-06-02',
        'tyres' => 6,
        'tare_weight' => 10000,
    ];
}

test('store rejects duplicate vehicle_no', function (): void {
    VehicleWorkorder::query()->create(validWorkorderPayload($this->siding, 'DUP01'));

    $this->actingAs($this->user)
        ->post(route('vehicle-workorders.store'), validWorkorderPayload($this->siding, 'DUP01'))
        ->assertInvalid(['vehicle_no']);

    expect(VehicleWorkorder::query()->where('vehicle_no', 'DUP01')->count())->toBe(1);
})->skip(fn (): bool => ! extension_loaded('pdo_sqlite'), 'phpunit.xml uses sqlite :memory:; pdo_sqlite extension required.');

test('update rejects vehicle_no taken by another workorder but allows keeping own', function (): void {
    VehicleWorkorder::query()->create(validWorkorderPayload($this->siding, 'TAKEN1'));
    $mine = VehicleWorkorder::query()->create(validWorkorderPayload($this->siding, 'MINE1'));

    $this->actingAs($this->user)
        ->put(route('vehicle-workorders.update', ['vehicle_workorder' => $mine]), validWorkorderPayload($this->siding, 'TAKEN1'))
        ->assertInvalid(['vehicle_no']);

    $this->actingAs($this->user)
        ->put(route('vehicle-workorders.update', ['vehicle_workorder' => $mine]), validWorkorderPayload($this->siding, 'MINE1'))
        ->assertValid(['vehicle_no']);
})->skip(fn (): bool => ! extension_loaded('pdo_sqlite'), 'phpunit.xml uses sqlite :memory:; pdo_sqlite extension required.');
