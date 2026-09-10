<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Coal Transport Report layout: title, date row, two-level headers (11 columns),
 * three shift rows, day total, month total. No fill colors; borders and bold only.
 */
final class CoalTransportReportExport implements FromArray, WithEvents
{
    private const LAST_COL = 'K';

    /**
     * @param  array{
     *     date: string,
     *     date_display: string,
     *     columns: array<int, array{code: string, label: string, siding_id: int|null}>,
     *     rows: array<int, array{sl_no: int, shift_label: string, cells: array<int, array{trips: int, qty: float}>, total_trips: int, total_qty: float}>,
     *     day_totals: array{cells: array<int, array{trips: int, qty: float}>, total_trips: int, total_qty: float},
     *     month_totals: array{cells: array<int, array{trips: int, qty: float}>, total_trips: int, total_qty: float}
     * }  $payload
     */
    public function __construct(
        private readonly array $payload,
    ) {}

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
        $p = $this->payload;
        $cols = $p['columns'];

        $sheet->setCellValue('A1', 'Coal Transport Report');
        $sheet->mergeCells('A1:'.self::LAST_COL.'1');
        $this->styleTitle($sheet, 'A1:'.self::LAST_COL.'1');

        $sheet->setCellValue('A2', 'Date');
        $sheet->mergeCells('A2:B2');
        $sheet->setCellValue('C2', $p['date_display']);
        $sheet->mergeCells('C2:H2');
        $sheet->mergeCells('I2:'.self::LAST_COL.'2');
        $this->styleDateRow($sheet, 'A2:'.self::LAST_COL.'2');

        $sheet->mergeCells('A3:A4');
        $sheet->setCellValue('A3', 'Sl No');
        $sheet->mergeCells('B3:B4');
        $sheet->setCellValue('B3', 'Shift');

        $labels = array_map(fn (array $c): string => $c['label'], $cols);
        $sheet->mergeCells('C3:D3');
        $sheet->setCellValue('C3', $labels[0] ?? 'Pakur');
        $sheet->setCellValue('C4', 'Trips');
        $sheet->setCellValue('D4', 'Qty');

        $sheet->mergeCells('E3:F3');
        $sheet->setCellValue('E3', $labels[1] ?? 'Dumka');
        $sheet->setCellValue('E4', 'Trips');
        $sheet->setCellValue('F4', 'Qty');

        $sheet->mergeCells('G3:H3');
        $sheet->setCellValue('G3', $labels[2] ?? 'Kurwa');
        $sheet->setCellValue('G4', 'Trips');
        $sheet->setCellValue('H4', 'Qty');

        $sheet->mergeCells('I3:I4');
        $sheet->setCellValue('I3', 'Shift');

        $sheet->mergeCells('J3:K3');
        $sheet->setCellValue('J3', '(Pakur+Dumka+Kurwa) Total');
        $sheet->setCellValue('J4', 'Trips');
        $sheet->setCellValue('K4', 'Qty');

        $this->styleHeaderBlock($sheet, 'A3:K4');

        $row = 5;
        foreach ($p['rows'] as $r) {
            $sheet->setCellValue('A'.$row, $r['sl_no']);
            $sheet->setCellValue('B'.$row, $r['shift_label']);
            $col = 'C';
            foreach ($r['cells'] as $cell) {
                $sheet->setCellValue($col.$row, $cell['trips']);
                $sheet->getStyle($col.$row)->getNumberFormat()->setFormatCode('#,##0');
                $col++;
                $sheet->setCellValue($col.$row, $cell['qty']);
                $sheet->getStyle($col.$row)->getNumberFormat()->setFormatCode('#,##0.00');
                $col++;
            }
            $sheet->setCellValue('I'.$row, $r['shift_label']);
            $sheet->setCellValue('J'.$row, $r['total_trips']);
            $sheet->getStyle('J'.$row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->setCellValue('K'.$row, $r['total_qty']);
            $sheet->getStyle('K'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
            $this->styleDataRow($sheet, 'A'.$row.':K'.$row);
            $row++;
        }

        $sheet->mergeCells('A'.$row.':B'.$row);
        $sheet->setCellValue('A'.$row, 'Day Total');
        $this->writeTotalCells($sheet, $row, $p['day_totals']);
        $this->styleTotalsRow($sheet, 'A'.$row.':K'.$row);
        $row++;

        $sheet->mergeCells('A'.$row.':B'.$row);
        $sheet->setCellValue('A'.$row, 'Month Total');
        $this->writeTotalCells($sheet, $row, $p['month_totals']);
        $this->styleTotalsRow($sheet, 'A'.$row.':K'.$row);

        $sheet->getStyle('A1:K'.$row)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'] as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $sheet->getStyle('A1:K'.$row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    }

    /**
     * @param  array{cells: array<int, array{trips: int, qty: float}>, total_trips: int, total_qty: float}  $totals
     */
    private function writeTotalCells(Worksheet $sheet, int $row, array $totals): void
    {
        $col = 'C';
        foreach ($totals['cells'] as $cell) {
            $sheet->setCellValue($col.$row, $cell['trips']);
            $sheet->getStyle($col.$row)->getNumberFormat()->setFormatCode('#,##0');
            $col++;
            $sheet->setCellValue($col.$row, $cell['qty']);
            $sheet->getStyle($col.$row)->getNumberFormat()->setFormatCode('#,##0.00');
            $col++;
        }
        $sheet->setCellValue('I'.$row, '');
        $sheet->setCellValue('J'.$row, $totals['total_trips']);
        $sheet->getStyle('J'.$row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->setCellValue('K'.$row, $totals['total_qty']);
        $sheet->getStyle('K'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
    }

    private function styleTitle(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    private function styleDateRow(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getStyle('A2:B2')->applyFromArray([
            'font' => ['bold' => true],
        ]);
    }

    private function styleHeaderBlock(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    private function styleTotalsRow(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    private function styleDataRow(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
}
