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
        $rake->loadMissing('siding');

        // Disabled: comparing XLSX header rake number to `rakes.rake_number` — export layouts and labels vary too much for reliable matching.
        $expected = $this->normalizeRakeNumberCell((string) $rake->rake_number);
        $rakeCandidates = $this->collectRakeNumberCandidatesFromSheet($sheet);

        if ($rakeCandidates !== []) {
            $rakeNumber = $rakeCandidates[0];
        } else {
            $rakeNumber = $expected;
            if ($rakeNumber === '') {
                throw new InvalidArgumentException('Rake number is missing in the XLSX template and the rake record has no rake number.');
            }
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

        if ($this->isDumkaKurwaSiding($rake) || $this->isPakurSiding($rake)) {
            $header = $this->applyHeaderFallbacksFromRake($rake, $header);
        }

        $wagonRows = $this->parseWagonRowsForSiding($rake, $sheet);
        if ($wagonRows === []) {
            throw new InvalidArgumentException('No wagon rows found in XLSX.');
        }

        $totals = $this->parseTotals($sheet);
        $totals = $this->fillTotalsFromWagonRows($totals, $wagonRows);

        return [
            'header' => $header,
            'totals' => $totals,
            'wagon_rows' => $wagonRows,
        ];
    }

    /**
     * @param  array<string, float|null>  $totals
     * @param  array<int, array<string, mixed>>  $wagonRows
     * @return array<string, float|null>
     */
    private function fillTotalsFromWagonRows(array $totals, array $wagonRows): array
    {
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

        return $totals;
    }

    private function isDumkaKurwaSiding(Rake $rake): bool
    {
        $code = mb_strtoupper(mb_trim((string) ($rake->siding?->code ?? '')));

        return in_array($code, ['DUMK', 'KURWA'], true);
    }

    private function isPakurSiding(Rake $rake): bool
    {
        return mb_strtoupper(mb_trim((string) ($rake->siding?->code ?? ''))) === 'PKUR';
    }

    /**
     * Wagon-only sheets may omit rake metadata cells; fill from the target rake when values are blank (Dumka/Kurwa/Pakur).
     *
     * @param  array<string, mixed>  $header
     * @return array<string, mixed>
     */
    private function applyHeaderFallbacksFromRake(Rake $rake, array $header): array
    {
        $rake->loadMissing('siding');

        if ($this->isBlankHeaderValue($header['siding'] ?? null)) {
            $name = $rake->siding?->name;
            $header['siding'] = $name !== null && mb_trim((string) $name) !== '' ? mb_trim((string) $name) : null;
        }

        if ($this->isBlankHeaderValue($header['location'] ?? null)) {
            $loc = $rake->destination_code ?? $rake->destination ?? null;
            if ($loc !== null) {
                $loc = mb_trim((string) $loc);
            }
            $header['location'] = $loc !== null && $loc !== '' ? $loc : null;
        }

        if ($this->isBlankHeaderValue($header['rake_sequence_no'] ?? null) && $rake->priority_number !== null) {
            $header['rake_sequence_no'] = (string) $rake->priority_number;
        }

        if ($this->isBlankHeaderValue($header['loading_date'] ?? null) && $rake->loading_date !== null) {
            $header['loading_date'] = $rake->loading_date->toDateString();
        }

        if ($this->isBlankHeaderValue($header['priority_number'] ?? null)) {
            if (! $this->isBlankHeaderValue($header['rake_sequence_no'] ?? null)) {
                $header['priority_number'] = mb_trim((string) $header['rake_sequence_no']);
            } elseif ($rake->priority_number !== null) {
                $header['priority_number'] = (string) $rake->priority_number;
            }
        }

        return $header;
    }

    private function isBlankHeaderValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        return mb_trim((string) $value) === '';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseWagonRowsForSiding(Rake $rake, Worksheet $sheet): array
    {
        $bounds = $this->getSearchBounds($sheet);
        $code = mb_strtoupper(mb_trim((string) ($rake->siding?->code ?? '')));

        if (in_array($code, ['DUMK', 'KURWA'], true)) {
            return $this->parseDumkaKurwaWagonRows($sheet, $bounds);
        }

        if ($code === 'PKUR') {
            $pakurHeader = $this->findPakurPdfStyleWeighmentTableHeader($sheet, $bounds);
            if ($pakurHeader !== null) {
                return $this->parsePakurPdfStyleWagonRows($sheet, $bounds, $pakurHeader);
            }
        }

        return $this->parseStandardWagonRowsWithColumnMap($sheet, $bounds);
    }

    /**
     * Pakur / standard template: SlNo, Wagon No, optional Wagon Type, CC, Gross, Tare, Net, Underload, Overload, Speed.
     *
     * @return array<int, array<string, mixed>>
     */
    private function parseStandardWagonRowsWithColumnMap(Worksheet $sheet, array $bounds): array
    {
        $headerInfo = $this->findWagonTableHeader($sheet, $bounds);
        if ($headerInfo === null) {
            throw new InvalidArgumentException('Unable to find wagon table header row in XLSX. Ensure header labels like "SlNo", "Wagon No", "CC", "Gross", "Tare", "Net" exist.');
        }

        [$headerRow, $map] = $headerInfo;

        $rows = [];
        $startRow = $headerRow + 1;
        $maxRow = min($bounds['maxRow'], $startRow + 300);
        $stopStreak = 0;

        for ($row = $startRow; $row <= $maxRow; $row++) {
            $seq = $this->parseLeadingInt($this->stringCellByIndex($sheet, $map['slno'], $row));
            if ($seq <= 0) {
                $stopStreak++;
                if ($stopStreak >= 3) {
                    break;
                }

                continue;
            }

            $stopStreak = 0;

            $wagonNumber = mb_trim($this->stringCellByIndex($sheet, $map['wagon_no'], $row));
            if ($wagonNumber === '') {
                $stopStreak++;
                if ($stopStreak >= 3) {
                    break;
                }

                continue;
            }

            $wagonType = isset($map['wagon_type'])
                ? $this->nullableStringCellByIndex($sheet, $map['wagon_type'], $row)
                : null;

            $cc = $this->requiredNumericByIndex($sheet, $map['cc'], $row, 'CC', $wagonNumber);
            $gross = $this->requiredNumericByIndex($sheet, $map['gross'], $row, 'Gross', $wagonNumber);
            $tare = $this->requiredNumericByIndex($sheet, $map['tare'], $row, 'Tare', $wagonNumber);
            $net = $this->requiredNumericByIndex($sheet, $map['net'], $row, 'Net', $wagonNumber);
            $under = $this->requiredNumericByIndex($sheet, $map['underload'], $row, 'Underload', $wagonNumber);
            $over = $this->requiredNumericByIndex($sheet, $map['overload'], $row, 'Overload', $wagonNumber);
            $speed = isset($map['speed'])
                ? $this->optionalNumericByIndex($sheet, $map['speed'], $row, 'Speed')
                : null;

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
     * Pakur PDF-style sheet (same column semantics as `WeighmentPdfImporter::mapFormatA`):
     * SlNo, Wagon No, Wagon Type, Wagon Axles, Wagon CC, Printed Tare, Actual Gross, Actual Tare, Net Wt, Under load, Over load, Speed.
     *
     * @param  array{0:int,1:array<string,int>}  $headerInfo
     * @return array<int, array<string, mixed>>
     */
    private function parsePakurPdfStyleWagonRows(Worksheet $sheet, array $bounds, array $headerInfo): array
    {
        [$headerRow, $map] = $headerInfo;

        $rows = [];
        $startRow = $headerRow + 1;
        $maxRow = min($bounds['maxRow'], $startRow + 300);
        $stopStreak = 0;

        for ($row = $startRow; $row <= $maxRow; $row++) {
            $seq = $this->parseLeadingInt($this->stringCellByIndex($sheet, $map['slno'], $row));
            if ($seq <= 0) {
                $stopStreak++;
                if ($stopStreak >= 3) {
                    break;
                }

                continue;
            }

            $stopStreak = 0;

            $wagonNumber = mb_trim($this->stringCellByIndex($sheet, $map['wagon_no'], $row));
            if ($wagonNumber === '') {
                $stopStreak++;
                if ($stopStreak >= 3) {
                    break;
                }

                continue;
            }

            $wagonType = isset($map['wagon_type'])
                ? $this->nullableStringCellByIndex($sheet, $map['wagon_type'], $row)
                : null;

            $axles = isset($map['axles'])
                ? $this->optionalAxlesByIndex($sheet, $map['axles'], $row)
                : null;

            $cc = $this->requiredNumericByIndex($sheet, $map['cc'], $row, 'Wagon CC', $wagonNumber);
            $printedTare = isset($map['printed_tare'])
                ? $this->optionalNumericByIndex($sheet, $map['printed_tare'], $row, 'Printed Tare')
                : null;
            $actualGross = $this->requiredNumericByIndex($sheet, $map['actual_gross'], $row, 'Actual Gross', $wagonNumber);
            $actualTare = $this->requiredNumericByIndex($sheet, $map['actual_tare'], $row, 'Actual Tare', $wagonNumber);
            $net = $this->requiredNumericByIndex($sheet, $map['net'], $row, 'Net Wt', $wagonNumber);
            $under = $this->requiredNumericByIndex($sheet, $map['underload'], $row, 'Under load', $wagonNumber);
            $over = $this->requiredNumericByIndex($sheet, $map['overload'], $row, 'Over load', $wagonNumber);
            $speed = isset($map['speed'])
                ? $this->optionalNumericByIndex($sheet, $map['speed'], $row, 'Speed')
                : null;

            $rows[] = [
                'sequence' => $seq,
                'wagon_number' => $wagonNumber,
                'wagon_type' => $wagonType,
                'axles' => $axles,
                'cc_capacity_mt' => $cc,
                'printed_tare_mt' => $printedTare,
                'actual_gross_mt' => $actualGross,
                'tare_weight_mt' => $actualTare,
                'net_weight_mt' => $net,
                'under_load_mt' => $under,
                'over_load_mt' => $over,
                'speed_kmph' => $speed,
            ];
        }

        return $rows;
    }

    private function optionalAxlesByIndex(Worksheet $sheet, int $col, int $row): ?int
    {
        $raw = $sheet->getCellByColumnAndRow($col, $row)->getCalculatedValue();
        if ($raw === null) {
            return null;
        }
        $str = mb_trim((string) $raw);
        if ($str === '') {
            return null;
        }
        if (is_numeric($str)) {
            return (int) round((float) $str);
        }

        return null;
    }

    /**
     * @param  array{maxRow:int,maxCol:int}  $bounds
     * @return array{0:int,1:array<string,int>}|null [headerRow, colMap]
     */
    private function findPakurPdfStyleWeighmentTableHeader(Worksheet $sheet, array $bounds): ?array
    {
        $required = [
            'slno' => ['slno', 'sl no', 'sino', 'seq', 'seq no', 'seq. no', 'sl'],
            'wagon_no' => ['wagon no', 'wagonno', 'wagon number', 'wagon'],
            'cc' => ['wagon cc', 'cc'],
            'actual_gross' => ['actual gross'],
            'actual_tare' => ['actual tare'],
            'net' => ['net wt', 'net weight', 'net'],
            'underload' => ['underload', 'under load', 'uload', 'under'],
            'overload' => ['overload', 'over load', 'oload', 'over'],
        ];

        $optional = [
            'wagon_type' => ['wagon type', 'wagontype', 'type'],
            'axles' => ['wagon axles', 'axles'],
            'printed_tare' => ['printed tare'],
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

    /**
     * Dumka/Kurwa: SlNo, Wagon Type, Wagon Own (rly), Wagon No, CC, Gross, Tare, Net, Oload, Uload, Speed.
     *
     * @return array<int, array<string, mixed>>
     */
    private function parseDumkaKurwaWagonRows(Worksheet $sheet, array $bounds): array
    {
        $headerInfo = $this->findDumkaKurwaTableHeader($sheet, $bounds);
        if ($headerInfo === null) {
            throw new InvalidArgumentException(
                'Unable to find Dumka/Kurwa wagon table header. Expected columns such as SlNo, Wagon Type, Wagon No, CC, Gross, Tare, Net, Oload, Uload.'
            );
        }

        [$headerRow, $map] = $headerInfo;

        $rows = [];
        $startRow = $headerRow + 1;
        $maxRow = min($bounds['maxRow'], $startRow + 300);
        $stopStreak = 0;

        for ($row = $startRow; $row <= $maxRow; $row++) {
            $seq = $this->parseLeadingInt($this->stringCellByIndex($sheet, $map['slno'], $row));
            if ($seq <= 0) {
                $stopStreak++;
                if ($stopStreak >= 3) {
                    break;
                }

                continue;
            }

            $stopStreak = 0;

            $wagonType = isset($map['wagon_type'])
                ? $this->nullableStringCellByIndex($sheet, $map['wagon_type'], $row)
                : null;

            $ownRly = isset($map['wagon_own_rly'])
                ? $this->nullableStringCellByIndex($sheet, $map['wagon_own_rly'], $row)
                : null;

            $wagonNoRaw = mb_trim($this->stringCellByIndex($sheet, $map['wagon_no'], $row));
            if ($wagonNoRaw === '') {
                $stopStreak++;
                if ($stopStreak >= 3) {
                    break;
                }

                continue;
            }

            $wagonNumber = $this->canonicalDumkaKurwaWagonNumber($ownRly, $wagonNoRaw);

            $cc = $this->requiredNumericByIndex($sheet, $map['cc'], $row, 'CC', $wagonNumber);
            $gross = $this->requiredNumericByIndex($sheet, $map['gross'], $row, 'Gross', $wagonNumber);
            $tare = $this->requiredNumericByIndex($sheet, $map['tare'], $row, 'Tare', $wagonNumber);
            $net = $this->requiredNumericByIndex($sheet, $map['net'], $row, 'Net', $wagonNumber);
            $over = $this->requiredNumericByIndex($sheet, $map['overload'], $row, 'Oload', $wagonNumber);
            $under = $this->requiredNumericByIndex($sheet, $map['underload'], $row, 'Uload', $wagonNumber);
            $speed = isset($map['speed'])
                ? $this->optionalNumericByIndex($sheet, $map['speed'], $row, 'Speed')
                : null;

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
     * Owning railway + last five digits of wagon number when both are present; otherwise last five digits from Wagon No only.
     */
    private function canonicalDumkaKurwaWagonNumber(?string $owningRlyRaw, string $wagonNoRaw): string
    {
        $lastFive = $this->lastFiveDigitsFromWagonField($wagonNoRaw);
        if ($lastFive === '') {
            throw new InvalidArgumentException(
                sprintf('Wagon number "%s" has no usable digits for Dumka/Kurwa import.', $wagonNoRaw)
            );
        }

        $own = $owningRlyRaw !== null ? mb_trim($owningRlyRaw) : '';
        $own = $own === '' ? '' : mb_strtoupper(preg_replace('/\s+/', '', $own) ?? '');

        if ($own !== '') {
            return $own.$lastFive;
        }

        return $lastFive;
    }

    private function lastFiveDigitsFromWagonField(string $wagonNoRaw): string
    {
        $digits = preg_replace('/\D/', '', $wagonNoRaw) ?? '';
        if ($digits === '') {
            return '';
        }

        if (mb_strlen($digits) >= 5) {
            return mb_substr($digits, -5);
        }

        return $digits;
    }

    /**
     * @param  array{maxRow:int,maxCol:int}  $bounds
     * @return array{0:int,1:array<string,int>}|null [headerRow, colMap]
     */
    private function findDumkaKurwaTableHeader(Worksheet $sheet, array $bounds): ?array
    {
        $required = [
            'slno' => ['slno', 'sl no', 'sino', 'seq', 'seq no', 'seq. no', 'sl'],
            'wagon_type' => ['wagon type', 'wagontype'],
            'wagon_no' => ['wagon no', 'wagonno', 'wagon number'],
            'cc' => ['cc'],
            'gross' => ['gross'],
            'tare' => ['tare'],
            'net' => ['net'],
            'overload' => ['oload', 'over load', 'overload'],
            'underload' => ['uload', 'under load', 'underload'],
        ];

        $optional = [
            'wagon_own_rly' => [
                'wgonown grly',
                'wgonown',
                'wagon own',
                'wagonown',
                'wagonown grly',
                'owning railway',
                'wagon own rly',
            ],
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

            if (! isset($map['wagon_own_rly'])
                && isset($map['wagon_type'], $map['wagon_no'])
                && $map['wagon_no'] > $map['wagon_type'] + 1) {
                $map['wagon_own_rly'] = $map['wagon_type'] + 1;
            }

            return [$row, $map];
        }

        return null;
    }

    /**
     * @param  array{maxRow:int,maxCol:int}  $bounds
     * @return array{0:int,1:array<string,int>}|null [headerRow, colMap]
     */
    private function findWagonTableHeader(Worksheet $sheet, array $bounds): ?array
    {
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

    /**
     * Trim, strip invisible chars, unify dashes, and map full-width digits so Excel/exports compare reliably.
     */
    private function normalizeRakeNumberCell(string $value): string
    {
        $value = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $value) ?? $value;
        $value = str_replace(["\u{2013}", "\u{2014}", "\u{2212}"], '-', $value);
        $value = mb_trim($value);

        $value = preg_replace_callback('/[\x{FF10}-\x{FF19}]/u', static function (array $m): string {
            return (string) (mb_ord($m[0], 'UTF-8') - 0xFF10 + ord('0'));
        }, $value) ?? $value;

        return $value;
    }

    /**
     * Ordered candidates: fixed template cell (B3), then common header labels. Any one matching the DB rake is accepted.
     *
     * @return list<string>
     */
    private function collectRakeNumberCandidatesFromSheet(Worksheet $sheet): array
    {
        $raw = [];

        $b3 = $this->normalizeRakeNumberCell($this->stringCell($sheet, RakeWeighmentXlsxTemplate::CELLS['rake_number']));
        if ($b3 !== '') {
            $raw[] = $b3;
        }

        foreach (['Rake Number', 'Rake No', 'Rake No.', 'RAKE NO', 'RAKE NUMBER'] as $label) {
            $v = $this->findLabelValueString($sheet, $label);
            if ($v === null) {
                continue;
            }
            $n = $this->normalizeRakeNumberCell($v);
            if ($n !== '') {
                $raw[] = $n;
            }
        }

        $out = [];
        $seen = [];
        foreach ($raw as $s) {
            $key = mb_strtolower($s);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $s;
        }

        return $out;
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
        $parsed = $this->normalizeRakeNumberCell($parsed);
        $expected = $this->normalizeRakeNumberCell($expected);

        $normalize = static function (string $v): string {
            $v = mb_strtoupper(mb_trim(preg_replace('/\s+/', ' ', $v) ?? ''));

            return $v;
        };

        $a = $normalize($parsed);
        $b = $normalize($expected);
        if ($a === $b) {
            return true;
        }

        $stripSidingPrefix = static function (string $v): string {
            foreach (['PKUR-', 'DUMK-', 'KURWA-'] as $prefix) {
                if (str_starts_with($v, $prefix)) {
                    return mb_substr($v, mb_strlen($prefix)) ?: $v;
                }
            }

            return $v;
        };

        $aStripped = $stripSidingPrefix($a);
        $bStripped = $stripSidingPrefix($b);

        if ($aStripped === $bStripped
            || $aStripped === $b
            || $a === $bStripped) {
            return true;
        }

        // Excel often stores rake no. as a number; DB may store "671" vs sheet 671.0 → "671".
        $numericEqual = static function (string $x, string $y): bool {
            $x = mb_trim(str_replace([',', ' '], '', $x));
            $y = mb_trim(str_replace([',', ' '], '', $y));
            if ($x === '' || $y === '') {
                return false;
            }
            if (is_numeric($x) && is_numeric($y)) {
                return abs((float) $x - (float) $y) < 0.00001;
            }

            return false;
        };

        if ($numericEqual($a, $b)) {
            return true;
        }

        // e.g. "PN-670" vs "670", or "RAKE 671" vs "671" — compare last digit group when both contain digits.
        $lastDigitRun = static function (string $v): ?string {
            if (preg_match_all('/\d+/', $v, $matches) !== 0 && $matches[0] !== []) {
                return (string) end($matches[0]);
            }

            return null;
        };

        $da = $lastDigitRun($a);
        $db = $lastDigitRun($b);
        if ($da !== null && $db !== null
            && mb_strlen($da) <= 6 && mb_strlen($db) <= 6
            && (int) $da === (int) $db) {
            return true;
        }

        return false;
    }
}
