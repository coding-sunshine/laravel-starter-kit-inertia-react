<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Rake;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class RakeWeighmentXlsxParser
{
    /**
     * @return array{header: array<string, mixed>, totals: array<string, float|null>, wagon_rows: array<int, array<string, mixed>>}
     */
    public function parseForRake(Rake $rake, string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheet(0);

        return $this->parseSheetForRake($rake, $sheet);
    }

    /**
     * @return array{header: array<string, mixed>, totals: array<string, float|null>, wagon_rows: array<int, array<string, mixed>>}
     */
    private function parseSheetForRake(Rake $rake, Worksheet $sheet): array
    {
        $rakeNumber = $this->findLabelValueString($sheet, 'Rake Number')
            ?? $this->stringCell($sheet, RakeWeighmentXlsxTemplate::CELLS['rake_number']);
        if ($rakeNumber === '') {
            throw new InvalidArgumentException('Rake number is missing in the XLSX template.');
        }

        if (! $this->rakeNumbersMatch($rakeNumber, (string) $rake->rake_number)) {
            throw new InvalidArgumentException('The uploaded XLSX appears to be for a different rake number.');
        }

        $header = [
            'rake_number' => $rakeNumber,
            'siding' => $this->findLabelValueString($sheet, 'Siding')
                ?? $this->stringCell($sheet, RakeWeighmentXlsxTemplate::CELLS['siding']),
            'location' => $this->findLabelValueString($sheet, 'Location')
                ?? $this->stringCell($sheet, RakeWeighmentXlsxTemplate::CELLS['location']),
            'rake_sequence_no' => $this->findLabelValueString($sheet, 'Rake Sequence No')
                ?? $this->stringCell($sheet, RakeWeighmentXlsxTemplate::CELLS['rake_sequence_no']),
            'loading_date' => $this->findLabelValueString($sheet, 'Date')
                ?? $this->stringCell($sheet, RakeWeighmentXlsxTemplate::CELLS['date']),
        ];

        $wagonRows = $this->parseWagonRows($sheet);
        if ($wagonRows === []) {
            throw new InvalidArgumentException('No wagon rows found in XLSX.');
        }

        $totals = $this->parseTotals($sheet);
        if ($totals['total_cc_weight_mt'] === null) {
            $totals['total_cc_weight_mt'] = $this->sum($wagonRows, 'cc_capacity_mt');
        }
        if ($totals['total_gross_weight_mt'] === null) {
            $totals['total_gross_weight_mt'] = $this->sum($wagonRows, 'actual_gross_mt');
        }
        if ($totals['total_tare_weight_mt'] === null) {
            $totals['total_tare_weight_mt'] = $this->sum($wagonRows, 'tare_weight_mt');
        }
        if ($totals['total_net_weight_mt'] === null) {
            $totals['total_net_weight_mt'] = $this->sum($wagonRows, 'net_weight_mt');
        }
        if ($totals['total_under_load_mt'] === null) {
            $totals['total_under_load_mt'] = $this->sum($wagonRows, 'under_load_mt');
        }
        if ($totals['total_over_load_mt'] === null) {
            $totals['total_over_load_mt'] = $this->sum($wagonRows, 'over_load_mt');
        }
        if ($totals['maximum_train_speed_kmph'] === null) {
            $totals['maximum_train_speed_kmph'] = $this->max($wagonRows, 'speed_kmph');
        }

        return [
            'header' => $header,
            'totals' => $totals,
            'wagon_rows' => $wagonRows,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseWagonRows(Worksheet $sheet): array
    {
        $bounds = $this->getSearchBounds($sheet);
        $headerInfo = $this->findWagonTableHeader($sheet, $bounds);
        if ($headerInfo === null) {
            throw new InvalidArgumentException('Unable to find wagon table header row in XLSX. Ensure header labels like "SlNo", "Wagon No", "CC", "Gross", "Tare", "Net" exist.');
        }

        [$headerRow] = $headerInfo;

        $rows = [];
        $startRow = $headerRow + 1;

        // Read until we hit totals/blank section; cap to avoid scanning huge sheets.
        $maxRow = min($bounds['maxRow'], $startRow + 300);
        $stopStreak = 0;

        for ($row = $startRow; $row <= $maxRow; $row++) {
            $tokens = $this->rowTokens($sheet, $row, $bounds['maxCol']);
            if ($tokens === []) {
                continue;
            }

            $seq = $this->parseLeadingInt($tokens[0] ?? '');
            if ($seq <= 0) {
                // Once we stop seeing wagon rows, we should be entering totals section.
                $stopStreak++;
                if ($stopStreak >= 3) {
                    break;
                }

                continue;
            }

            $stopStreak = 0;

            $wagonNumber = mb_trim((string) ($tokens[1] ?? ''));
            if ($wagonNumber === '') {
                // Treat as end of table / malformed row.
                $stopStreak++;
                if ($stopStreak >= 3) {
                    break;
                }

                continue;
            }

            $wagonType = null;
            $numericStart = 2;
            $maybeType = $tokens[2] ?? null;
            if ($maybeType !== null && $maybeType !== '' && $this->toFloat($maybeType) === null) {
                $wagonType = mb_trim((string) $maybeType) !== '' ? mb_trim((string) $maybeType) : null;
                $numericStart = 3;
            }

            $numeric = array_slice($tokens, $numericStart);
            // Expect at least: CC, Gross, Tare, Net, Underload, Overload (Speed optional)
            if (count($numeric) < 6) {
                throw new InvalidArgumentException("Wagon row for {$wagonNumber} is incomplete. Expected CC, Gross, Tare, Net, Underload, Overload.");
            }

            $cc = $this->requireNumericToken($numeric[0] ?? null, 'CC', $wagonNumber);
            $gross = $this->requireNumericToken($numeric[1] ?? null, 'Gross', $wagonNumber);
            $tare = $this->requireNumericToken($numeric[2] ?? null, 'Tare', $wagonNumber);
            $net = $this->requireNumericToken($numeric[3] ?? null, 'Net', $wagonNumber);
            $under = $this->requireNumericToken($numeric[4] ?? null, 'Underload', $wagonNumber);
            $over = $this->requireNumericToken($numeric[5] ?? null, 'Overload', $wagonNumber);
            $speed = isset($numeric[6]) ? $this->optionalNumericToken($numeric[6]) : null;

            $rows[] = [
                'sequence' => $seq,
                'wagon_number' => $wagonNumber,
                'wagon_type' => $wagonType,
                'cc_capacity_mt' => $cc,
                'actual_gross_mt' => $gross,
                'tare_weight_mt' => $tare,
                'net_weight_mt' => $net,
                'under_load_mt' => $under,
                'over_load_mt' => $over,
                'speed_kmph' => $speed,
                'printed_tare_mt' => null,
                'axles' => null,
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private function rowTokens(Worksheet $sheet, int $row, int $maxCol): array
    {
        $out = [];
        for ($col = 1; $col <= $maxCol; $col++) {
            $raw = $sheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
            $text = mb_trim((string) $raw);
            if ($text === '') {
                continue;
            }
            $out[] = $text;
        }

        return $out;
    }

    private function requireNumericToken(mixed $value, string $label, string $wagonNumber): float
    {
        $v = $this->toFloat($value);
        if ($v === null) {
            throw new InvalidArgumentException("{$label} must be a number for wagon {$wagonNumber}.");
        }

        return $v;
    }

    private function optionalNumericToken(mixed $value): ?float
    {
        $v = $this->toFloat($value);
        if ($v === null && mb_trim((string) $value) !== '') {
            throw new InvalidArgumentException('Speed must be a number.');
        }

        return $v;
    }

    /**
     * @return array{
     *   total_cc_weight_mt: float|null,
     *   total_gross_weight_mt: float|null,
     *   total_tare_weight_mt: float|null,
     *   total_net_weight_mt: float|null,
     *   total_under_load_mt: float|null,
     *   total_over_load_mt: float|null,
     *   maximum_train_speed_kmph: float|null,
     *   maximum_weight_mt: float|null
     * }
     */
    private function parseTotals(Worksheet $sheet): array
    {
        $totalCc = $this->findLabelValueFloat($sheet, 'Total CC');
        $totalGross = $this->findLabelValueFloat($sheet, 'Total Gross');
        $totalTare = $this->findLabelValueFloat($sheet, 'Total Tare');
        $totalNet = $this->findLabelValueFloat($sheet, 'Total Net');
        $totalUnder = $this->findLabelValueFloat($sheet, 'Total Underload');
        $totalOver = $this->findLabelValueFloat($sheet, 'Total Overload');

        return [
            'total_cc_weight_mt' => $totalCc,
            'total_gross_weight_mt' => $totalGross,
            'total_tare_weight_mt' => $totalTare,
            'total_net_weight_mt' => $totalNet,
            'total_under_load_mt' => $totalUnder,
            'total_over_load_mt' => $totalOver,
            'maximum_train_speed_kmph' => null,
            'maximum_weight_mt' => null,
        ];
    }

    /**
     * Find a label cell (case-insensitive), then return the adjacent right cell as string.
     */
    private function findLabelValueString(Worksheet $sheet, string $label): ?string
    {
        $pos = $this->findCellByLabel($sheet, $label);
        if ($pos === null) {
            return null;
        }

        [$col, $row] = $pos;
        $value = (string) $sheet->getCellByColumnAndRow($col + 1, $row)->getCalculatedValue();
        $value = mb_trim($value);

        return $value !== '' ? $value : null;
    }

    private function findLabelValueFloat(Worksheet $sheet, string $label): ?float
    {
        $pos = $this->findCellByLabel($sheet, $label);
        if ($pos === null) {
            return null;
        }

        [$col, $row] = $pos;
        $raw = $sheet->getCellByColumnAndRow($col + 1, $row)->getCalculatedValue();
        $v = $this->toFloat($raw);

        if ($v === null) {
            $str = mb_trim((string) $raw);
            if ($str !== '') {
                throw new InvalidArgumentException("{$label} must be a number.");
            }
        }

        return $v;
    }

    /**
     * @return array{0:int,1:int}|null [colIndex(1-based), rowIndex(1-based)]
     */
    private function findCellByLabel(Worksheet $sheet, string $label): ?array
    {
        $needle = $this->normalizeLabel($label);
        $bounds = $this->getSearchBounds($sheet);

        for ($row = 1; $row <= $bounds['maxRow']; $row++) {
            for ($col = 1; $col <= $bounds['maxCol']; $col++) {
                $raw = $sheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
                $text = $this->normalizeLabel((string) $raw);
                if ($text === '') {
                    continue;
                }
                if ($text === $needle) {
                    return [$col, $row];
                }
            }
        }

        return null;
    }

    /**
     * @return array{maxRow:int,maxCol:int}
     */
    private function getSearchBounds(Worksheet $sheet): array
    {
        // Limit search area for performance but keep it flexible.
        $maxRow = min(300, max(20, (int) $sheet->getHighestRow()));
        $highestCol = $sheet->getHighestColumn();
        $maxCol = min(30, max(10, (int) \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol)));

        return ['maxRow' => $maxRow, 'maxCol' => $maxCol];
    }

    private function normalizeLabel(string $value): string
    {
        $value = mb_strtolower(mb_trim($value));
        $value = preg_replace('/\s+/', ' ', $value) ?? '';
        $value = str_replace([':', '.', "\u{00A0}"], '', $value);

        return mb_trim($value);
    }

    /**
     * @param  array{maxRow:int,maxCol:int}  $bounds
     * @return array{0:int,1:array<string,int>}|null [headerRow, colMap]
     */
    private function findWagonTableHeader(Worksheet $sheet, array $bounds): ?array
    {
        // Header row must contain at least these columns.
        $required = [
            'slno' => ['slno', 'sl no', 'sino', 'seq', 'seq no', 'seq. no', 'sl'],
            'wagon_no' => ['wagon no', 'wagonno', 'wagon number', 'wagon'],
            'cc' => ['cc'],
            'gross' => ['gross'],
            'tare' => ['tare'],
            'net' => ['net'],
            'underload' => ['underload', 'under load', 'uload', 'under'],
            'overload' => ['overload', 'over load', 'oload', 'over'],
        ];

        $optional = [
            'wagon_type' => ['wagon type', 'wagontype', 'type'],
            'speed' => ['speed'],
        ];

        $scanRows = min(80, $bounds['maxRow']);
        for ($row = 1; $row <= $scanRows; $row++) {
            $labelsByCol = [];
            for ($col = 1; $col <= $bounds['maxCol']; $col++) {
                $raw = (string) $sheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
                $labelsByCol[$col] = $this->normalizeLabel($raw);
            }

            $map = [];
            foreach ($required as $key => $aliases) {
                $found = null;
                foreach ($labelsByCol as $col => $label) {
                    if ($label === '') {
                        continue;
                    }
                    foreach ($aliases as $alias) {
                        if ($label === $this->normalizeLabel($alias)) {
                            $found = $col;
                            break 2;
                        }
                    }
                }
                if ($found === null) {
                    continue 2;
                }
                $map[$key] = $found;
            }

            foreach ($optional as $key => $aliases) {
                foreach ($labelsByCol as $col => $label) {
                    if ($label === '') {
                        continue;
                    }
                    foreach ($aliases as $alias) {
                        if ($label === $this->normalizeLabel($alias)) {
                            $map[$key] = $col;
                            break 2;
                        }
                    }
                }
            }

            return [$row, $map];
        }

        return null;
    }

    private function stringCellByIndex(Worksheet $sheet, int $col, int $row): string
    {
        $value = (string) $sheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();

        return mb_trim($value);
    }

    private function nullableStringCellByIndex(Worksheet $sheet, int $col, int $row): ?string
    {
        $value = $this->stringCellByIndex($sheet, $col, $row);

        return $value !== '' ? $value : null;
    }

    private function intCellByIndex(Worksheet $sheet, int $col, int $row): int
    {
        $value = $sheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
        if (is_int($value)) {
            return $value;
        }

        $value = mb_trim((string) $value);
        if ($value === '' || ! is_numeric($value)) {
            return 0;
        }

        return (int) $value;
    }

    private function parseLeadingInt(string $value): int
    {
        $value = mb_trim($value);
        if ($value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        if (preg_match('/^\s*(\d+)/', $value, $m)) {
            return (int) $m[1];
        }

        return 0;
    }

    private function requiredNumericByIndex(Worksheet $sheet, int $col, int $row, string $label, string $wagonNumber): float
    {
        $raw = $sheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
        $v = $this->toFloat($raw);
        if ($v === null) {
            throw new InvalidArgumentException("{$label} must be a number for wagon {$wagonNumber}.");
        }

        return $v;
    }

    private function optionalNumericByIndex(Worksheet $sheet, int $col, int $row, string $label): ?float
    {
        $raw = $sheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
        $v = $this->toFloat($raw);
        if ($v === null) {
            $str = mb_trim((string) $raw);
            if ($str !== '') {
                throw new InvalidArgumentException("{$label} must be a number.");
            }
        }

        return $v;
    }

    private function stringCell(Worksheet $sheet, string $cell): string
    {
        $value = (string) $sheet->getCell($cell)->getCalculatedValue();

        return mb_trim($value);
    }

    private function nullableStringCell(Worksheet $sheet, string $cell): ?string
    {
        $value = $this->stringCell($sheet, $cell);

        return $value !== '' ? $value : null;
    }

    private function intCell(Worksheet $sheet, string $cell): int
    {
        $value = $sheet->getCell($cell)->getCalculatedValue();
        if (is_int($value)) {
            return $value;
        }

        $value = mb_trim((string) $value);
        if ($value === '' || ! is_numeric($value)) {
            return 0;
        }

        return (int) $value;
    }

    private function requiredNumericCell(Worksheet $sheet, string $cell, string $label, int $row): float
    {
        $raw = $sheet->getCell($cell)->getCalculatedValue();
        $v = $this->toFloat($raw);
        if ($v === null) {
            throw new InvalidArgumentException("{$label} must be a number at row {$row}.");
        }

        return $v;
    }

    private function optionalNumericCell(Worksheet $sheet, string $cell, string $label, int $row): ?float
    {
        $raw = $sheet->getCell($cell)->getCalculatedValue();
        $v = $this->toFloat($raw);
        if ($v === null) {
            $str = mb_trim((string) $raw);
            if ($str !== '') {
                throw new InvalidArgumentException("{$label} must be a number at row {$row}.");
            }
        }

        return $v;
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_float($value) || is_int($value)) {
            return round((float) $value, 2);
        }

        $value = mb_trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = str_replace([','], '', $value);
        if (! is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
    }

    /**
     * @param  array<int, array<string, mixed>>  $wagonRows
     */
    private function sum(array $wagonRows, string $key): float
    {
        $sum = 0.0;
        foreach ($wagonRows as $row) {
            $v = $row[$key] ?? null;
            if (is_numeric($v)) {
                $sum += (float) $v;
            }
        }

        return round($sum, 2);
    }

    /**
     * @param  array<int, array<string, mixed>>  $wagonRows
     */
    private function max(array $wagonRows, string $key): ?float
    {
        $max = null;
        foreach ($wagonRows as $row) {
            $v = $row[$key] ?? null;
            if (! is_numeric($v)) {
                continue;
            }
            $f = (float) $v;
            if ($max === null || $f > $max) {
                $max = $f;
            }
        }

        return $max !== null ? round($max, 2) : null;
    }

    private function rakeNumbersMatch(string $parsed, string $expected): bool
    {
        $normalize = static function (string $v): string {
            $v = mb_strtoupper(mb_trim(preg_replace('/\s+/', ' ', $v) ?? ''));

            return $v;
        };

        return $normalize($parsed) === $normalize($expected);
    }
}
