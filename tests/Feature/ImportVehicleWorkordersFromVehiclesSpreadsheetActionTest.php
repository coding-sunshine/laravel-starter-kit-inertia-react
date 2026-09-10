<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\ImportVehicleWorkordersFromVehiclesSpreadsheetAction;
use App\Models\Organization;
use App\Models\Siding;
use App\Models\VehicleWorkorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
final class ImportVehicleWorkordersFromVehiclesSpreadsheetActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organization::factory()->create();
        Siding::factory()->for($org)->create(['code' => 'DUMK', 'station_code' => 'DMK']);
    }

    public function test_creates_vehicle_workorder_with_tare_weight_from_spreadsheet(): void
    {
        $path = sys_get_temp_dir().'/vehicle-import-create-'.uniqid('', true).'.xlsx';
        self::writeVehicleSpreadsheetFixture($path, [
            ['AA01AA1234', 'D1', 'Acme Logistics', '', '12.5'],
        ]);

        $action = app(ImportVehicleWorkordersFromVehiclesSpreadsheetAction::class);
        $stats = $action->handle($path);

        $this->assertSame(1, $stats['created']);
        $this->assertSame(0, $stats['updated']);
        $this->assertSame(0, $stats['skipped']);
        $this->assertSame(0, $stats['tare_weight_null_or_non_positive']);
        $this->assertSame([], $stats['tare_weight_issue_rows']);

        $row = VehicleWorkorder::query()->where('vehicle_no', 'AA01AA1234')->firstOrFail();

        $this->assertSame('Acme Logistics', $row->transport_name);
        $this->assertSame(12.5, (float) $row->tare_weight);

        unlink($path);
    }

    public function test_updates_mapped_fields_without_changing_tare_weight(): void
    {
        $sidingId = (int) Siding::query()->where('code', 'DUMK')->value('id');

        VehicleWorkorder::query()->create([
            'siding_id' => $sidingId,
            'vehicle_no' => 'BB01BB9999',
            'transport_name' => 'Old Transport',
            'tare_weight' => 88.88,
            'wo_no' => 'D77',
        ]);

        $path = sys_get_temp_dir().'/vehicle-import-update-'.uniqid('', true).'.xlsx';
        self::writeVehicleSpreadsheetFixture($path, [
            ['BB01BB9999', 'D88', 'New Transport', '', '1.23'],
        ]);

        $action = app(ImportVehicleWorkordersFromVehiclesSpreadsheetAction::class);
        $stats = $action->handle($path);

        $this->assertSame(0, $stats['created']);
        $this->assertSame(1, $stats['updated']);
        $this->assertSame(0, $stats['skipped']);
        $this->assertSame(0, $stats['tare_weight_null_or_non_positive']);
        $this->assertSame([], $stats['tare_weight_issue_rows']);

        $row = VehicleWorkorder::query()->where('vehicle_no', 'BB01BB9999')->firstOrFail();

        $this->assertSame('New Transport', $row->transport_name);
        $this->assertSame('D88', $row->wo_no);
        $this->assertSame(88.88, (float) $row->tare_weight);

        unlink($path);
    }

    public function test_first_spreadsheet_row_wins_when_same_vehicle_no_appears_twice(): void
    {
        $path = sys_get_temp_dir().'/vehicle-import-dup-rows-'.uniqid('', true).'.xlsx';
        self::writeVehicleSpreadsheetFixture($path, [
            ['CC01CC7777', 'D1', 'First Transport', '', '10'],
            ['CC01CC7777', 'D2', 'Second Transport', '', '99'],
        ]);

        $action = app(ImportVehicleWorkordersFromVehiclesSpreadsheetAction::class);
        $stats = $action->handle($path);

        $this->assertSame(1, $stats['created']);
        $this->assertSame(0, $stats['updated']);
        $this->assertSame(1, $stats['skipped']);
        $this->assertSame(0, $stats['tare_weight_null_or_non_positive']);
        $this->assertSame([], $stats['tare_weight_issue_rows']);

        $row = VehicleWorkorder::query()->where('vehicle_no', 'CC01CC7777')->firstOrFail();

        $this->assertSame('First Transport', $row->transport_name);
        $this->assertSame('D1', $row->wo_no);
        $this->assertSame(10.0, (float) $row->tare_weight);

        unlink($path);
    }

    public function test_counts_merged_rows_with_tare_null_zero_or_negative(): void
    {
        $path = sys_get_temp_dir().'/vehicle-import-tare-bad-'.uniqid('', true).'.xlsx';
        self::writeVehicleSpreadsheetFixture($path, [
            ['EE01EE1000', 'D1', 'T1', '', ''],
            ['EE02EE2000', 'D1', 'T2', '', '0'],
            ['EE03EE3000', 'D1', 'T3', '', '-3.5'],
            ['EE04EE4000', 'D1', 'T4', '', '22'],
        ]);

        $action = app(ImportVehicleWorkordersFromVehiclesSpreadsheetAction::class);
        $stats = $action->handle($path);

        $this->assertSame(4, $stats['created']);
        $this->assertSame(0, $stats['updated']);
        $this->assertSame(0, $stats['skipped']);
        $this->assertSame(3, $stats['tare_weight_null_or_non_positive']);

        $this->assertCount(3, $stats['tare_weight_issue_rows']);

        $issues = $stats['tare_weight_issue_rows'];
        $this->assertNull($issues[0]['tare_weight_xlsx']);
        $this->assertNull($issues[0]['tare_weight_database']);
        $this->assertSame(0.0, $issues[1]['tare_weight_xlsx']);
        $this->assertNull($issues[1]['tare_weight_database']);
        $this->assertSame(-3.5, $issues[2]['tare_weight_xlsx']);
        $this->assertNull($issues[2]['tare_weight_database']);

        $byPlate = [];
        foreach ($stats['tare_weight_issue_rows'] as $issue) {
            $byPlate[$issue['vehicle_no']] = $issue['vehicle_workorder_id'];
            $this->assertSame('create', $issue['outcome']);
        }

        $this->assertSame(
            ['EE01EE1000', 'EE02EE2000', 'EE03EE3000'],
            array_keys($byPlate),
        );

        foreach ($byPlate as $plate => $id) {
            $this->assertGreaterThan(0, $id);
            $this->assertSame((int) $id, VehicleWorkorder::query()->where('vehicle_no', $plate)->value('id'));
        }

        unlink($path);
    }

    public function test_tare_issue_row_includes_database_tare_when_update_has_bad_xlsx_tare(): void
    {
        $sidingId = (int) Siding::query()->where('code', 'DUMK')->value('id');

        VehicleWorkorder::query()->create([
            'siding_id' => $sidingId,
            'vehicle_no' => 'FF01FF0001',
            'transport_name' => 'Old',
            'tare_weight' => 77.77,
            'wo_no' => 'D71',
        ]);

        $path = sys_get_temp_dir().'/vehicle-import-tare-issue-update-'.uniqid('', true).'.xlsx';
        self::writeVehicleSpreadsheetFixture($path, [
            ['FF01FF0001', 'D72', 'New Name', '', ''],
        ]);

        $action = app(ImportVehicleWorkordersFromVehiclesSpreadsheetAction::class);
        $stats = $action->handle($path);

        $this->assertSame(1, $stats['tare_weight_null_or_non_positive']);
        $this->assertCount(1, $stats['tare_weight_issue_rows']);

        $issue = $stats['tare_weight_issue_rows'][0];
        $this->assertSame('FF01FF0001', $issue['vehicle_no']);
        $this->assertSame('update', $issue['outcome']);
        $this->assertNull($issue['tare_weight_xlsx']);
        $this->assertSame(77.77, $issue['tare_weight_database']);

        $this->assertSame(77.77, (float) VehicleWorkorder::query()->where('vehicle_no', 'FF01FF0001')->value('tare_weight'));

        unlink($path);
    }

    private static function writeVehicleSpreadsheetFixture(string $path, array $dataRows): void
    {
        $headers = ['REGD. NO', 'WO NO', 'TRANSPORT NAME', 'WORK ORDER DATE', 'TARE WEIGHT'];
        /** @var list<array<int, mixed>> $rows */
        $rows = array_merge([$headers], $dataRows);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($rows);

        (new Xlsx($spreadsheet))->save($path);
    }
}
