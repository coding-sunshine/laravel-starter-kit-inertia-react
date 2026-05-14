<?php

declare(strict_types=1);

namespace App\Exports;

use Closure;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Styled executive overview table: three period columns (today, month-to-date, FY-to-date)
 * plus an FY Summary pivot block (financial years across columns × metrics rows), same source as dashboard FY Summary.
 *
 * Expects {@see ExecutiveDashboardController::buildExecutiveYesterdayData()} shape.
 */
final class ExecutiveOverviewTableExport implements FromArray, WithEvents
{
    /** @var list<'today'|'month'|'fy'> */
    private const PERIOD_KEYS = ['today', 'month', 'fy'];

    /** Style palette (ARGB) — executive overview export spec. */
    private const CLR_HEADER_BLUE = 'FF1F4E79';

    /** OB / Coal production row labels and values; FY Prod. OB / Prod. Coal. */
    private const CLR_PRODUCTION_FILL = 'FFFFE699';

    /** Trips / Rakes column headers and numeric cells; FY pivot year column headers. */
    private const CLR_TRIPS_RAKES_FILL = 'FFFBE5D6';

    /** Qty column headers and numeric cells. */
    private const CLR_QTY_FILL = 'FFF8CBAD';

    /** Coal / Rake dispatch section headers, siding names, Disp. Coal / Disp. Rake FY rows. */
    private const CLR_DISPATCH_FILL = 'FFB4C7E7';

    /** FY label merge (main grid top-left); kept distinct from production. */
    private const CLR_GRID_FY_CORNER = 'FFFBE5D6';

    private const CLR_WHITE_TEXT = 'FFFFFFFF';

    private const CLR_BLACK = 'FF000000';

    private const CLR_RED_TOTAL = 'FFFF0000';

    /** Indian-style grouping + two decimals for FY summaries. */
    private const FMT_IND_QTY_TWO = '#,##,##0.00';

    /** @var array<string, mixed> */
    private readonly array $data;

    /**
     * @param  array<string, mixed>  $executiveYesterdayData  buildExecutiveYesterdayData output
     */
    public function __construct(
        array $executiveYesterdayData,
    ) {
        $this->data = $executiveYesterdayData;
    }

    public function array(): array
    {
        return [['']];
    }

    /**
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $this->populateSheet($sheet);
            },
        ];
    }

    private function populateSheet(Worksheet $sheet): void
    {
        $payload = $this->buildStyledPayload();

        $row = $this->writeMainGrid($sheet, $payload, 1);
        $this->applyMainGridOutline($sheet, 1, $row);

        $gap = $row + 2;
        /** @var list<array<string, mixed>> $fyRows */
        $fyRows = is_array($this->data['fySummary']['rows'] ?? null)
            ? $this->data['fySummary']['rows']
            : [];
        $bottomEnd = $this->writeFySummaryPivotBlock($sheet, $fyRows, $gap);

