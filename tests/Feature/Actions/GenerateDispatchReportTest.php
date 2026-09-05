<?php

declare(strict_types=1);

use App\Actions\GenerateDispatchReport;
use App\Models\DailyVehicleEntry;
use App\Models\DispatchReport;
use App\Models\Siding;
use App\Models\VehicleDispatch;
use App\Models\VehicleWorkorder;

function makeDispatch(Siding $siding, string $truck, string $issuedOn, array $overrides = []): VehicleDispatch
{
    return VehicleDispatch::create([
        'siding_id' => $siding->id,
        'serial_no' => 4321,
        'ref_no' => 11,
        'permit_no' => 'PERMIT-'.uniqid(),
        'pass_no' => 'PASS-'.uniqid(),
        'issued_on' => $issuedOn,
        'truck_regd_no' => $truck,
        'mineral' => 'Coal',
        'mineral_weight' => 25.0,
        'shift' => '1st',
        ...$overrides,
    ]);
}

it('numbers trips per truck per day instead of writing the pass serial', function (): void {
    $siding = Siding::factory()->create();

    makeDispatch($siding, 'MH12AB1234', '2026-08-01 06:00:00');
    makeDispatch($siding, 'MH12AB1234', '2026-08-01 14:00:00');
    makeDispatch($siding, 'MH12AB1234', '2026-08-02 06:00:00');
    makeDispatch($siding, 'MH12ZZ9999', '2026-08-01 06:00:00');

    (new GenerateDispatchReport)->handle([$siding->id]);

    $trips = DispatchReport::query()
        ->orderBy('vehicle_dispatch_id')
        ->pluck('trips')
        ->all();

    expect($trips)->toBe([1, 2, 1, 1]);
});

it('fills wo_no and tyres from the workorder for the truck', function (): void {
    $siding = Siding::factory()->create();

    VehicleWorkorder::create([
        'siding_id' => $siding->id,
        'vehicle_no' => 'MH12AB1234',
        'wo_no' => 'WO-NEW',
        'tyres' => 10,
        'tare_weight' => 15.5,
        'transport_name' => 'Acme Roadways',
    ]);

    makeDispatch($siding, 'MH12AB1234', '2026-08-01 06:00:00');

    (new GenerateDispatchReport)->handle([$siding->id]);

    $report = DispatchReport::sole();

    expect($report->wo_no)->toBe('WO-NEW')
        ->and((int) $report->tyres)->toBe(10)
        ->and($report->transport_name)->toBe('Acme Roadways');
});

it('falls back to the same truck and day entry when the challan does not match', function (): void {
    $siding = Siding::factory()->create();

    DailyVehicleEntry::create([
        'siding_id' => $siding->id,
        'entry_date' => '2026-08-01',
        'shift' => '1st',
        'e_challan_no' => 'SOME-OTHER-CHALLAN',
        'vehicle_no' => 'MH12AB1234',
        'gross_wt' => 40.0,
        'tare_wt' => 15.0,
        'wb_no' => 'WB-7',
        'trip_id_no' => 'T-99',
    ]);

    makeDispatch($siding, 'MH12AB1234', '2026-08-01 06:00:00');

    (new GenerateDispatchReport)->handle([$siding->id]);

    $report = DispatchReport::sole();

    expect((float) $report->gross_wt_siding_rec_wt)->toBe(40.0)
        ->and($report->wb)->toBe('WB-7')
        ->and($report->trip_id_no)->toBe('T-99')
        ->and((float) $report->net_wt_siding_rec_wt)->toBe(25.0);
});

it('leaves net weight null when the tare comes from the workorder rather than the weighbridge', function (): void {
    $siding = Siding::factory()->create();

    DailyVehicleEntry::create([
        'siding_id' => $siding->id,
        'entry_date' => '2026-08-01',
        'shift' => '1st',
        'e_challan_no' => 'CHALLAN-1',
        'vehicle_no' => 'MH12AB1234',
        'gross_wt' => 40.0,
        'tare_wt' => null,
    ]);

    VehicleWorkorder::create([
        'siding_id' => $siding->id,
        'vehicle_no' => 'MH12AB1234',
        'wo_no' => 'WO-1',
        'tyres' => 10,
        'tare_weight' => 15.0,
    ]);

    makeDispatch($siding, 'MH12AB1234', '2026-08-01 06:00:00', ['pass_no' => 'CHALLAN-1']);

    (new GenerateDispatchReport)->handle([$siding->id]);

    $report = DispatchReport::sole();

    expect((float) $report->tare_wt)->toBe(15.0)
        ->and($report->net_wt_siding_rec_wt)->toBeNull()
        ->and($report->coal_ton_variation)->toBeNull();
});
