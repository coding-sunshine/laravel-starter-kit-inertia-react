<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Siding;
use App\Models\TransportWorkOrderRegistration;
use App\Models\User;
use App\Models\VehicleWorkorder;
use Database\Seeders\Essential\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('transporters index exposes assigned vehicle count by transporter name and wo_no on same siding', function (): void {
    $org = Organization::factory()->create();
    $sidingA = Siding::query()->create([
        'organization_id' => $org->id,
        'name' => 'Assigned Count Siding A',
        'code' => 'ACA',
        'location' => 'Test',
        'station_code' => 'AA',
        'is_active' => true,
    ]);
    $sidingB = Siding::query()->create([
        'organization_id' => $org->id,
        'name' => 'Assigned Count Siding B',
        'code' => 'ACB',
        'location' => 'Test',
        'station_code' => 'AB',
        'is_active' => true,
    ]);

    $user = User::factory()->withoutTwoFactor()->create(['onboarding_completed' => true]);
    $user->assignRole('admin');
    $user->givePermissionTo('sections.transport.view');
    $user->sidings()->attach($sidingA->id, ['is_primary' => true]);

    $transport = 'AssignedFleet '.Str::uuid()->toString();
    $woPrimary = 'D/'.Str::uuid()->toString();

    $registration = TransportWorkOrderRegistration::factory()
        ->forSiding($sidingA)
        ->create([
            'transporter_name' => $transport,
            'work_order_no_1' => $woPrimary,
            'work_order_no_2' => null,
            'email' => 'assigned-'.Str::uuid()->toString().'@example.test',
        ]);

    $workorderPayload = [
        'transport_name' => $transport,
        'wo_no' => $woPrimary,
        'work_order_date' => '2025-06-02',
        'tyres' => 6,
        'tare_weight' => 10000,
    ];

    VehicleWorkorder::query()->create(array_merge($workorderPayload, [
        'siding_id' => $sidingA->id,
        'vehicle_no' => 'ASG01',
        'rcd_pin_no' => 'P1',
    ]));

    VehicleWorkorder::query()->create(array_merge($workorderPayload, [
        'siding_id' => $sidingA->id,
        'vehicle_no' => 'ASG02',
        'rcd_pin_no' => 'P2',
    ]));

    VehicleWorkorder::query()->create(array_merge($workorderPayload, [
        'siding_id' => $sidingB->id,
        'vehicle_no' => 'ASG03',
        'rcd_pin_no' => 'P3',
    ]));

    VehicleWorkorder::query()->create(array_merge($workorderPayload, [
        'siding_id' => $sidingA->id,
        'vehicle_no' => 'ASG04',
        'rcd_pin_no' => 'P4',
        'wo_no' => 'OTHER-WO',
    ]));

    $this->actingAs($user)
        ->get(route('vehicle-workorders.index', ['view' => 'transporters']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('VehicleWorkorders/Index')
            ->where('transportWorkOrderRegistrations.data', function ($rows) use ($registration): bool {
                foreach ($rows as $row) {
                    if ((int) ($row['id'] ?? 0) === $registration->id) {
                        return (int) ($row['assigned_vehicle_workorders_count'] ?? -1) === 2;
                    }
                }

                return false;
            })
        );
});

test('assigned vehicle count matches wo_no to registration work_order_no_2 when present', function (): void {
    $org = Organization::factory()->create();
    $siding = Siding::query()->create([
        'organization_id' => $org->id,
        'name' => 'WO2 Match Siding',
        'code' => 'WO2',
        'location' => 'Test',
        'station_code' => 'WW',
        'is_active' => true,
    ]);

    $user = User::factory()->withoutTwoFactor()->create(['onboarding_completed' => true]);
    $user->assignRole('admin');
    $user->givePermissionTo('sections.transport.view');
    $user->sidings()->attach($siding->id, ['is_primary' => true]);

    $transport = 'WO2Fleet '.Str::uuid()->toString();
    $wo2 = 'W2/'.Str::uuid()->toString();

    $registration = TransportWorkOrderRegistration::factory()
        ->forSiding($siding)
        ->create([
            'transporter_name' => $transport,
            'work_order_no_1' => null,
            'work_order_no_2' => $wo2,
            'email' => 'wo2-'.Str::uuid()->toString().'@example.test',
        ]);

    VehicleWorkorder::query()->create([
        'siding_id' => $siding->id,
        'vehicle_no' => 'W2V1',
        'rcd_pin_no' => 'P1',
        'transport_name' => $transport,
        'wo_no' => $wo2,
        'work_order_date' => '2025-06-02',
        'tyres' => 6,
        'tare_weight' => 10000,
    ]);

    $this->actingAs($user)
        ->get(route('vehicle-workorders.index', ['view' => 'transporters']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('VehicleWorkorders/Index')
            ->where('transportWorkOrderRegistrations.data', function ($rows) use ($registration): bool {
                foreach ($rows as $row) {
                    if ((int) ($row['id'] ?? 0) === $registration->id) {
                        return (int) ($row['assigned_vehicle_workorders_count'] ?? -1) === 1;
                    }
                }

                return false;
            })
        );
});
