<?php

declare(strict_types=1);

use App\Models\DailyVehicleEntry;
use App\Models\Siding;
use App\Models\User;
use App\Models\VehicleDispatch;
use Database\Seeders\Essential\RolesAndPermissionsSeeder;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function reconciliationReportUser(): User
{
    return User::factory()->withoutTwoFactor()->create([
        'onboarding_completed' => true,
        'access_to_siding_shift_data' => true,
    ]);
}

test('reconciliation report requires access to all siding shift data', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'onboarding_completed' => true,
        'access_to_siding_shift_data' => false,
    ]);
    $siding = Siding::factory()->create(['code' => 'PKUR']);

    $this->actingAs($user)
        ->getJson(route('vehicle-dispatch.reconciliation-report', [
            'siding_id' => $siding->id,
            'from' => '2026-05-20',
            'to' => '2026-05-20',
        ]))
        ->assertForbidden();
});

test('reconciliation report requires authentication', function (): void {
    $this->getJson(route('vehicle-dispatch.reconciliation-report', [
        'siding_id' => 1,
        'from' => '2026-05-19',
        'to' => '2026-05-20',
    ]))->assertUnauthorized();
});

test('reconciliation report rejects range over max span days', function (): void {
    $user = reconciliationReportUser();
    $siding = Siding::factory()->create(['code' => 'PKUR']);

    $this->actingAs($user)
        ->getJson(route('vehicle-dispatch.reconciliation-report', [
            'siding_id' => $siding->id,
            'from' => '2026-01-01',
            'to' => '2026-05-20',
        ]))
        ->assertUnprocessable();
});

test('reconciliation report rejects disallowed siding code', function (): void {
    $user = reconciliationReportUser();
    $siding = Siding::factory()->create(['code' => 'OTHER']);

    $this->actingAs($user)
        ->getJson(route('vehicle-dispatch.reconciliation-report', [
            'siding_id' => $siding->id,
            'from' => '2026-05-20',
            'to' => '2026-05-20',
        ]))
        ->assertForbidden();
});

