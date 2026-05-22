<?php

declare(strict_types=1);

use App\Models\DailyVehicleEntry;
use App\Models\DailyVehicleEntryRollup;
use App\Models\Siding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    if (DB::connection()->getDriverName() !== 'pgsql') {
        $this->markTestSkipped('Rollup aggregate SQL is PostgreSQL-only (see DailyVehicleEntryRollupSql).');
    }
});

it('aggregates road_dispatch daily_vehicle_entries into rollups grouped by entry_date siding and shift', function (): void {
    Carbon::setTestNow('2026-05-18 12:00:00');

    try {
        $siding = Siding::factory()->create();
        $day = '2026-05-18';

        DailyVehicleEntry::query()->create([
            'siding_id' => $siding->id,
            'entry_date' => $day,
            'shift' => 1,
            'entry_type' => DailyVehicleEntry::ENTRY_TYPE_ROAD_DISPATCH,
            'vehicle_no' => 'WB01A0001',
            'e_challan_no' => 'CH-1',
            'gross_wt' => 80,
            'tare_wt' => 30,
            'net_wt' => 50,
            'status' => 'completed',
            'reached_at' => $day.' 08:00:00',
        ]);

        DailyVehicleEntry::query()->create([
            'siding_id' => $siding->id,
            'entry_date' => $day,
            'shift' => 1,
            'entry_type' => DailyVehicleEntry::ENTRY_TYPE_ROAD_DISPATCH,
            'vehicle_no' => 'WB01A0002',
            'e_challan_no' => 'CH-2',
            'gross_wt' => 40,
            'status' => 'draft',
            'reached_at' => $day.' 09:00:00',
        ]);

        DailyVehicleEntry::query()->create([
            'siding_id' => $siding->id,
            'entry_date' => $day,
            'shift' => 1,
            'entry_type' => DailyVehicleEntry::ENTRY_TYPE_RAILWAY_SIDING_EMPTY_WEIGHMENT,
            'vehicle_no' => 'WB01A9999',
            'e_challan_no' => 'RW-1',
            'gross_wt' => 999,
            'status' => 'completed',
            'reached_at' => $day.' 10:00:00',
        ]);

        $this->artisan('rollup:backfill-daily-vehicle-entries', [
            '--from' => $day,
            '--to' => $day,
        ])->assertSuccessful();

        expect(DailyVehicleEntryRollup::query()->count())->toBe(1);

        $rollup = DailyVehicleEntryRollup::query()->firstOrFail();

        expect($rollup->entries_count)->toBe(2)
            ->and($rollup->completed_entries_count)->toBe(1)
            ->and($rollup->pending_entries_count)->toBe(1)
            ->and(round((float) $rollup->completed_net_wt_mt, 2))->toBe(50.0)
            ->and(round((float) $rollup->pending_gross_wt_mt, 2))->toBe(40.0)
            ->and($rollup->shift)->toBe(1)
            ->and((string) $rollup->rollup_day->format('Y-m-d'))->toBe($day);
    } finally {
        Carbon::setTestNow();
    }
});

it('rejects --from/--to outside the current indian fy unless skip flag is set', function (): void {
    Carbon::setTestNow('2026-05-18 12:00:00');

    try {
        $this->artisan('rollup:backfill-daily-vehicle-entries', [
            '--from' => '2020-01-01',
            '--to' => '2020-01-02',
        ])->assertFailed();
    } finally {
        Carbon::setTestNow();
    }
});
