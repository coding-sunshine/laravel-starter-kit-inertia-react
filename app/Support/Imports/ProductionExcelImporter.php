<?php

declare(strict_types=1);

namespace App\Support\Imports;

use App\Models\ProductionEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

final class ProductionExcelImporter
{
    /**
     * @return array{
     *   rows_processed:int,
     *   created:int,
     *   skipped:int,
     *   skipped_rows?:list<array{row:int,month:mixed,qty:mixed,reason:string,sheet:string}>,
     *   errors:list<string>
     * }
     */
    public function import(string $path, string $type, ?string $sheetName = null, bool $dryRun = false, bool $logSkipped = false): array
    {
        $stats = [
            'rows_processed' => 0,
            'created' => 0,
            'skipped' => 0,
            'errors' => [],
        ];
        if ($logSkipped) {
            $stats['skipped_rows'] = [];
        }

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (Throwable $e) {
            return [
                ...$stats,
                'errors' => ['Failed to load Excel: '.$e->getMessage()],
            ];
        }

        $sheets = $sheetName !== null && $sheetName !== ''
            ? [$spreadsheet->getSheetByName($sheetName)]
            : [$spreadsheet->getActiveSheet()];

        if (count($sheets) === 1 && $sheets[0] === null) {
            return [
                ...$stats,
                'errors' => ["Sheet not found: {$sheetName}"],
            ];
        }

        DB::transaction(function () use ($sheets, $type, $dryRun, $logSkipped, &$stats): void {
            foreach ($sheets as $sheet) {
                if (! $sheet instanceof Worksheet) {
                    continue;
                }

                $this->importSheet($sheet, $type, $dryRun, $logSkipped, $stats);
            }
        });

        return $stats;
    }

    /**
     * @param  array{rows_processed:int,created:int,skipped:int,errors:list<string>}  $stats
     */
    private function importSheet(Worksheet $sheet, string $type, bool $dryRun, bool $logSkipped, array &$stats): void
    {
        $highestRow = $sheet->getHighestDataRow();
        if ($highestRow < 2) {
            return;
        }

        $headerRow = 1;
        $columnMap = $this->buildColumnMap($sheet, $headerRow);

        foreach (['month', 'qty'] as $required) {
            if (! array_key_exists($required, $columnMap)) {
                $stats['errors'][] = sprintf('Missing required column "%s" on sheet: %s', $required, $sheet->getTitle());

                return;
            }
        }

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $monthCell = $sheet->getCellByColumnAndRow($columnMap['month'], $row);
            $qtyCell = $sheet->getCellByColumnAndRow($columnMap['qty'], $row);

            $monthValue = $monthCell->getValue();
            if ($monthValue === null || (is_string($monthValue) && mb_trim($monthValue) === '')) {
                continue;
            }

            $month = $this->parseMonth($monthValue);
            if ($month === null) {
                $stats['skipped']++;
                $stats['errors'][] = sprintf('Row %d: invalid Month value "%s" (%s)', $row, (string) $monthValue, $sheet->getTitle());
                if ($logSkipped) {
                    $stats['skipped_rows'][] = [
                        'row' => $row,
                        'month' => $monthCell->getFormattedValue(),
                        'qty' => $qtyCell->getFormattedValue(),
                        'reason' => 'invalid_month',
                        'sheet' => $sheet->getTitle(),
                    ];
                }

                continue;
            }

            $qtyValue = $this->readNumericCellValue($qtyCell);
            if ($qtyValue === null) {
                $stats['skipped']++;
                if ($logSkipped) {
                    $stats['skipped_rows'][] = [
                        'row' => $row,
                        'month' => $monthCell->getFormattedValue(),
                        'qty' => $qtyCell->isFormula() ? $qtyCell->getCalculatedValue() : $qtyCell->getFormattedValue(),
                        'reason' => 'invalid_qty',
                        'sheet' => $sheet->getTitle(),
                    ];
                }

                continue;
            }

            $data = [
                'type' => $type,
                // Always store the 1st of the month, even if Excel provides month-end dates.
                'date' => Carbon::create($month->year, $month->month, 1)->toDateString(),
                'trip' => null,
                'qty' => round($qtyValue, 2),
                'siding_id' => null,
                'created_by' => null,
                'updated_by' => null,
            ];

            if (! $dryRun) {
                ProductionEntry::query()->create($data);
            }

            $stats['rows_processed']++;
            $stats['created']++;
        }
    }

    /**
     * @return array{month?:int,qty?:int}
     */
    private function buildColumnMap(Worksheet $sheet, int $headerRow): array
    {
        $map = [];
        $maxColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        for ($col = 1; $col <= $maxColumnIndex; $col++) {
            $header = $sheet->getCellByColumnAndRow($col, $headerRow)->getFormattedValue();
            $headerText = is_string($header) ? mb_strtolower(mb_trim($header)) : mb_strtolower((string) $header);
            if ($headerText === '') {
                continue;
            }

            if ($headerText === 'month') {
                $map['month'] = $col;
            }

            if ($headerText === 'total coal') {
                $map['qty'] = $col;
            }
        }

        return $map;
    }

    private function parseMonth(mixed $value): ?Carbon
    {
        if (is_numeric($value)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject((float) $value);

                return Carbon::instance($dt)->startOfDay();
            } catch (Throwable) {
                return null;
            }
        }

        if (is_string($value)) {
            $text = mb_trim($value);
            if ($text === '') {
                return null;
            }

            try {
                return Carbon::parse($text)->startOfDay();
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }

    private function readNumericCellValue(\PhpOffice\PhpSpreadsheet\Cell\Cell $cell): ?float
    {
        try {
            $value = $cell->isFormula() ? $cell->getCalculatedValue() : $cell->getValue();
        } catch (Throwable) {
            return null;
        }

        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $text = str_replace(',', '', mb_trim($value));
            if ($text === '' || ! is_numeric($text)) {
                return null;
            }

            return (float) $text;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }
}
