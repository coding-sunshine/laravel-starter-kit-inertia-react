<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Siding;
use App\Models\VehicleWorkorder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

final class ImportTareWeightFromExcelCommand extends Command
{
    private const REMARKS_ON_CREATE = 'new Record Created';

    protected $signature = 'vehicle-workorders:import-tare-weight-from-excel
                            {--file=database/excel/TareWeight Data.xlsx : Path to the XLSX file}
                            {--siding=1 : siding_id for newly created vehicle workorders}';

    protected $description = 'Import tare weight from Excel: update existing vehicle_workorders by Truck No, or create rows with the given siding_id. All DB changes run in one transaction (rolled back on any error).';

    public function handle(): int
    {
        $file = (string) $this->option('file');
        $path = str_starts_with($file, '/') ? $file : base_path($file);

        if (! File::exists($path)) {
            $this->error(sprintf('File not found: %s', $path));

            return self::FAILURE;
        }

        // Maatwebsite Excel::toArray() loads the full workbook with formatting; that is often very slow on large files.
        // PhpSpreadsheet with readDataOnly skips styles and is typically much faster for data imports.
        if (! $this->output->isQuiet()) {
            $this->comment(sprintf(
                '[%s] Reading spreadsheet (only cell values; this step can take 30-90s on large files)...',
                now()->toDateTimeString(),
            ));
        }

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);

        $spreadsheet = $reader->load($path);
        $sheetRows = $spreadsheet->getActiveSheet()->toArray();
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        if (! $this->output->isQuiet()) {
            $this->comment(sprintf(
                '[%s] Spreadsheet loaded (%d row(s) on first sheet).',
                now()->toDateTimeString(),
                count($sheetRows),
            ));
        }

        if ($sheetRows === []) {
            $this->warn('Spreadsheet is empty.');

            return self::SUCCESS;
        }
        $headerRow = $sheetRows[0] ?? [];
        $dataRows = array_slice($sheetRows, 1);

        $truckCol = $this->resolveColumnIndex($headerRow, 'Truck No');
        if ($truckCol === null) {
            $this->error('Could not find a "Truck No" column in the first row.');

            return self::FAILURE;
        }

        $twCol = $this->resolveColumnIndex($headerRow, 'TW1');
        if ($twCol === null) {
            $this->error('Could not find a "TW1" column in the first row.');

            return self::FAILURE;
        }

        $transportCol = $this->resolveColumnIndex($headerRow, 'Transporter Name');

        /** @var array<string, array{tare_weight: int, transport_name: string|null}> $lastByTruck Last row in file wins per vehicle_no. */
        $lastByTruck = [];
        $rowsWithTruckButInvalidTw = 0;
        foreach ($dataRows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $rawTruck = $row[$truckCol] ?? null;
            $vehicleNo = is_string($rawTruck) || is_numeric($rawTruck) ? mb_trim((string) $rawTruck) : '';
            if ($vehicleNo === '') {
                continue;
            }

            $rawTw = $row[$twCol] ?? null;
            $parsed = $this->parseTareWeight($rawTw);
            if ($parsed === null) {
                $rowsWithTruckButInvalidTw++;

                continue;
            }

            $transportName = null;
            if ($transportCol !== null) {
                $rawTransport = $row[$transportCol] ?? null;
                $transportName = is_string($rawTransport) || is_numeric($rawTransport)
                    ? mb_trim((string) $rawTransport)
                    : null;
                if ($transportName === '') {
                    $transportName = null;
                }
            }

            $lastByTruck[$vehicleNo] = [
                'tare_weight' => $parsed,
                'transport_name' => $transportName,
            ];
        }

        if ($lastByTruck === []) {
            $this->warn('No rows with Truck No and a valid TW1 value.');

            return self::SUCCESS;
        }

        $sidingId = (int) $this->option('siding');
        if ($sidingId < 1) {
            $this->error('Option --siding must be a positive integer.');

            return self::FAILURE;
        }

        if (! Siding::query()->whereKey($sidingId)->exists()) {
            $this->error(sprintf('No siding found with id %d.', $sidingId));

            return self::FAILURE;
        }

        $updated = 0;
        $created = 0;
        $totalTrucks = count($lastByTruck);

        $this->newLine();
        $this->info(sprintf(
            '[%s] Import started — %d unique truck(s) to process.',
            now()->toDateTimeString(),
            $totalTrucks,
        ));

        try {
            DB::transaction(function () use ($lastByTruck, $sidingId, &$updated, &$created, $totalTrucks): void {
                $current = 0;
                foreach ($lastByTruck as $vehicleNo => $payload) {
                    $current++;
                    $tareWeight = $payload['tare_weight'];
                    $transportName = $payload['transport_name'];

                    $latest = VehicleWorkorder::query()
                        ->where('vehicle_no', $vehicleNo)
                        ->latest('id')
                        ->first();

                    if ($latest !== null) {
                        if (! $this->output->isQuiet()) {
                            $this->comment(sprintf(
                                '[%d/%d] Updating vehicle_no=%s (id %d)',
                                $current,
                                $totalTrucks,
                                $vehicleNo,
                                $latest->id,
                            ));
                        }

                        $latest->update([
                            'tare_weight' => $tareWeight,
                        ]);

                        $updated++;
                    } else {
                        if (! $this->output->isQuiet()) {
                            $this->comment(sprintf(
                                '[%d/%d] Creating vehicle_no=%s (new row, siding_id=%d)',
                                $current,
                                $totalTrucks,
                                $vehicleNo,
                                $sidingId,
                            ));
                        }

                        VehicleWorkorder::query()->create([
                            'siding_id' => $sidingId,
                            'vehicle_no' => $vehicleNo,
                            'transport_name' => $transportName,
                            'tare_weight' => $tareWeight,
                            'remarks' => self::REMARKS_ON_CREATE,
                        ]);

                        $created++;
                    }
                }
            });
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('Import failed; all database changes were rolled back.');
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info(sprintf(
            '[%s] Import finished — updated: %d, created: %d.',
            now()->toDateTimeString(),
            $updated,
            $created,
        ));
        if ($rowsWithTruckButInvalidTw > 0) {
            $this->warn(sprintf('Data rows with Truck No but missing or invalid TW1 (not applied): %d', $rowsWithTruckButInvalidTw));
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<int, mixed>  $headerRow
     */
    private function resolveColumnIndex(array $headerRow, string $target): ?int
    {
        $normalizedTarget = mb_strtolower(mb_trim($target));

        foreach ($headerRow as $index => $cell) {
            $label = is_string($cell) || is_numeric($cell) ? mb_trim((string) $cell) : '';
            if ($label === '') {
                continue;
            }
            if (mb_strtolower($label) === $normalizedTarget) {
                return (int) $index;
            }
        }

        return null;
    }

    private function parseTareWeight(mixed $raw): ?int
    {
        if ($raw === null) {
            return null;
        }
        if (is_string($raw)) {
            $raw = mb_trim($raw);
        }
        if ($raw === '') {
            return null;
        }
        if (is_numeric($raw)) {
            return (int) round((float) $raw);
        }

        return null;
    }
}
