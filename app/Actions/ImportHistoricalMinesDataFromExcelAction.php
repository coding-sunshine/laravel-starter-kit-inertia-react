<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\HistoricalMine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

/**
 * Import historical mines data from PRD Excel — one database row per spreadsheet row.
 *
 * Expected columns (header row): DATE, DISPATCHED TRIPS, DISPATCHED QTY or DISPATCH QTY,
 * RECEIVED TRIPS or REACHED TRIPS, RECEIVED QTY, and optionally COAL PRODUCTION QTY, OB PRODUCTION QTY.
 *
 * The DATE cell is stored in the `month` column as a calendar date (not rolled to month start),
 * except the "Mon-YY to dd/mm/yyyy" range special case, which uses the range end date and remarks.
 */
final readonly class ImportHistoricalMinesDataFromExcelAction
{
    /**
     * @return array{
     *   rows_processed:int,
     *   created:int,
     *   updated:int,
     *   skipped:int,
     *   errors:list<string>
     * }
     */
    public function handle(string $path, ?int $sidingId = null): array
    {
        $stats = [
            'rows_processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (Throwable $e) {
            return [
                ...$stats,
                'errors' => ['Failed to load Excel: '.$e->getMessage()],
            ];
        }

        DB::transaction(function () use ($spreadsheet, &$stats, $sidingId): void {
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $this->importSheet($sheet, $stats, $sidingId);
            }
        });

        return $stats;
    }

    /**
     * @param  array{rows_processed:int,created:int,updated:int,skipped:int,errors:list<string>}  $stats
     */
    private function importSheet(Worksheet $sheet, array &$stats, ?int $sidingId): void
    {
        $highestRow = $sheet->getHighestDataRow();
        if ($highestRow < 2) {
            return;
        }

        $headerRow = $this->detectHeaderRow($sheet);
        if ($headerRow === null) {
            $stats['errors'][] = 'Header row not detected for sheet: '.$sheet->getTitle();

            return;
        }

        $columnMap = $this->buildColumnMap($sheet, $headerRow);
        $required = ['date', 'trips_dispatched', 'dispatched_qty', 'trips_received', 'received_qty'];
        foreach ($required as $key) {
            if (! array_key_exists($key, $columnMap)) {
                $stats['errors'][] = sprintf('Missing required column "%s" on sheet: %s', $key, $sheet->getTitle());

                return;
            }
        }

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $rawDate = $sheet->getCellByColumnAndRow($columnMap['date'], $row)->getFormattedValue();
            $rawDate = is_string($rawDate) ? mb_trim($rawDate) : (string) $rawDate;
            if ($rawDate === '') {
                continue;
            }

            $dateResult = $this->parseEntryDateAndRemarks($rawDate);
            if ($dateResult === null) {
                $stats['skipped']++;

                continue;
            }

            $entryDate = $dateResult['date'];
            $remarks = $dateResult['remarks'];

            $data = [
                'siding_id' => $sidingId,
                'month' => $entryDate->toDateString(),
                'trips_dispatched' => $this->cleanInt($sheet->getCellByColumnAndRow($columnMap['trips_dispatched'], $row)->getFormattedValue()),
                'dispatched_qty' => $this->cleanDecimal2($sheet->getCellByColumnAndRow($columnMap['dispatched_qty'], $row)->getFormattedValue()),
                'trips_received' => $this->cleanInt($sheet->getCellByColumnAndRow($columnMap['trips_received'], $row)->getFormattedValue()),
                'received_qty' => $this->cleanDecimal2($sheet->getCellByColumnAndRow($columnMap['received_qty'], $row)->getFormattedValue()),
                'coal_production_qty' => array_key_exists('coal_production_qty', $columnMap)
                    ? $this->cleanDecimal2($sheet->getCellByColumnAndRow($columnMap['coal_production_qty'], $row)->getFormattedValue())
                    : null,
                'ob_production_qty' => array_key_exists('ob_production_qty', $columnMap)
                    ? $this->cleanDecimal2($sheet->getCellByColumnAndRow($columnMap['ob_production_qty'], $row)->getFormattedValue())
                    : null,
                'remarks' => $remarks,
            ];

            HistoricalMine::query()->create($data);
            $stats['created']++;
            $stats['rows_processed']++;
        }
    }

    /**
     * @return array{date:Carbon,remarks:?string}|null
     */
    private function parseEntryDateAndRemarks(string $rawDate): ?array
    {
        // Special case: "Nov-19 to 01/03/2022" (store the range end date; remarks explain the span)
        if (preg_match('/^(?<start>[A-Za-z]{3}-\d{2,4})\s*to\s*(?<end>\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4})$/i', $rawDate, $matches) === 1) {
            $start = (string) $matches['start'];
            $endRaw = (string) $matches['end'];

            $end = $this->parseIndiaDate($endRaw);
            if ($end === null) {
                return null;
            }

            $endNormalized = $end->format('d/m/Y');

            return [
                'date' => $end->copy()->startOfDay(),
                'remarks' => sprintf('From %s to %s.', $start, $endNormalized),
            ];
        }

        $date = $this->parseIndiaDate($rawDate);
        if ($date === null) {
            // Last chance: Excel serial date stored as string
            if (is_numeric($rawDate)) {
                try {
                    $dt = ExcelDate::excelToDateTimeObject((float) $rawDate);
                    $date = Carbon::instance($dt)->startOfDay();
                } catch (Throwable) {
                    return null;
                }
            } else {
                return null;
            }
        }

        return [
            'date' => $date->copy()->startOfDay(),
            'remarks' => null,
        ];
    }

    private function parseIndiaDate(string $value): ?Carbon
    {
        $normalized = mb_trim($value);
        if ($normalized === '') {
            return null;
        }

        // Normalize separators for dd/mm/yyyy-ish inputs
        $normalized = str_replace(['.'], '/', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        $formats = ['d/m/Y', 'd-m-Y', 'd/m/y', 'd-m-y', 'Y-m-d'];
        foreach ($formats as $format) {
            try {
                $dt = Carbon::createFromFormat($format, $normalized);

                return $dt->startOfDay();
            } catch (Throwable) {
                // continue
            }
        }

        // If the input is "01/04/2022 " etc with accidental slashes, attempt generic parse.
        try {
            return Carbon::parse($normalized)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string,int>
     */
    private function buildColumnMap(Worksheet $sheet, int $headerRow): array
    {
        $map = [];
        $highestColumn = $sheet->getHighestColumn();
        $maxColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        for ($col = 1; $col <= $maxColumnIndex; $col++) {
            $header = $sheet->getCellByColumnAndRow($col, $headerRow)->getFormattedValue();
            $headerText = is_string($header) ? mb_strtoupper(mb_trim($header)) : mb_strtoupper((string) $header);
            if ($headerText === '') {
                continue;
            }

            if (str_contains($headerText, 'DATE')) {
                $map['date'] = $col;
            }

            if (str_contains($headerText, 'DISPATCHED TRIPS')) {
                $map['trips_dispatched'] = $col;
            }

            if (str_contains($headerText, 'DISPATCHED QTY') || str_contains($headerText, 'DISPATCH QTY')) {
                $map['dispatched_qty'] = $col;
            }

            if (str_contains($headerText, 'RECEIVED TRIPS') || str_contains($headerText, 'REACHED TRIPS')) {
                $map['trips_received'] = $col;
            }

            if (str_contains($headerText, 'RECEIVED QTY')) {
                $map['received_qty'] = $col;
            }

            if (str_contains($headerText, 'COAL PRODUCTION QTY')) {
                $map['coal_production_qty'] = $col;
            }

            if (str_contains($headerText, 'OB PRODUCTION QTY')) {
                $map['ob_production_qty'] = $col;
            }
        }

        return $map;
    }

    private function detectHeaderRow(Worksheet $sheet): ?int
    {
        $highestRow = min($sheet->getHighestDataRow(), 50);
        $highestColumn = $sheet->getHighestColumn();
        $maxColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        for ($row = 1; $row <= $highestRow; $row++) {
            $hasDate = false;
            $hasDispatchedTrips = false;
            $hasReceivedTrips = false;

            for ($col = 1; $col <= $maxColumnIndex; $col++) {
                $value = $sheet->getCellByColumnAndRow($col, $row)->getFormattedValue();
                $text = is_string($value) ? mb_strtoupper(mb_trim($value)) : mb_strtoupper((string) $value);

                if ($text === '') {
                    continue;
                }

                if (str_contains($text, 'DATE')) {
                    $hasDate = true;
                }
                if (str_contains($text, 'DISPATCHED TRIPS')) {
                    $hasDispatchedTrips = true;
                }
                if (str_contains($text, 'RECEIVED TRIPS') || str_contains($text, 'REACHED TRIPS')) {
                    $hasReceivedTrips = true;
                }
            }

            if ($hasDate && $hasDispatchedTrips && $hasReceivedTrips) {
                return $row;
            }
        }

        return null;
    }

    private function cleanInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $text = is_string($value) ? mb_trim($value) : (string) $value;
        if ($text === '' || str_starts_with($text, '=')) {
            return null;
        }

        $text = str_replace(',', '', $text);
        if (! is_numeric($text)) {
            return null;
        }

        return (int) round((float) $text);
    }

    private function cleanDecimal2(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $text = is_string($value) ? mb_trim($value) : (string) $value;
        if ($text === '' || str_starts_with($text, '=')) {
            return null;
        }

        $text = str_replace(',', '', $text);
        if (! is_numeric($text)) {
            return null;
        }

        return round((float) $text, 2);
    }
}
