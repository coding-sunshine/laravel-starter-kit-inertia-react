<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use App\Models\VehicleWorkorder;
use App\Services\ExcelWorkbookReader;
use Illuminate\Database\Seeder;

final class VehicleWorkorderSeeder extends Seeder
{
    private const FILES = [
        'pakur' => 'excel/pakur workload.ods',
        'kurwa' => 'excel/kurwa workload.ods',
        'dumka' => 'excel/dumka workload.ods',
    ];

    private ExcelWorkbookReader $excel;

    public function run(): void
    {
        $this->excel = app(ExcelWorkbookReader::class);

        $base = database_path();
        foreach (self::FILES as $name => $path) {
            if (! is_file($base.'/'.$path)) {
                $this->command?->info("VehicleWorkorderSeeder skipped: database/{$path} not found.");

                return;
            }
        }

        $this->importPakur();
        $this->importKurwa();
        $this->importDumka();
    }

    private function parseDate(mixed $value): ?string
    {
        return $this->excel->parseDate($value);
    }

    private function parseInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * `vehicle_no` is globally unique; the source spreadsheets contain duplicate
     * rows for the same plate, so upsert by `vehicle_no` instead of blind-inserting
     * (which would violate the unique constraint). Rows without a plate are always
     * inserted since the unique index allows multiple NULLs.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function upsertVehicleWorkorder(array $attributes): void
    {
        if ($attributes['vehicle_no'] === null) {
            VehicleWorkorder::create($attributes);

            return;
        }

        VehicleWorkorder::query()->updateOrCreate(
            ['vehicle_no' => $attributes['vehicle_no']],
            $attributes
        );
    }

    private function importPakur()
    {
        $rows = $this->excel->toArray(database_path('excel/pakur workload.ods'));

        foreach ($rows[0] as $index => $row) {

            if ($index === 0) {
                continue;
            }

            $this->upsertVehicleWorkorder([
                'siding_id' => 1,
                'vehicle_no' => $row[1] ?? null,
                'rcd_pin_no' => $row[2] ?? null,
                'transport_name' => $row[3] ?? null,
                'wo_no' => $row[4] ?? null,
                'wo_no_2' => $row[5] ?? null,
                'work_order_date' => $this->parseDate($row[6] ?? null),
                'proprietor_name' => $row[7] ?? null,
                'place' => $row[8] ?? null,
                'address' => $row[9] ?? null,
                'tyres' => $this->parseInteger($row[10] ?? null),
                'tare_weight' => is_numeric($row[11] ?? null) ? (float) $row[11] : null,
                'mobile_no_1' => $row[12] ?? null,
                'mobile_no_2' => $row[13] ?? null,
                'owner_type' => $row[14] ?? null,
                'regd_date' => $this->parseDate($row[15] ?? null),
                'permit_validity_date' => $this->parseDate($row[16] ?? null),
                'tax_validity_date' => $this->parseDate($row[17] ?? null),
                'fitness_validity_date' => $this->parseDate($row[18] ?? null),
                'insurance_validity_date' => $this->parseDate($row[19] ?? null),
                'model' => $row[20] ?? null,
                'remarks' => $row[21] ?? null,
                'local_or_non_local' => $row[22] ?? null,
                'referenced' => $row[23] ?? null,
                'pan_no' => $row[24] ?? null,
                'gst_no' => $row[25] ?? null,
            ]);
        }
    }

    private function importKurwa()
    {
        $rows = $this->excel->toArray(database_path('excel/kurwa workload.ods'));

        foreach ($rows[0] as $index => $row) {

            if ($index === 0) {
                continue;
            }

            $this->upsertVehicleWorkorder([
                'siding_id' => 2,
                'vehicle_no' => $row[1] ?? null,
                'rcd_pin_no' => $row[2] ?? null,
                'transport_name' => $row[3] ?? null,
                'wo_no' => $row[4] ?? null,
                'work_order_date' => $this->parseDate($row[5] ?? null),
                'issued_date' => $this->parseDate($row[6] ?? null),
                'represented_by' => $row[7] ?? null,
                'place' => $row[8] ?? null,
                'address' => $row[9] ?? null,
                'tyres' => $this->parseInteger($row[10] ?? null),
                'tare_weight' => is_numeric($row[11] ?? null) ? (float) $row[11] : null,
                'mobile_no_1' => $row[12] ?? null,
                'mobile_no_2' => $row[13] ?? null,
                'owner_type' => $row[14] ?? null,
                'regd_date' => $this->parseDate($row[15] ?? null),
                'permit_validity_date' => $this->parseDate($row[16] ?? null),
                'tax_validity_date' => $this->parseDate($row[17] ?? null),
                'fitness_validity_date' => $this->parseDate($row[18] ?? null),
                'insurance_validity_date' => $this->parseDate($row[19] ?? null),
                'maker_model' => $row[20] ?? null,
                'make' => $row[21] ?? null,
                'remarks' => $row[22] ?? null,
                'recommended_by' => $row[23] ?? null,
                'local_or_non_local' => $row[24] ?? null,
            ]);
        }
    }

    private function importDumka()
    {
        $rows = $this->excel->toArray(database_path('excel/dumka workload.ods'));

        foreach ($rows[0] as $index => $row) {

            if ($index === 0) {
                continue;
            }

            $this->upsertVehicleWorkorder([
                'siding_id' => 3,
                'vehicle_no' => $row[1] ?? null,
                'rcd_pin_no' => $row[2] ?? null,
                'transport_name' => $row[3] ?? null,
                'wo_no' => $row[4] ?? null,
                'work_order_date' => $this->parseDate($row[5] ?? null),
                'issued_date' => $this->parseDate($row[6] ?? null),
                'represented_by' => $row[7] ?? null,
                'place' => $row[8] ?? null,
                'address' => $row[9] ?? null,
                'tyres' => $this->parseInteger($row[10] ?? null),
                'tare_weight' => is_numeric($row[11] ?? null) ? (float) $row[11] : null,
                'mobile_no_1' => $row[12] ?? null,
                'mobile_no_2' => $row[13] ?? null,
                'owner_type' => $row[14] ?? null,
                'regd_date' => $this->parseDate($row[15] ?? null),
                'permit_validity_date' => $this->parseDate($row[16] ?? null),
                'tax_validity_date' => $this->parseDate($row[17] ?? null),
                'fitness_validity_date' => $this->parseDate($row[18] ?? null),
                'insurance_validity_date' => $this->parseDate($row[19] ?? null),
                'maker_model' => $row[20] ?? null,
                'make' => $row[21] ?? null,
                'remarks' => $row[22] ?? null,
                'recommended_by' => $row[23] ?? null,
                'local_or_non_local' => $row[24] ?? null,
            ]);
        }
    }
}
