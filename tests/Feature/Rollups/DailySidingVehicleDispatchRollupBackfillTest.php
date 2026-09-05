<?php

declare(strict_types=1);

use App\Models\DailySidingVehicleDispatchRollup;
use App\Models\Siding;
use App\Models\SidingVehicleDispatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    if (DB::connection()->getDriverName() !== 'pgsql') {
        $this->markTestSkipped('Rollup aggregate SQL is PostgreSQL-only (see SidingVehicleDispatchRollupSql).');
    }
});

it('aggregates siding_vehicle_dispatches into rollups grouped by calendar day siding and shift tier', function (): void {
    Carbon::setTestNow('2026-05-18 12:00:00');

    try {
        $siding = Siding::factory()->create();
        $day = '2026-05-18';

        foreach ([['1st', 100.5], ['2nd', 200]] as [$shift, $wt]) {
            SidingVehicleDispatch::query()->create([
                'siding_id' => $siding->id,
                'permit_no' => 'P-'.$shift.'-'.uniqid('', true),
                'pass_no' => 'PASS-'.$shift.'-'.uniqid('', true),
                'truck_regd_no' => 'TRK-'.$shift,
                'mineral' => 'COAL',
                'mineral_weight' => $wt,
                'issued_on' => $day.' 10:00:00',
                'shift' => $shift,
            ]);
        }

        $this->artisan('rollup:backfill-daily-siding-vehicle-dispatches', [
            '--from' => $day,
            '--to' => $day,
        ])->assertSuccessful();

        expect(DailySidingVehicleDispatchRollup::query()->count())->toBe(2);

        $shiftOne = DailySidingVehicleDispatchRollup::query()
            ->where('shift_number', 1)
            ->firstOrFail();

        expect($shiftOne->dispatches_count)->toBe(1)
            ->and(round((float) $shiftOne->qty_mineral_mt, 2))->toBe(100.5);

        $shiftTwo = DailySidingVehicleDispatchRollup::query()
            ->where('shift_number', 2)
            ->firstOrFail();

        expect($shiftTwo->dispatches_count)->toBe(1)
            ->and(round((float) $shiftTwo->qty_mineral_mt, 2))->toBe(200.0);
    } finally {
        Carbon::setTestNow();
    }
});

it('rejects --from/--to outside the current indian fy unless skip flag is set', function (): void {
    Carbon::setTestNow('2026-05-18 12:00:00');

    try {
        $this->artisan('rollup:backfill-daily-siding-vehicle-dispatches', [
            '--from' => '2020-01-01',
            '--to' => '2020-01-02',
        ])->assertFailed();
    } finally {
        Carbon::setTestNow();
    }
});
