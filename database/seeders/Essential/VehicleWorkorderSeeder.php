<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use App\Models\VehicleWorkorder;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

final class VehicleWorkorderSeeder extends Seeder
{
    private const FILES = [
        'pakur' => 'excel/pakur workload.ods',
        'kurwa' => 'excel/kurwa workload.ods',
        'dumka' => 'excel/dumka workload.ods',
    ];

    public function run(): void
    {
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
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            try {
                $date = ExcelDate::excelToDateTimeObject((float) $value);

                return $date->format('Y-m-d');
            } catch (Throwable) {
                return null;
            }
        }
        if (is_string($value)) {
            $parsed = strtotime(mb_trim($value));

            return $parsed !== false ? date('Y-m-d', $parsed) : null;
        }

        return null;
    }

    private function parseInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function importPakur()
    {
        $rows = Excel::toArray([], database_path('excel/pakur workload.ods'));

        foreach ($rows[0] as $index => $row) {

            if ($index === 0) {
                continue;
            }

            VehicleWorkorder::create([
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
        $rows = Excel::toArray([], database_path('excel/kurwa workload.ods'));

        foreach ($rows[0] as $index => $row) {

            if ($index === 0) {
                continue;
            }

            VehicleWorkorder::create([
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
        $rows = Excel::toArray([], database_path('excel/dumka workload.ods'));

        foreach ($rows[0] as $index => $row) {

            if ($index === 0) {
                continue;
            }

            VehicleWorkorder::create([
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
