<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Rake;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

final class RakeWeighmentXlsxTemplate
{
    public const string SHEET_TITLE = 'Weighment';

    /**
     * Cell map for the generated template.
     */
    public const array CELLS = [
        // Header labels row 3
        'rake_number' => 'B3',
        'siding' => 'D3',
        'location' => 'F3',
        'date' => 'H3',
        // Row 4
        'rake_sequence_no' => 'B4',
        // Totals (labels in A, values in B)
        'total_cc' => 'B68',
        'total_gross' => 'B69',
        'total_tare' => 'B70',
        'total_net' => 'B71',
        'total_underload' => 'B72',
        'total_overload' => 'B73',
    ];

    public function makeForRake(Rake $rake): Spreadsheet
    {
        $rake->loadMissing('siding');

        $sheet = new Spreadsheet;
        $worksheet = $sheet->getActiveSheet();
        $worksheet->setTitle(self::SHEET_TITLE);

        // Title
        $worksheet->setCellValue('A1', 'WEIGHMENT SHEET');
        $worksheet->mergeCells('A1:J1');
        $worksheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $worksheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header labels
        $worksheet->setCellValue('A3', 'Rake Number');
        $worksheet->setCellValue('C3', 'Siding');
        $worksheet->setCellValue('E3', 'Location');
        $worksheet->setCellValue('G3', 'Date');
        $worksheet->setCellValue('A4', 'Rake Sequence No');

        $worksheet->getStyle('A3:H4')->getFont()->setBold(true);

        // Prefill values
        $worksheet->setCellValue(self::CELLS['rake_number'], (string) ($rake->rake_number ?? ''));
        $worksheet->setCellValue(self::CELLS['siding'], (string) ($rake->siding?->name ?? ''));
        $worksheet->setCellValue(self::CELLS['location'], (string) ($rake->destination_code ?? ''));
        $worksheet->setCellValue(self::CELLS['date'], $rake->loading_date?->toDateString() ?? '');
        $worksheet->setCellValue(self::CELLS['rake_sequence_no'], (string) ($rake->priority_number ?? ''));

        // Table header
        $headerRow = 6;
        $worksheet->fromArray(
            ['SlNo', 'Wagon No', 'Wagon Type', 'CC', 'Gross', 'Tare', 'Net', 'Underload', 'Overload', 'Speed'],
            null,
            "A{$headerRow}",
        );

        $worksheet->getStyle("A{$headerRow}:J{$headerRow}")->getFont()->setBold(true);
        $worksheet->getStyle("A{$headerRow}:J{$headerRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEFEFEF');
        $worksheet->getStyle("A{$headerRow}:J{$headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Rows 1..60
        $firstDataRow = $headerRow + 1;
        $lastDataRow = $firstDataRow + 59;
        for ($i = 0; $i < 60; $i++) {
            $row = $firstDataRow + $i;
            $worksheet->setCellValue("A{$row}", $i + 1);
        }

        // Totals footer
        $worksheet->setCellValue('A67', 'TOTALS');
        $worksheet->getStyle('A67')->getFont()->setBold(true);

        $worksheet->setCellValue('A68', 'Total CC');
        $worksheet->setCellValue('A69', 'Total Gross');
        $worksheet->setCellValue('A70', 'Total Tare');
        $worksheet->setCellValue('A71', 'Total Net');
        $worksheet->setCellValue('A72', 'Total Underload');
        $worksheet->setCellValue('A73', 'Total Overload');
        $worksheet->getStyle('A68:A73')->getFont()->setBold(true);

        // Column widths
        $worksheet->getColumnDimension('A')->setWidth(6);
        $worksheet->getColumnDimension('B')->setWidth(18);
        $worksheet->getColumnDimension('C')->setWidth(14);
        foreach (['D', 'E', 'F', 'G', 'H', 'I', 'J'] as $col) {
            $worksheet->getColumnDimension($col)->setWidth(12);
        }

        // Borders for table area
        $worksheet->getStyle("A{$headerRow}:J{$lastDataRow}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // Borders for totals
        $worksheet->getStyle('A68:B73')
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // Number formatting
        $worksheet->getStyle("D{$firstDataRow}:J{$lastDataRow}")
            ->getNumberFormat()
            ->setFormatCode('0.00');
        $worksheet->getStyle('B68:B73')
            ->getNumberFormat()
            ->setFormatCode('0.00');

        return $sheet;
    }
}
