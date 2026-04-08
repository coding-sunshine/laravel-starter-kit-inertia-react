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
 * Coal stock (approx) siding layout: title + date row, two-level headers (7 columns), data rows, TOTAL.
 * Headers use line breaks + wrap text for a compact sheet. No fill colors; borders and bold as specified.
 *
 * @phpstan-type Row array{siding: string, opening: float, road: float, no_of_rakes: int, rakes_qty: float, closing: float, remarks: string}
 * @phpstan-type Totals array{opening: float, road: float, no_of_rakes: int, rakes_qty: float, closing: float}
 * @phpstan-type Payload array{date_display: string, rows: list<Row>, totals: Totals}
 */
final class CoalStockApproxDetailExport implements FromArray, WithEvents
{
    private const LAST_COL = 'G';

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
        /** @var Payload $p */
        $p = $this->payload;

        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'COAL STOCK(APPROX) DETAILS (SIDING)');
        $this->styleTitleLeft($sheet, 'A1:E1');

        $sheet->setCellValue('F1', 'DATE :');
        $sheet->setCellValue('G1', $p['date_display']);

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getStyle('F1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getStyle('G1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->mergeCells('A2:A3');
        $sheet->setCellValue('A2', 'Siding');
        $sheet->mergeCells('B2:B3');
        $sheet->setCellValue('B2', "Rly Siding\nOpening Coal\nStock (Tons)");
        $sheet->mergeCells('C2:C3');
        $sheet->setCellValue('C2', "Coal Dispatch\nQty By Road\n(Tons)");
        $sheet->mergeCells('D2:E2');
        $sheet->setCellValue('D2', "Dispatch\nBy Rail");
        $sheet->setCellValue('D3', "No. of\nRakes");
        $sheet->setCellValue('E3', "Rake Qty\n(Tons)");
        $sheet->mergeCells('F2:F3');
        $sheet->setCellValue('F2', "Rly Siding\nClosing Stock\n(Tons)");
        $sheet->mergeCells('G2:G3');
        $sheet->setCellValue('G2', 'Remarks');

        $this->styleHeaderBlock($sheet, 'A2:'.self::LAST_COL.'3');
        $sheet->getRowDimension(2)->setRowHeight(36);
        $sheet->getRowDimension(3)->setRowHeight(36);

        $row = 4;
        foreach ($p['rows'] as $r) {
            $sheet->setCellValue('A'.$row, $r['siding']);
            $sheet->setCellValue('B'.$row, $r['opening']);
            $sheet->getStyle('B'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->setCellValue('C'.$row, $r['road']);
            $sheet->getStyle('C'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->setCellValue('D'.$row, $r['no_of_rakes']);
            $sheet->getStyle('D'.$row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->setCellValue('E'.$row, $r['rakes_qty']);
            $sheet->getStyle('E'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->setCellValue('F'.$row, $r['closing']);
            $sheet->getStyle('F'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->setCellValue('G'.$row, $r['remarks']);
            $this->styleDataRow($sheet, $row);
            $row++;
        }

        $t = $p['totals'];
        $sheet->setCellValue('A'.$row, 'TOTAL');
        $sheet->setCellValue('B'.$row, $t['opening']);
        $sheet->getStyle('B'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->setCellValue('C'.$row, $t['road']);
        $sheet->getStyle('C'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->setCellValue('D'.$row, $t['no_of_rakes']);
        $sheet->getStyle('D'.$row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->setCellValue('E'.$row, $t['rakes_qty']);
        $sheet->getStyle('E'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->setCellValue('F'.$row, $t['closing']);
        $sheet->getStyle('F'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->setCellValue('G'.$row, '');
        $this->styleTotalsRow($sheet, $row);

        $lastDataRow = $row;
        $sheet->getStyle('A1:'.self::LAST_COL.$lastDataRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        $this->applyCompactColumnWidths($sheet);

        $sheet->getStyle('A4:'.self::LAST_COL.$lastDataRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    }

    private function applyCompactColumnWidths(Worksheet $sheet): void
    {
        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(11);
        $sheet->getColumnDimension('C')->setWidth(11);
        $sheet->getColumnDimension('D')->setWidth(9);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(11);
        $sheet->getColumnDimension('G')->setWidth(22);
    }

    private function styleTitleLeft(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    private function styleHeaderBlock(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'size' => 10],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
    }

    private function styleTotalsRow(Worksheet $sheet, int $row): void
    {
        $range = 'A'.$row.':'.self::LAST_COL.$row;
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        foreach (['B', 'C', 'D', 'E', 'F'] as $col) {
            $sheet->getStyle($col.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
        $sheet->getStyle('G'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    }

    private function styleDataRow(Worksheet $sheet, int $row): void
    {
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        foreach (['B', 'C', 'D', 'E', 'F'] as $col) {
            $sheet->getStyle($col.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
        $sheet->getStyle('G'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('G'.$row)->getAlignment()->setWrapText(true);
    }
}
