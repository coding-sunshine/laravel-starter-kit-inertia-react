<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\HistoricalMine;
use App\Models\Siding;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

final readonly class ImportSidingDispatchFromExcel
{
    public const string DEFAULT_REMARKS = 'From Total of all siding bcz data was not avlaible for any siding.';

    /**
     * @return array{
     *   months_processed:int,
     *   created:int,
     *   siding_columns_mapped:int,
     *   errors:int,
     *   error_messages:array<int, string>,
     * }
     */
    public function handle(string $path): array
    {
        $stats = [
            'months_processed' => 0,
            'created' => 0,
            'siding_columns_mapped' => 0,
            'errors' => 0,
            'error_messages' => [],
        ];

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheet(0);

        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        /** @var array<int, string> $headers */
        $headers = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $header = mb_trim((string) $sheet->getCellByColumnAndRow($col, 1)->getFormattedValue());
            $headers[$col] = $header;
        }

        $monthCol = $this->findHeaderColumn($headers, 'month');
        if ($monthCol === null) {
            $this->addError($stats, 'Missing required header: Month');

            return $stats;
        }

        $remarksCol = $this->findHeaderColumn($headers, 'remarks');

        $sidingsByLowerName = Siding::query()
            ->get(['id', 'name'])
            ->mapWithKeys(fn (Siding $siding): array => [mb_strtolower(mb_trim((string) $siding->name)) => $siding->id])
            ->all();

        /** @var array<int, array{col:int, siding_id:int, header:string}> $sidingColumns */
        $sidingColumns = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            if ($col === $monthCol || ($remarksCol !== null && $col === $remarksCol)) {
                continue;
            }

            $header = mb_trim((string) ($headers[$col] ?? ''));
            if ($header === '') {
                continue;
            }

            $lookup = mb_strtolower($header);
            $sidingId = $sidingsByLowerName[$lookup] ?? null;
            if ($sidingId === null) {
                $this->addError($stats, sprintf('Unknown siding header "%s" (no matching sidings.name)', $header));

                continue;
            }

            $sidingColumns[] = ['col' => $col, 'siding_id' => (int) $sidingId, 'header' => $header];
        }

        $stats['siding_columns_mapped'] = count($sidingColumns);

        DB::transaction(function () use ($sheet, $highestRow, $monthCol, $remarksCol, $sidingColumns, &$stats): void {
            HistoricalMine::query()->delete();

            for ($row = 2; $row <= $highestRow; $row++) {
                $monthCell = $sheet->getCellByColumnAndRow($monthCol, $row);
                $month = $this->parseMonthCell($monthCell->getValue(), $monthCell->getFormattedValue());
                if ($month === null) {
                    // Treat rows without a parsable month as "not a month row" and skip silently.
                    continue;
                }

                $remarks = null;
                if ($remarksCol !== null) {
                    $rawRemarks = mb_trim((string) $sheet->getCellByColumnAndRow($remarksCol, $row)->getFormattedValue());
                    if (mb_strtolower($rawRemarks) === 'yes') {
                        $remarks = self::DEFAULT_REMARKS;
                    }
                }

                $stats['months_processed']++;

                foreach ($sidingColumns as $sidingColumn) {
                    $qtyCell = $sheet->getCellByColumnAndRow($sidingColumn['col'], $row);
                    $qty = $this->parseQtyCell($qtyCell->getCalculatedValue(), $qtyCell->getFormattedValue());

                    HistoricalMine::query()->create([
                        'month' => $month->toDateString(),
                        'siding_id' => $sidingColumn['siding_id'],
                        'trips_dispatched' => 0,
                        'dispatched_qty' => $qty,
                        'trips_received' => 0,
                        'received_qty' => 0,
                        'coal_production_qty' => 0,
                        'ob_production_qty' => 0,
                        'remarks' => $remarks,
                    ]);

                    $stats['created']++;
                }
            }
        });

        return $stats;
    }

    /**
     * @param  array<int, string>  $headers
     */
    private function findHeaderColumn(array $headers, string $needleLower): ?int
    {
        foreach ($headers as $col => $header) {
            if (mb_strtolower(mb_trim($header)) === $needleLower) {
                return $col;
            }
        }

        return null;
    }

    private function addError(array &$stats, string $message): void
    {
        $stats['errors']++;
        $stats['error_messages'][] = $message;
    }

    private function parseMonthCell(mixed $rawValue, string $formattedValue): ?CarbonInterface
    {
        if (is_numeric($rawValue)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject((float) $rawValue);

                return Carbon::instance($dt)->startOfMonth();
            } catch (Throwable) {
                // fall through
            }
        }

        $value = mb_trim((string) ($formattedValue !== '' ? $formattedValue : $rawValue));
        if ($value === '') {
            return null;
        }

        // 11/19 or 11/2019 (month/year)
        if (preg_match('/^\s*(\d{1,2})\s*[\/\-]\s*(\d{2,4})\s*$/', $value, $m) === 1) {
            $month = (int) $m[1];
            $year = (int) $m[2];
            if ($year < 100) {
                $year += 2000;
            }

            if ($month >= 1 && $month <= 12 && $year >= 1900 && $year <= 2200) {
                return Carbon::create($year, $month, 1)->startOfDay();
            }
        }

        // Handles values like "Nov-19", "November 2019", "2026-03-01", etc.
        try {
            $parsed = Carbon::parse('1 '.$value)->startOfMonth();

            return $parsed->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    private function parseQtyCell(mixed $rawValue, string $formattedValue): float
    {
        if (is_numeric($rawValue)) {
            return (float) $rawValue;
        }

        $value = mb_trim((string) ($formattedValue !== '' ? $formattedValue : $rawValue));
        if ($value === '') {
            return 0.0;
        }

        $normalized = str_replace([',', ' '], '', $value);
        if (! is_numeric($normalized)) {
            return 0.0;
        }

        return (float) $normalized;
    }
}