test('reconciliation report returns dispatch received and in transit metrics', function (): void {
    $user = reconciliationReportUser();
    $siding = Siding::factory()->create(['code' => 'PKUR', 'name' => 'Pakur']);
    $date = '2026-05-20';

    VehicleDispatch::query()->create([
        'siding_id' => $siding->id,
        'permit_no' => 'P1',
        'pass_no' => 'PASS-1',
        'truck_regd_no' => 'WB01A0001',
        'mineral' => 'COAL',
        'mineral_weight' => 25.5,
        'issued_on' => $date.' 10:00:00',
        'shift' => '2nd',
    ]);
    VehicleDispatch::query()->create([
        'siding_id' => $siding->id,
        'permit_no' => 'P2',
        'pass_no' => 'PASS-2',
        'truck_regd_no' => 'WB01A0002',
        'mineral' => 'COAL',
        'mineral_weight' => 30.0,
        'issued_on' => $date.' 10:30:00',
        'shift' => '2nd',
    ]);

    DailyVehicleEntry::query()->create([
        'siding_id' => $siding->id,
        'entry_date' => $date,
        'shift' => 2,
        'entry_type' => DailyVehicleEntry::ENTRY_TYPE_ROAD_DISPATCH,
        'vehicle_no' => 'WB01A0001',
        'e_challan_no' => 'CH-1',
        'gross_wt' => 50,
        'tare_wt' => 20,
        'net_wt' => 30,
        'status' => 'completed',
        'reached_at' => $date.' 14:00:00',
    ]);
    DailyVehicleEntry::query()->create([
        'siding_id' => $siding->id,
        'entry_date' => $date,
        'shift' => 2,
        'entry_type' => DailyVehicleEntry::ENTRY_TYPE_ROAD_DISPATCH,
        'vehicle_no' => 'WB01A0003',
        'e_challan_no' => 'CH-2',
        'gross_wt' => 40,
        'status' => 'draft',
        'reached_at' => $date.' 15:00:00',
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('vehicle-dispatch.reconciliation-report', [
            'siding_id' => $siding->id,
            'from' => $date,
            'to' => $date,
        ]))
        ->assertOk()
        ->assertJsonPath('siding.code', 'PKUR')
        ->assertJsonPath('from', $date)
        ->assertJsonPath('to', $date);

    $day = collect($response->json('days'))->firstWhere('date', $date);
    expect($day)->not->toBeNull();

    $shift2 = collect($day['shifts'])->firstWhere('shift', 2);
    expect($shift2['dispatch_trips'])->toBe(2)
        ->and($shift2['dispatch_qty'])->toBe(55.5)
        ->and($shift2['received_trips'])->toBe(2)
        ->and($shift2['received_qty'])->toBe(30.0)
        ->and($shift2['in_transit_trips'])->toBe(0)
        ->and($shift2['in_transit_qty'])->toBe(25.5);

    $rangeTotal = $response->json('range_total');
    expect($rangeTotal['stock_updated_mt'])->toBe(30.0)
        ->and($rangeTotal['in_progress_gross_mt'])->toBe(40.0)
        ->and($rangeTotal['dispatch_trips'])->toBe(2)
        ->and($rangeTotal['received_trips'])->toBe(2);
});

test('reconciliation report shows negative in transit when received exceeds dispatch', function (): void {
    $user = reconciliationReportUser();
    $siding = Siding::factory()->create(['code' => 'DUMK']);
    $date = '2026-05-21';

    VehicleDispatch::query()->create([
        'siding_id' => $siding->id,
        'permit_no' => 'P1',
        'pass_no' => 'PASS-A',
        'truck_regd_no' => 'WB01A0099',
        'mineral' => 'COAL',
        'mineral_weight' => 10.0,
        'issued_on' => $date.' 10:00:00',
        'shift' => '2nd',
    ]);

    DailyVehicleEntry::query()->create([
        'siding_id' => $siding->id,
        'entry_date' => $date,
        'shift' => 2,
        'entry_type' => DailyVehicleEntry::ENTRY_TYPE_ROAD_DISPATCH,
        'vehicle_no' => 'WB01A0100',
        'e_challan_no' => 'CH-A',
        'gross_wt' => 50,
        'tare_wt' => 20,
        'net_wt' => 30,
        'status' => 'completed',
        'reached_at' => $date.' 14:00:00',
    ]);
    DailyVehicleEntry::query()->create([
        'siding_id' => $siding->id,
        'entry_date' => $date,
        'shift' => 2,
        'entry_type' => DailyVehicleEntry::ENTRY_TYPE_ROAD_DISPATCH,
        'vehicle_no' => 'WB01A0101',
        'e_challan_no' => 'CH-B',
        'gross_wt' => 40,
        'tare_wt' => 10,
        'net_wt' => 30,
        'status' => 'completed',
        'reached_at' => $date.' 15:00:00',
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('vehicle-dispatch.reconciliation-report', [
            'siding_id' => $siding->id,
            'from' => $date,
            'to' => $date,
        ]))
        ->assertOk();

    $shift2 = collect(collect($response->json('days'))->firstWhere('date', $date)['shifts'])
        ->firstWhere('shift', 2);

    expect($shift2['dispatch_trips'])->toBe(1)
        ->and($shift2['received_trips'])->toBe(2)
        ->and($shift2['in_transit_trips'])->toBe(-1)
        ->and($shift2['in_transit_qty'])->toBe(-50.0);

    expect($response->json('range_total.in_transit_trips'))->toBe(-1)
        ->and($response->json('range_total.in_transit_qty'))->toBe(-50.0);
});

test('reconciliation report range total nets positive and negative in transit', function (): void {
    $user = reconciliationReportUser();
    $siding = Siding::factory()->create(['code' => 'PKUR']);
    $date = '2026-05-22';

    VehicleDispatch::query()->create([
        'siding_id' => $siding->id,
        'permit_no' => 'P1',
        'pass_no' => 'PASS-1',
        'truck_regd_no' => 'WB01A0001',
        'mineral' => 'COAL',
        'mineral_weight' => 100.0,
        'issued_on' => $date.' 10:00:00',
        'shift' => '1st',
    ]);

    VehicleDispatch::query()->create([
        'siding_id' => $siding->id,
        'permit_no' => 'P2',
        'pass_no' => 'PASS-2',
        'truck_regd_no' => 'WB01A0002',
        'mineral' => 'COAL',
        'mineral_weight' => 10.0,
        'issued_on' => $date.' 18:00:00',
        'shift' => '3rd',
    ]);

    DailyVehicleEntry::query()->create([
        'siding_id' => $siding->id,
        'entry_date' => $date,
        'shift' => 3,
        'entry_type' => DailyVehicleEntry::ENTRY_TYPE_ROAD_DISPATCH,
        'vehicle_no' => 'WB01A0099',
        'e_challan_no' => 'CH-1',
        'gross_wt' => 50,
        'tare_wt' => 5,
        'net_wt' => 45,
        'status' => 'completed',
        'reached_at' => $date.' 20:00:00',
    ]);
    DailyVehicleEntry::query()->create([
        'siding_id' => $siding->id,
        'entry_date' => $date,
        'shift' => 3,
        'entry_type' => DailyVehicleEntry::ENTRY_TYPE_ROAD_DISPATCH,
        'vehicle_no' => 'WB01A0100',
        'e_challan_no' => 'CH-2',
        'gross_wt' => 50,
        'tare_wt' => 5,
        'net_wt' => 45,
        'status' => 'completed',
        'reached_at' => $date.' 21:00:00',
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('vehicle-dispatch.reconciliation-report', [
            'siding_id' => $siding->id,
            'from' => $date,
            'to' => $date,
        ]))
        ->assertOk();

    $day = collect($response->json('days'))->firstWhere('date', $date);
    $shift1 = collect($day['shifts'])->firstWhere('shift', 1);
    $shift3 = collect($day['shifts'])->firstWhere('shift', 3);

    expect($shift1['in_transit_qty'])->toBe(100.0)
        ->and($shift3['in_transit_qty'])->toBe(-80.0)
        ->and($response->json('range_total.in_transit_qty'))->toBe(20.0)
        ->and($response->json('range_total.in_transit_trips'))->toBe(0);
});
