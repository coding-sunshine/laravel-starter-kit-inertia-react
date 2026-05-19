<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\DeduplicateVehicleWorkordersByVehicleNoAction;
use App\Models\Organization;
use App\Models\Siding;
use App\Models\VehicleWorkorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
final class DeduplicateVehicleWorkordersByVehicleNoActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organization::factory()->create();
        Siding::factory()->for($org)->create(['code' => 'DUMK', 'station_code' => 'DMK']);

        $sidingId = (int) Siding::query()->where('code', 'DUMK')->value('id');

        VehicleWorkorder::query()->create([
            'siding_id' => $sidingId,
            'vehicle_no' => 'DUPTEST1',
            'wo_no' => 'D1',
        ]);

        VehicleWorkorder::query()->create([
            'siding_id' => $sidingId,
            'vehicle_no' => 'DUPTEST1',
            'wo_no' => 'D2',
        ]);

        VehicleWorkorder::query()->create([
            'siding_id' => $sidingId,
            'vehicle_no' => 'UNIQ1',
            'wo_no' => 'D3',
        ]);
    }

    public function test_dry_run_reports_counts_without_deleting(): void
    {
        $action = app(DeduplicateVehicleWorkordersByVehicleNoAction::class);
        $stats = $action->handle(true);

        $this->assertSame(1, $stats['duplicate_plate_groups']);
        $this->assertSame(1, $stats['rows_removed']);
        $this->assertSame(3, VehicleWorkorder::query()->count());
    }

    public function test_dedupe_keeps_newest_id_per_vehicle_no(): void
    {
        $keepId = (int) VehicleWorkorder::query()->where('vehicle_no', 'DUPTEST1')->orderByDesc('id')->value('id');

        $action = app(DeduplicateVehicleWorkordersByVehicleNoAction::class);
        $stats = $action->handle(false);

        $this->assertSame(1, $stats['duplicate_plate_groups']);
        $this->assertSame(1, $stats['rows_removed']);

        $this->assertSame(2, VehicleWorkorder::query()->count());
        $this->assertSame(1, VehicleWorkorder::query()->where('vehicle_no', 'DUPTEST1')->count());

        $row = VehicleWorkorder::query()->where('vehicle_no', 'DUPTEST1')->firstOrFail();

        $this->assertSame($keepId, (int) $row->id);
        $this->assertSame('D2', $row->wo_no);
    }
}