        $lastRow = max($row, $bottomEnd);
        $fyColIdx = max(8, Coordinate::columnIndexFromString($this->fyPivotLastColumnLetter($fyRows)));
        $throughLetter = Coordinate::stringFromColumnIndex($fyColIdx);
        $this->autosizeCols($sheet, $throughLetter, $lastRow);
        $sheet->freezePane('C3');
    }

    /**
     * @return array{
     *     fy_label: string,
     *     period_titles: list<string>,
     *     periods: array{today: array{from:string,to:string}, month: array{from:string,to:string}, fy: array{from:string,to:string}},
     *     production: array{ob: array{today: mixed, month: mixed, fy: mixed}, coal: array{today: mixed, month: mixed, fy: mixed}},
     *     road: array{siding_rows: array<int, array{sidingName: string}>, totals: array{today: mixed, month: mixed, fy: mixed}},
     *     rail: array{siding_rows: array<int, array{sidingName: string}>, totals: array{today: mixed, month: mixed, fy: mixed}},
     * }
     */
    private function buildStyledPayload(): array
    {
        $d = $this->data;

        $periods = $d['periods'];

        /** Short labels for the three buckets (today → date, month MTD, FY YTD); data still keyed by period in payload. */
        $periodTitles = ['Date', 'Month', 'Year'];

        return [
            'fy_label' => (string) $d['fyLabel'],
            'period_titles' => $periodTitles,
            'periods' => [
                'today' => $periods['today'],
                'month' => $periods['month'],
                'fy' => $periods['fy'],
            ],
            'production' => [
                'ob' => [
                    'today' => $d['obProduction']['today'],
                    'month' => $d['obProduction']['month'],
                    'fy' => $d['obProduction']['fy'],
                ],
                'coal' => [
                    'today' => $d['coalProduction']['today'],
                    'month' => $d['coalProduction']['month'],
                    'fy' => $d['coalProduction']['fy'],
                ],
            ],
            'road' => [
                'siding_rows' => $d['roadDispatch']['bySiding'],
                'totals' => $d['roadDispatch']['totals'],
            ],
            'rail' => [
                'siding_rows' => $d['railDispatch']['bySiding'],
                'totals' => $d['railDispatch']['totals'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload  from {@see buildStyledPayload()}
     * @return int last row written
     */
    private function writeMainGrid(Worksheet $sheet, array $payload, int $startRow): int
    {
        $r = $startRow;

        $sheet->mergeCells("A{$r}:B".($r + 1));
        $sheet->setCellValue("A{$r}", $payload['fy_label']);
        $this->styleCellRange($sheet, "A{$r}:B".($r + 1), [
            'fill' => self::CLR_GRID_FY_CORNER,
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'bold' => true,
        ]);

        $colStarts = ['C', 'E', 'G'];
        foreach (self::PERIOD_KEYS as $i => $_key) {
            $ca = $colStarts[$i];
            $cb = chr(ord($ca) + 1);
            $title = $payload['period_titles'][$i];

            $sheet->mergeCells("{$ca}{$r}:{$cb}{$r}");
            $sheet->setCellValue("{$ca}{$r}", $title === '' ? '-' : $title);
            $this->styleCellRange($sheet, "{$ca}{$r}:{$cb}{$r}", [
                'fill' => self::CLR_HEADER_BLUE,
                'foreground' => self::CLR_WHITE_TEXT,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'bold' => true,
            ]);
            $sheet->setCellValue("{$ca}".($r + 1), 'Trips / Rakes');
            $sheet->setCellValue("{$cb}".($r + 1), 'Qty');
            $this->styleCellRange($sheet, "{$ca}".($r + 1), [
                'fill' => self::CLR_TRIPS_RAKES_FILL,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'bold' => true,
            ]);
            $this->styleCellRange($sheet, "{$cb}".($r + 1), [
                'fill' => self::CLR_QTY_FILL,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'bold' => true,
            ]);

            foreach ([$ca, $cb] as $cLetter) {
                $sheet->getStyle("{$cLetter}".($r + 1))->getFont()->setSize(9);
            }
        }

        $r += 2;

        $r = $this->writeProductionChunk($sheet, $payload['production'], $r);
        $r = $this->writeRoadChunk($sheet, $payload['road'], $r);
        $r = $this->writeRailChunk($sheet, $payload['rail'], $r);

        return $r - 1;
    }

    /**
     * @param  array{siding_rows: mixed, totals: mixed}  $rail
     */
    private function writeRailChunk(Worksheet $sheet, array $rail, int $startRow): int
    {
        return $this->writeDispatchNamed(
            $sheet,
            $startRow,
            'Rake Dispatch',
            $rail['totals'],
            is_array($rail['siding_rows']) ? $rail['siding_rows'] : [],
            true,
        );
    }

    /**
     * @param  array{siding_rows: mixed, totals: mixed}  $road
     */
    private function writeRoadChunk(Worksheet $sheet, array $road, int $startRow): int
    {
        return $this->writeDispatchNamed(
            $sheet,
            $startRow,
            'Coal Dispatch',
            $road['totals'],
            is_array($road['siding_rows']) ? $road['siding_rows'] : [],
            false,
        );
    }

    /**
     * @param  array<int, array{sidingName?: string}>  $sidings
     * @param  array<string, array{trips?: int, qty?: float, rakes?: int}>  $totals
     */
    private function writeDispatchNamed(
        Worksheet $sheet,
        int $r,
        string $sectionTitle,
        array $totals,
        array $sidings,
        bool $rail,
    ): int {
        $sheet->mergeCells("A{$r}:B{$r}");
        $sheet->setCellValue("A{$r}", $sectionTitle);
        $this->styleCellRange($sheet, "A{$r}:B{$r}", [
            'fill' => self::CLR_DISPATCH_FILL,
            'foreground' => self::CLR_BLACK,
            'bold' => true,
            'horizontal' => Alignment::HORIZONTAL_CENTER,
        ]);
        $sheet->mergeCells("C{$r}:H{$r}");
        $sheet->setCellValue("C{$r}", '');
        $this->styleCellRange($sheet, "C{$r}:H{$r}", [
            'fill' => self::CLR_DISPATCH_FILL,
            'foreground' => self::CLR_BLACK,
        ]);

        $r++;

        foreach ($sidings as $s) {
            $name = (string) ($s['sidingName'] ?? '');
            $sheet->setCellValue("A{$r}", $name);
            $sheet->mergeCells("A{$r}:B{$r}");
            $this->styleCellRange($sheet, "A{$r}:B{$r}", [
                'fill' => self::CLR_DISPATCH_FILL,
                'bold' => false,
                'horizontal' => Alignment::HORIZONTAL_LEFT,
            ]);
            $this->fillPeriodMetricsRow($sheet, $r, $s['totals'] ?? [], $rail);
            $this->fillTripsQtyColumnBackgrounds($sheet, $r);
            $r++;
        }

        $sheet->setCellValue("A{$r}", 'Total');
        $sheet->mergeCells("A{$r}:B{$r}");
        $sheet->getStyle("A{$r}:B{$r}")->getFont()->setColor(new Color(self::CLR_RED_TOTAL))->setBold(true);
        $this->fillPeriodMetricsRow($sheet, $r, $totals, $rail);
        $this->fillTripsQtyColumnBackgrounds($sheet, $r);
        foreach (range('C', 'H') as $cLetter) {
            $sheet->getStyle("{$cLetter}{$r}")->getFont()->setBold(true);
        }
        $r++;

        return $r;
    }

    /**
     * @param  array<string, mixed>  $production
     */
    private function writeProductionChunk(Worksheet $sheet, array $production, int $r): int
    {
        foreach (['ob' => 'OB Production', 'coal' => 'Coal Production'] as $key => $labelRow) {
            $series = $production[$key]
                ?? [];
            $sheet->setCellValue("A{$r}", $labelRow);
            $sheet->mergeCells("A{$r}:B{$r}");
            $this->styleCellRange($sheet, "A{$r}:B{$r}", [
                'fill' => self::CLR_PRODUCTION_FILL,
                'bold' => true,
            ]);
            $this->fillPeriodMetricsRowTripsQty($sheet, $r, is_array($series) ? $series : []);
            $this->fillTripsQtyColumnBackgrounds($sheet, $r);
            $r++;
        }

        return $r;
    }

    /**
     * @param  array<string, array{rakes?: int, qty?: float}>  $byPeriodMap
     */
    private function fillPeriodMetricsRow(
        Worksheet $sheet,
        int $row,
        array $byPeriodMap,
        bool $rail,
    ): void {
        $cols = [['C', 'D'], ['E', 'F'], ['G', 'H']];

        foreach (self::PERIOD_KEYS as $i => $pk) {
            $cellPk = $byPeriodMap[$pk] ?? null;
            if (! is_array($cellPk)) {
                $primary = null;
                $qty = null;
            } elseif ($rail) {
                $primary = isset($cellPk['rakes']) ? (int) $cellPk['rakes'] : null;
                $qty = isset($cellPk['qty']) ? (float) $cellPk['qty'] : null;
            } else {
                $primary = isset($cellPk['trips']) ? (int) $cellPk['trips'] : null;
                $qty = isset($cellPk['qty']) ? (float) $cellPk['qty'] : null;
            }

            [$c1, $c2] = $cols[$i];
            $this->numberCellTripsLike($sheet, "{$c1}{$row}", $primary);
            $this->numberCellQty($sheet, "{$c2}{$row}", $qty);
            foreach ([$c1, $c2] as $c) {
                $sheet->getStyle("{$c}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
        }
    }

    private function fillTripsQtyColumnBackgrounds(Worksheet $sheet, int $row): void
    {
        foreach (['C', 'E', 'G'] as $col) {
            $sheet->getStyle($col.$row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB(self::CLR_TRIPS_RAKES_FILL);
        }

        foreach (['D', 'F', 'H'] as $col) {
            $sheet->getStyle($col.$row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB(self::CLR_QTY_FILL);
        }
    }

    /**
     * @param  array<string, array{trips?: int, qty?: float}>  $tripsQtyByPeriod  keyed today|month|fy
     */
    private function fillPeriodMetricsRowTripsQty(Worksheet $sheet, int $row, array $tripsQtyByPeriod): void
    {
        $this->fillPeriodMetricsRow($sheet, $row, $tripsQtyByPeriod, false);
    }

    private function numberCellTripsLike(Worksheet $sheet, string $coord, ?int $v): void
    {
        if ($v !== null) {
            $sheet->setCellValue($coord, $v);
        }
        $sheet->getStyle($coord)->getNumberFormat()->setFormatCode('#,##0');
    }

    private function numberCellQty(Worksheet $sheet, string $coord, ?float $v): void
    {
        if ($v !== null) {
            $sheet->setCellValue($coord, $v);
        }
        $sheet->getStyle($coord)->getNumberFormat()->setFormatCode('#,##0.00');
    }

    /**
     * FY keys as column headers (unique financial years in first-seen order, then `Till Date` when present).
     *
     * @param  list<array<string, mixed>>  $fyRows
     * @return list<string>
     */
    private function fySummaryColumnLabels(array $fyRows): array
    {
        $yearKeysOrdered = [];
        foreach ($fyRows as $record) {
            if (! is_array($record)) {
                continue;
            }
            $k = (string) ($record['fy'] ?? '');
            if ($k === '' || $k === 'Till Date') {
                continue;
            }
            $yearKeysOrdered[] = $k;
        }

        $columnLabels = [];
        foreach ($yearKeysOrdered as $yk) {
            if (! in_array($yk, $columnLabels, true)) {
                $columnLabels[] = $yk;
            }
        }

        $hasTillDate = false;
        foreach ($fyRows as $record) {
            if (! is_array($record)) {
                continue;
            }
            if ((string) ($record['fy'] ?? '') === 'Till Date') {
                $hasTillDate = true;

                break;
            }
        }
        if ($hasTillDate) {
            $columnLabels[] = 'Till Date';
        }

        return $columnLabels;
    }

    /**
     * Transposed FY summary: column headers are FY labels (+ Till Date last), rows are Prod OB/Coal, Disp Coal/Rake.
     * Mirrors dashboard FY Summary aggregates (same `fySummary.rows` payload).
     *
     * @param  list<array<string, mixed>>  $fyRows
     */
    private function writeFySummaryPivotBlock(
        Worksheet $sheet,
        array $fyRows,
        int $startRow,
    ): int {
        $columnLabels = $this->fySummaryColumnLabels($fyRows);

        if ($columnLabels === []) {
            return $startRow - 1;
        }

        $byFy = [];
        foreach ($fyRows as $record) {
            if (! is_array($record)) {
                continue;
            }
            $k = (string) ($record['fy'] ?? '');
            if ($k === '') {
                continue;
            }
            $byFy[$k] = $record;
        }

        $lastColLetter = Coordinate::stringFromColumnIndex(1 + count($columnLabels));
        $titleRange = 'A'.$startRow.':'.$lastColLetter.$startRow;

        $r = $startRow;

        $sheet->setCellValue("A{$r}", 'FY Summary');
        $sheet->mergeCells($titleRange);
        $this->styleCellRange($sheet, $titleRange, ['bold' => true, 'fontSize' => 12]);
        $sheet->getStyle($titleRange)->getFont()->setUnderline(true);
        $r++;

        $sheet->setCellValue("A{$r}", '');
        $sheet->getStyle('A'.$r)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::CLR_TRIPS_RAKES_FILL],
            ],
        ]);
        foreach ($columnLabels as $i => $label) {
            $colLetter = Coordinate::stringFromColumnIndex($i + 2);
            $sheet->setCellValue("{$colLetter}{$r}", $label);
            $this->styleCellRange($sheet, "{$colLetter}{$r}", [
                'fill' => self::CLR_TRIPS_RAKES_FILL,
                'bold' => true,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ]);
        }
        $r++;

        /** @var list{array{title: string, labelFill: string, valueFill: string, extract: Closure(array): float}} */
        $lines = [
            [
                'title' => 'Prod. OB',
                'labelFill' => self::CLR_PRODUCTION_FILL,
                'valueFill' => self::CLR_PRODUCTION_FILL,
                'extract' => static fn (array $row): float => round((float) ($row['production']['obQty'] ?? 0.0), 2),
            ],
            [
                'title' => 'Prod. Coal',
                'labelFill' => self::CLR_PRODUCTION_FILL,
                'valueFill' => self::CLR_PRODUCTION_FILL,
                'extract' => static fn (array $row): float => round((float) ($row['production']['coalQty'] ?? 0.0), 2),
            ],
            [
                'title' => 'Disp. Coal',
                'labelFill' => self::CLR_DISPATCH_FILL,
                'valueFill' => self::CLR_DISPATCH_FILL,
                'extract' => static fn (array $row): float => round((float) ($row['roadDispatch']['qty'] ?? 0.0), 2),
            ],
            [
                'title' => 'Disp. Rake',
                'labelFill' => self::CLR_DISPATCH_FILL,
                'valueFill' => self::CLR_DISPATCH_FILL,
                'extract' => static fn (array $row): float => round((float) ($row['railDispatch']['qty'] ?? 0.0), 2),
            ],
        ];

        foreach ($lines as $def) {
            $sheet->setCellValue('A'.$r, $def['title']);
            $this->styleCellRange($sheet, 'A'.$r, [
                'fill' => $def['labelFill'],
                'bold' => false,
                'horizontal' => Alignment::HORIZONTAL_LEFT,
            ]);

            foreach ($columnLabels as $i => $fyKey) {
                $record = $byFy[$fyKey] ?? null;
                $qty = $record !== null ? $def['extract']($record) : 0.0;

                $colLetter = Coordinate::stringFromColumnIndex($i + 2);
                $coord = $colLetter.$r;
                $sheet->setCellValue($coord, $qty);
                $sheet->getStyle($coord)->getNumberFormat()->setFormatCode(self::FMT_IND_QTY_TWO);
                $sheet->getStyle($coord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle($coord)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($def['valueFill']);
            }
            $r++;
        }

        $this->thinBorderAround($sheet, 'A'.$startRow.':'.$lastColLetter.($r - 1));

        return $r - 1;
    }

    /**
     * Last data column letter for the FY pivot (column A holds row labels). Matches {@see fySummaryColumnLabels()}.
     *
     * @param  list<array<string, mixed>>  $fyRows
     */
    private function fyPivotLastColumnLetter(array $fyRows): string
    {
        return Coordinate::stringFromColumnIndex(1 + count($this->fySummaryColumnLabels($fyRows)));
    }

    private function applyMainGridOutline(Worksheet $sheet, int $startRow, int $endRow): void
    {
        $sheet->getStyle('A'.$startRow.':H'.$endRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => self::CLR_BLACK],
                ],
            ],
        ]);
    }

    private function thinBorderAround(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => self::CLR_BLACK],
                ],
            ],
        ]);
    }

    private function autosizeCols(Worksheet $sheet, string $throughColLetter, int $lastRow): void
    {
        $from = Coordinate::columnIndexFromString('A');
        $to = Coordinate::columnIndexFromString($throughColLetter);
        for ($i = $from; $i <= $to; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
        $sheet->getStyle('A1:'.$throughColLetter.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    }

    /**
     * @param  array{
     *     fill?: string,
     *     foreground?: string,
     *     horizontal?: string,
     *     vertical?: string,
     *     bold?: bool,
     *     fontSize?: int
     * }  $opts
     */
    private function styleCellRange(Worksheet $sheet, string $coord, array $opts): void
    {
        $style = $sheet->getStyle($coord);
        if (($opts['bold'] ?? false) === true) {
            $style->getFont()->setBold(true);
        }
        if (isset($opts['fontSize'])) {
            $style->getFont()->setSize((int) $opts['fontSize']);
        }
        if (isset($opts['foreground'])) {
            $style->getFont()->getColor()->setARGB($opts['foreground']);
        }
        $style->getAlignment()->setHorizontal($opts['horizontal'] ?? Alignment::HORIZONTAL_GENERAL);
        if (isset($opts['vertical'])) {
            $style->getAlignment()->setVertical($opts['vertical']);
        }
        if (isset($opts['fill'])) {
            $style->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($opts['fill']);
        }
        $style->getAlignment()->setWrapText(false);
    }
}
