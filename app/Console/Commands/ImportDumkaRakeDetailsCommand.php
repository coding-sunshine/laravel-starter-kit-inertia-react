<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DiverrtDestination;
use App\Models\Rake;
use App\Models\Siding;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

final class ImportDumkaRakeDetailsCommand extends Command
{
    /**
     * --purge removes ONLY rakes for siding DUMK with data_source=historical_import (and their
     * diverrt_destination rows). No other sidings, sources, or tables are touched.
     */
    protected $signature = 'rrmcs:import-dumka-rake-details
                            {--file= : Absolute or relative xlsx path}
                            {--purge : Re-import helper: delete only DUMK rakes with data_source historical_import and their divert rows (nothing else)}';

    protected $description = 'Import Dumka rake Excel (scoped purge: only DUMK + historical_import rakes)';

    private int $sidingId;

    /**
     * @var array<string, int>
     */
    private array $columnMap = [];

    /**
     * Existing rakes for this siding keyed by rr_number (avoids one SELECT per imported row).
     *
     * @var array<string, Rake>
     */
    private array $rakesByRrNumber = [];

    public function handle(): int
    {
        $siding = Siding::query()->where('code', 'DUMK')->first();
        if ($siding === null) {
            $this->error('No siding with code DUMK found. Seed sidings before importing.');

            return self::FAILURE;
        }

        $this->sidingId = (int) $siding->id;

        $file = $this->resolveFilePath();
        if (! is_file($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        if ($this->option('purge')) {
            $this->purgeDumkaHistoricalRakesOnly();
        }

        $spreadsheet = IOFactory::load($file);
        $this->logVerbose("Loaded workbook: {$file}");
        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'diverts' => 0,
            'sheets' => 0,
        ];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $title = $sheet->getTitle();
            if ($this->shouldSkipSheetByName($title)) {
                $this->logVerbose("Skipping sheet (name): \"{$title}\"");

                continue;
            }

            $headerRow = $this->detectHeaderRow($sheet);
            if ($headerRow === null) {
                $this->warn("No header row found in sheet \"{$title}\" — skipped.");

                continue;
            }

            $stats['sheets']++;
            $this->columnMap = $this->buildColumnMap($sheet, $headerRow);

            if (! $this->sheetHasRequiredColumns()) {
                $this->warn("Sheet \"{$title}\" missing required columns (loading_date, rake_number, rr_number, destination) — skipped.");
                $stats['sheets']--;

                continue;
            }

            $this->info(sprintf(
                'Sheet "%s": header row %d; mapped: %s',
                $title,
                $headerRow,
                implode(', ', array_keys($this->columnMap))
            ));
            $this->logVerbose('Column index map: '.json_encode($this->columnMap, JSON_THROW_ON_ERROR));

            $highestRow = max($headerRow + 1, $sheet->getHighestDataRow());

            DB::transaction(function () use ($sheet, $headerRow, $highestRow, $title, &$stats): void {
                $rowMatrix = $this->readSheetRowsAsMatrix($sheet, $headerRow + 1, $highestRow);
                foreach ($rowMatrix as $rowIndex => $rowValues) {
                    $row = $headerRow + 1 + $rowIndex;
                    if (! $this->isDataRowArray($rowValues)) {
                        $stats['skipped']++;

                        continue;
                    }

                    $payload = $this->extractRakePayloadFromRowArray($rowValues, $title, $row);
                    if ($payload === null) {
                        $stats['skipped']++;

                        continue;
                    }

                    $rake = $this->upsertRake($payload, $stats);
                    if ($rake === null) {
                        $stats['skipped']++;

                        continue;
                    }

                    $this->logVerbose(sprintf(
                        '%s row %d: rr=%s rake#=%s loading_date=%s',
                        $title,
                        $row,
                        (string) ($payload['rr_number'] ?? ''),
                        (string) ($payload['rake_number'] ?? ''),
                        (string) ($payload['loading_date'] ?? '')
                    ));

                    $divertedText = $this->extractDiversionTextFromRowArray($rowValues);
                    if ($divertedText === null) {
                        continue;
                    }

                    $this->upsertDivertDestination($rake, $divertedText, $stats);
                }
            });
        }

        $this->info('Dumka rake details import completed.');
        $this->table(
            ['sheets', 'created', 'updated', 'skipped', 'divert_records'],
            [[
                $stats['sheets'],
                $stats['created'],
                $stats['updated'],
                $stats['skipped'],
                $stats['diverts'],
            ]]
        );

        return self::SUCCESS;
    }

    /**
     * Strict scope: siding_id = this DUMK siding, data_source = historical_import only.
     */
    private function purgeDumkaHistoricalRakesOnly(): void
    {
        $rakeIds = Rake::query()
            ->where('siding_id', $this->sidingId)
            ->where('data_source', 'historical_import')
            ->pluck('id');

        if ($rakeIds->isEmpty()) {
            $this->info('Purge: no rows matched (only DUMK + data_source=historical_import).');

            return;
        }

        $deletedDiverts = 0;
        $deletedRakes = 0;

        DB::transaction(function () use ($rakeIds, &$deletedDiverts, &$deletedRakes): void {
            $deletedDiverts = DiverrtDestination::query()->whereIn('rake_id', $rakeIds)->delete();
            $deletedRakes = Rake::query()->whereIn('id', $rakeIds)->delete();
        });

        $this->info("Purge: deleted {$deletedRakes} rakes and {$deletedDiverts} divert rows (DUMK siding, historical_import only).");
    }

    private function refreshRakeIndexForSiding(): void
    {
        $this->rakesByRrNumber = Rake::query()
            ->where('siding_id', $this->sidingId)
            ->whereNotNull('rr_number')
            ->where('rr_number', '!=', '')
            ->get()
            ->keyBy(fn (Rake $r): string => (string) $r->rr_number)
            ->all();
    }

    /**
     * One range read per sheet (raw values; Excel date serials preserved) — much faster than per-cell getFormattedValue().
     *
     * @return list<array<int, mixed>>
     */
    private function readSheetRowsAsMatrix(Worksheet $sheet, int $firstRow, int $lastRow): array
    {
        if ($firstRow > $lastRow) {
            return [];
        }

        $lastColLetter = $sheet->getHighestDataColumn();
        $range = 'A'.$firstRow.':'.$lastColLetter.$lastRow;
        $matrix = $sheet->rangeToArray($range, null, true, false, false);

        return array_is_list($matrix) ? $matrix : array_values($matrix);
    }

    /**
     * @param  array<int, mixed>  $rowValues
     */
    private function rowArrayCell(array $rowValues, int $columnIndex1Based): mixed
    {
        $i = $columnIndex1Based - 1;

        return $rowValues[$i] ?? null;
    }

    /**
     * Columns to scan for "DIVERTED TO" (mapped data columns only — not the entire sheet width).
     *
     * @return list<int>
     */
    private function divertScanColumnIndices(): array
    {
        $cols = array_values(array_unique(array_values($this->columnMap)));
        sort($cols);

        return array_values($cols);
    }

    private function shouldSkipSheetByName(string $title): bool
    {
        $upper = mb_strtoupper(mb_trim($title));

        return str_contains($upper, 'KUMAR')
            || str_contains($upper, 'ABSTRACT')
            || str_contains($upper, 'KILOMETER');
    }

    private function sheetHasRequiredColumns(): bool
    {
        foreach (['loading_date', 'rake_number', 'rr_number', 'destination'] as $key) {
            if (! isset($this->columnMap[$key])) {
                return false;
            }
        }

        return true;
    }

    private function logVerbose(string $message): void
    {
        if ($this->output->isVerbose()) {
            $this->line("<fg=gray>{$message}</>");
        }
    }

    private function resolveFilePath(): string
    {
        $option = $this->option('file');
        if (is_string($option) && $option !== '') {
            if (str_starts_with($option, '/')) {
                return $option;
            }

            return base_path($option);
        }

        return database_path('excel/DUMKA RAKE DETAILS WITH FRIGHT.xlsx');
    }

    private function detectHeaderRow(Worksheet $sheet): ?int
    {
        $maxColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $limit = min($sheet->getHighestDataRow(), 60);

        for ($row = 1; $row <= $limit; $row++) {
            $hasLoadingDate = false;
            $hasRakeNo = false;

            for ($column = 1; $column <= $maxColumn; $column++) {
                $normalized = $this->normalizeHeaderText(
                    (string) $sheet->getCellByColumnAndRow($column, $row)->getFormattedValue()
                );

                if (str_contains($normalized, 'LOADING') && str_contains($normalized, 'DATE')) {
                    $hasLoadingDate = true;
                }

                if (str_contains($normalized, 'RAKE') && str_contains($normalized, 'NO')) {
                    $hasRakeNo = true;
                }
            }

            if ($hasLoadingDate && $hasRakeNo) {
                return $row;
            }
        }

        return null;
    }

    private function normalizeHeaderText(string $header): string
    {
        $h = mb_strtoupper(mb_trim($header));
        $h = str_replace(["\u{2019}", "'", '.'], ' ', $h);

        return preg_replace('/\s+/', ' ', $h) ?? $h;
    }

    /**
     * @return array<string, int>
     */
    private function buildColumnMap(Worksheet $sheet, int $headerRow): array
    {
        $map = [];
        $maxColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        for ($column = 1; $column <= $maxColumn; $column++) {
            $rawHeader = (string) $sheet->getCellByColumnAndRow($column, $headerRow)->getFormattedValue();
            $header = $this->normalizeHeaderText($rawHeader);
            if ($header === '') {
                continue;
            }

            if (str_contains($header, 'LOADING') && str_contains($header, 'DATE')) {
                $map['loading_date'] = $column;
            }
            if (str_contains($header, 'RAKE') && str_contains($header, 'NO')) {
                $map['rake_number'] = $column;
            }
            if (str_contains($header, 'NO OF') && str_contains($header, 'WAGON')) {
                if (str_contains($header, 'UNFIT')) {
                    $map['unfit_wagon_count'] = $column;
                } elseif (str_contains($header, 'LOADED')) {
                    $map['wagon_count'] = $column;
                } elseif (! isset($map['wagon_count'])) {
                    $map['wagon_count'] = $column;
                }
            }
            if (str_contains($header, 'WAGON TYPE')) {
                $map['rake_type'] = $column;
            } elseif (
                str_contains($header, 'WAGON')
                && ! str_contains($header, 'NO')
                && ! str_contains($header, 'TYPE')
                && ! str_contains($header, 'UNFIT')
                && ! isset($map['rake_type'])
            ) {
                $map['rake_type'] = $column;
            }
            if (str_contains($header, 'DESTINATION')) {
                $map['destination'] = $column;
            }
            if ((str_contains($header, 'INV') || str_contains($header, 'IVC')) && str_contains($header, 'NO')) {
                $map['invoice_no'] = $column;
            }
            if ($this->headerLooksLikeRrNumberColumn($header)) {
                $map['rr_number'] = $column;
            }
            if ((str_contains($header, 'R R') || preg_match('/\bRR\b/', $header) === 1) && str_contains($header, 'DATE')) {
                $map['rr_date'] = $column;
            }
            if (str_contains($header, 'PRIORITY')) {
                $map['priority_number'] = $column;
            }
            if (
                (str_contains($header, 'OUT') && str_contains($header, 'WARD'))
                || (str_contains($header, 'OUTWARD') && (str_contains($header, 'WT') || str_contains($header, 'WEIGHT')))
            ) {
                $map['out_ward_wt'] = $column;
            }
            if (str_contains($header, 'C. C. WT') || (str_contains($header, 'CARRYING') && str_contains($header, 'CAPACITY'))) {
                $map['chargeable_weight'] = $column;
            }
            if (str_contains($header, 'NET') && str_contains($header, 'WT')) {
                $map['loaded_weight_mt'] = $column;
            }
            if (str_contains($header, 'E. MINING') || (str_contains($header, 'MINING') && str_contains($header, 'CHALLAN'))) {
                $map['e_mining_chalan'] = $column;
            }
            if (str_contains($header, 'PLACEMENT') && ! str_contains($header, 'WEIGHMENT')) {
                $map['placement_time'] = $column;
            }
            if (str_contains($header, 'LOADING') && str_contains($header, 'COMPLETE')) {
                $map['loading_end_time'] = $column;
            }
            if (str_contains($header, 'WEIGHMENT') && str_contains($header, 'PLACE')) {
                if (str_contains($header, 'TIME') || str_contains($header, ' AND ')) {
                    $map['weighment_place_and_time'] = $column;
                } elseif (! isset($map['weighment_place_plain'])) {
                    $map['weighment_place_plain'] = $column;
                }
            }
            if (str_contains($header, 'ARRIVAL')) {
                $map['arrival_time'] = $column;
            }
            if (str_contains($header, 'DRAWN')) {
                $map['drawn_out'] = $column;
            }
            if (str_contains($header, 'UNDER') && str_contains($header, 'LOAD')) {
                $map['under_load_mt'] = $column;
            }
            if (str_contains($header, 'OVER') && str_contains($header, 'LOAD')) {
                $map['over_load_mt'] = $column;
            }
            if (str_contains($header, 'REMARK')) {
                $map['remarks'] = $column;
            }
        }

        return $map;
    }

    private function headerLooksLikeRrNumberColumn(string $header): bool
    {
        if (str_contains($header, 'DATE')) {
            return false;
        }

        $hasRr = str_contains($header, 'R R') || preg_match('/\bRR\b/', $header) === 1;
        $hasNum = str_contains($header, 'NUM') || str_contains($header, 'NUMBER') || preg_match('/\bNO\b/', $header) === 1;

        return $hasRr && $hasNum;
    }

    /**
     * @param  array<int, mixed>  $rowValues
     */
    private function isDataRowArray(array $rowValues): bool
    {
        $slValue = mb_strtoupper(mb_trim($this->stringFromMatrixCell($this->rowArrayCell($rowValues, 1))));

        if ($slValue === '' || str_contains($slValue, 'TOTAL') || str_contains($slValue, 'NOTE')) {
            return false;
        }

        if (isset($this->columnMap['rake_number'])) {
            $rake = $this->cleanInt($this->rowArrayCell($rowValues, $this->columnMap['rake_number']));

            return $rake !== null;
        }

        return false;
    }

    /**
     * @param  array<int, mixed>  $rowValues
     * @return array<string, mixed>|null
     */
    private function extractRakePayloadFromRowArray(array $rowValues, string $sheetTitle, int $rowIndex): ?array
    {
        $rakeNumber = $this->getIntFromRowArray($rowValues, 'rake_number');
        if ($rakeNumber === null) {
            return null;
        }

        $loadingDate = $this->getDateFromRowArray($rowValues, 'loading_date');
        if ($loadingDate === null) {
            $this->logVerbose("{$sheetTitle} row {$rowIndex}: skip — could not parse loading_date");

            return null;
        }

        $rrNumber = $this->getTextFromRowArray($rowValues, 'rr_number');
        if ($rrNumber === null || $rrNumber === '') {
            $this->logVerbose("{$sheetTitle} row {$rowIndex}: skip — missing rr_number");

            return null;
        }

        $destination = $this->getTextFromRowArray($rowValues, 'destination');
        if ($destination === null || $destination === '') {
            $this->logVerbose("{$sheetTitle} row {$rowIndex}: skip — missing destination");

            return null;
        }

        $rrDate = $this->getDateFromRowArray($rowValues, 'rr_date');
        $combinedWeighment = $this->getTextFromRowArray($rowValues, 'weighment_place_and_time');
        $plainWeighmentPlace = $this->getTextFromRowArray($rowValues, 'weighment_place_plain');

        if (is_string($combinedWeighment) && $combinedWeighment !== '') {
            [$weighmentPlace, $weighmentAt] = $this->parseWeighmentValue($combinedWeighment);
        } elseif (is_string($plainWeighmentPlace) && $plainWeighmentPlace !== '') {
            $weighmentPlace = $plainWeighmentPlace;
            $weighmentAt = null;
        } else {
            $weighmentPlace = null;
            $weighmentAt = null;
        }

        $destinationCode = mb_strtoupper($destination);

        return [
            'siding_id' => $this->sidingId,
            'rake_number' => $rakeNumber,
            'loading_date' => $loadingDate->toDateString(),
            'rake_type' => $this->getTextFromRowArray($rowValues, 'rake_type'),
            'wagon_count' => $this->getIntFromRowArray($rowValues, 'wagon_count'),
            'destination' => $destination,
            'destination_code' => $destinationCode,
            'invoice_no' => $this->getTextFromRowArray($rowValues, 'invoice_no'),
            'rr_number' => $rrNumber,
            'rr_actual_date' => $rrDate?->startOfDay()->toDateTimeString(),
            'priority_number' => $this->getIntFromRowArray($rowValues, 'priority_number'),
            'out_ward_wt' => $this->getFloatFromRowArray($rowValues, 'out_ward_wt'),
            'chargeable_weight' => $this->getFloatFromRowArray($rowValues, 'chargeable_weight'),
            'loaded_weight_mt' => $this->getFloatFromRowArray($rowValues, 'loaded_weight_mt'),
            'e_mining_chalan' => $this->getTextFromRowArray($rowValues, 'e_mining_chalan'),
            'placement_time' => $this->getDateTimeFromRowArray($rowValues, 'placement_time')?->toDateTimeString(),
            'loading_end_time' => $this->getDateTimeFromRowArray($rowValues, 'loading_end_time')?->toDateTimeString(),
            'weighment_place' => $weighmentPlace,
            'weighment_end_time' => $weighmentAt?->toDateTimeString(),
            'arrival_time' => $this->getTimeOrDateTimeFromRowArray($rowValues, 'arrival_time')?->toDateTimeString(),
            'drawn_out' => $this->getTimeOrDateTimeFromRowArray($rowValues, 'drawn_out')?->toDateTimeString(),
            'under_load_mt' => $this->getFloatFromRowArray($rowValues, 'under_load_mt'),
            'over_load_mt' => $this->getFloatFromRowArray($rowValues, 'over_load_mt'),
            'remarks' => $this->getTextFromRowArray($rowValues, 'remarks'),
            'state' => 'completed',
            'data_source' => 'historical_import',
            'is_diverted' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, int>  $stats
     */
    private function upsertRake(array $payload, array &$stats): ?Rake
    {
        $rrNumber = $payload['rr_number'];

        if (is_string($rrNumber) && $rrNumber !== '') {
            $existing = $this->rakesByRrNumber[$rrNumber] ?? null;
            if ($existing instanceof Rake) {
                $existing->fill($payload);
                $existing->save();
                $stats['updated']++;

                return $existing;
            }

            $stats['created']++;
            $rake = Rake::query()->create($payload);
            $this->rakesByRrNumber[$rrNumber] = $rake;

            return $rake;
        }

        $query = Rake::query()->where('siding_id', $this->sidingId)
            ->where('rake_number', (string) $payload['rake_number']);
        if (is_string($payload['loading_date']) && $payload['loading_date'] !== '') {
            $query->whereDate('loading_date', $payload['loading_date']);
        }

        $rake = $query->first();
        if ($rake instanceof Rake) {
            $rake->fill($payload);
            $rake->save();
            $stats['updated']++;

            return $rake;
        }

        $stats['created']++;

        return Rake::query()->create($payload);
    }

    /**
     * @param  array<string, int>  $stats
     */
    private function upsertDivertDestination(Rake $rake, string $note, array &$stats): void
    {
        $location = null;
        if (preg_match('/DIVERTED\\s+TO\\s+([A-Z0-9]+)/i', $note, $locationMatches) === 1) {
            $location = mb_strtoupper($locationMatches[1]);
        }

        if ($location === null) {
            return;
        }

        $sttNo = null;
        $srrRrNumber = null;
        $srrDate = null;

        if (preg_match('/SRR\\s*NO\\s*[:\\-\\s]*\\s*(\\d+)\\s*\\/\\s*(\\d+)\\s*[\\/-]\\s*([0-9\\.]+)/i', $note, $matches) === 1) {
            $sttNo = $matches[1];
            $srrRrNumber = $matches[2];
            $srrDate = $this->parseDateString($matches[3])?->toDateString();
        } elseif (preg_match('/SRR\\s*-\\s*(\\d+)\\s*\\/\\s*(\\d+)\\s*\\/\\s*([0-9\\.]+)/i', $note, $dumkaMatches) === 1) {
            $sttNo = $dumkaMatches[1];
            $srrRrNumber = $dumkaMatches[2];
            $srrDate = $this->parseDateString($dumkaMatches[3])?->toDateString();
        }

        $divertQuery = DiverrtDestination::query()
            ->where('rake_id', $rake->id)
            ->where('location', $location)
            ->where('rr_number', $srrRrNumber)
            ->where('stt_no', $sttNo);

        if ($srrDate !== null) {
            $divertQuery->whereDate('srr_date', $srrDate);
        } else {
            $divertQuery->whereNull('srr_date');
        }

        $divert = $divertQuery->first();

        if ($divert === null) {
            DiverrtDestination::query()->create([
                'rake_id' => $rake->id,
                'location' => $location,
                'rr_number' => $srrRrNumber,
                'stt_no' => $sttNo,
                'srr_date' => $srrDate,
                'data_source' => 'historical_import',
            ]);
            $stats['diverts']++;
        } elseif ($divert->data_source !== 'historical_import') {
            $divert->data_source = 'historical_import';
            $divert->save();
        }

        if (! $rake->is_diverted) {
            $rake->is_diverted = true;
            $rake->save();
        }
    }

    /**
     * @param  array<int, mixed>  $rowValues
     */
    private function extractDiversionTextFromRowArray(array $rowValues): ?string
    {
        foreach ($this->divertScanColumnIndices() as $column) {
            $value = mb_trim($this->stringFromMatrixCell($this->rowArrayCell($rowValues, $column)));
            if ($value === '') {
                continue;
            }

            if (mb_stripos($value, 'DIVERTED TO') !== false) {
                return $value;
            }
        }

        return null;
    }

    private function stringFromMatrixCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_float($value)) {
            if (abs($value - round($value)) < 1e-9 && abs($value) < 1e15) {
                return sprintf('%.0f', $value);
            }
        }

        return mb_trim((string) $value);
    }

    /**
     * @param  array<int, mixed>  $rowValues
     */
    private function getTextFromRowArray(array $rowValues, string $key): ?string
    {
        if (! isset($this->columnMap[$key])) {
            return null;
        }

        return $this->cleanText($this->stringFromMatrixCell($this->rowArrayCell($rowValues, $this->columnMap[$key])));
    }

    /**
     * @param  array<int, mixed>  $rowValues
     */
    private function getIntFromRowArray(array $rowValues, string $key): ?int
    {
        if (! isset($this->columnMap[$key])) {
            return null;
        }

        return $this->cleanInt($this->rowArrayCell($rowValues, $this->columnMap[$key]));
    }

    /**
     * @param  array<int, mixed>  $rowValues
     */
    private function getFloatFromRowArray(array $rowValues, string $key): ?float
    {
        if (! isset($this->columnMap[$key])) {
            return null;
        }

        return $this->cleanFloat($this->rowArrayCell($rowValues, $this->columnMap[$key]));
    }

    /**
     * @param  array<int, mixed>  $rowValues
     */
    private function getDateFromRowArray(array $rowValues, string $key): ?Carbon
    {
        if (! isset($this->columnMap[$key])) {
            return null;
        }

        return $this->parseDateFromRaw($this->rowArrayCell($rowValues, $this->columnMap[$key]));
    }

    /**
     * @param  array<int, mixed>  $rowValues
     */
    private function getDateTimeFromRowArray(array $rowValues, string $key): ?Carbon
    {
        if (! isset($this->columnMap[$key])) {
            return null;
        }

        return $this->parseDateTimeFromRaw($this->rowArrayCell($rowValues, $this->columnMap[$key]));
    }

    /**
     * @param  array<int, mixed>  $rowValues
     */
    private function getTimeOrDateTimeFromRowArray(array $rowValues, string $key): ?Carbon
    {
        if (! isset($this->columnMap[$key])) {
            return null;
        }

        $raw = $this->rowArrayCell($rowValues, $this->columnMap[$key]);
        $parsed = $this->parseDateTimeFromRaw($raw);
        if ($parsed instanceof Carbon) {
            return $parsed;
        }

        return $this->parseTimeOrDateTime($this->stringFromMatrixCell($raw));
    }

    private function parseDateFromRaw(mixed $raw): ?Carbon
    {
        if (is_numeric($raw)) {
            $n = (float) $raw;
            if ($this->looksLikeExcelDateSerial($n)) {
                try {
                    $dt = ExcelDate::excelToDateTimeObject($n);

                    return Carbon::parse($dt)->startOfDay();
                } catch (Throwable) {
                    // string fallback
                }
            }
        }

        $text = '';
        if (is_string($raw)) {
            $text = mb_trim($raw);
        } elseif (is_scalar($raw) && (string) $raw !== '') {
            $text = mb_trim((string) $raw);
        }

        return $this->parseDateString($text)?->startOfDay();
    }

    private function parseDateTimeFromRaw(mixed $raw): ?Carbon
    {
        if (is_numeric($raw)) {
            $n = (float) $raw;
            if ($this->looksLikeExcelDateSerial($n)) {
                try {
                    $dt = ExcelDate::excelToDateTimeObject($n);

                    return Carbon::parse($dt);
                } catch (Throwable) {
                    // fall through
                }
            }
        }

        $text = '';
        if (is_string($raw)) {
            $text = mb_trim($raw);
        } elseif (is_scalar($raw) && (string) $raw !== '') {
            $text = mb_trim((string) $raw);
        }

        return $this->parseDateTime($text !== '' ? $text : null);
    }

    /**
     * Excel calendar serials for ~1980–2100; excludes RR numbers (e.g. 461000035) and small ints (rake counts).
     */
    private function looksLikeExcelDateSerial(float $n): bool
    {
        $whole = (int) floor($n);

        return $whole >= 25000 && $whole <= 65000 && $n < 100000;
    }

    /**
     * @return array{0: string|null, 1: Carbon|null}
     */
    private function parseWeighmentValue(?string $value): array
    {
        if (! is_string($value) || $value === '') {
            return [null, null];
        }

        $trimmed = mb_trim($value);

        if (preg_match('/^([A-Z0-9]+)\\s+([0-9]{1,2}:[0-9]{2})\\s*\\/\\s*([0-9\\.]{8,10})$/i', $trimmed, $matches) === 1) {
            $parsedDate = $this->parseDateString($matches[3]);
            if ($parsedDate instanceof Carbon) {
                $dateTime = $this->parseDateTime($matches[2].' / '.$parsedDate->format('d.m.y'));

                return [mb_strtoupper($matches[1]), $dateTime];
            }
        }

        if (preg_match('/^([A-Z0-9]{2,10})\\s+([0-9]{1,2}:[0-9]{2})\\/([0-9]{1,2}\\.[0-9]{1,2}\\.[0-9]{2,4})$/i', $trimmed, $compact) === 1) {
            $parsedDate = $this->parseDateString($compact[3]);
            if ($parsedDate instanceof Carbon) {
                $dateTime = $this->parseDateTime($compact[2].' / '.$parsedDate->format('d.m.y'));

                return [mb_strtoupper($compact[1]), $dateTime];
            }
        }

        return [$value, null];
    }

    private function cleanText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = mb_trim((string) $value);
        if ($text === '' || str_starts_with($text, '=')) {
            return null;
        }

        return $text;
    }

    private function cleanInt(mixed $value): ?int
    {
        $float = $this->cleanFloat($value);

        return $float === null ? null : (int) $float;
    }

    private function cleanFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = str_replace(',', '', mb_trim($value));
            if ($value === '' || str_starts_with($value, '=')) {
                return null;
            }
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    /**
     * Loading / RR dates in the Dumka workbook are always day–month–year. Detect separator
     * (. / -), split into three parts, then build Carbon. Two-digit years use 20yy (file range ~2022–2026).
     */
    private function parseDateString(string $value): ?Carbon
    {
        $value = mb_trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{4}[.\/-]\d{1,2}[.\/-]\d{1,2}|\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})/', $value, $m) === 1) {
            $value = $m[1];
        }

        return $this->parseDateAsDayMonthYear($value);
    }

    private function parseDateAsDayMonthYear(string $value): ?Carbon
    {
        $separator = null;
        foreach (['.', '/', '-'] as $candidate) {
            if (str_contains($value, $candidate)) {
                $separator = $candidate;

                break;
            }
        }

        if ($separator === null) {
            return null;
        }

        $parts = explode($separator, $value);
        if (count($parts) !== 3) {
            return null;
        }

        $p0 = mb_trim($parts[0]);
        $p1 = mb_trim($parts[1]);
        $p2 = mb_trim($parts[2]);

        if (mb_strlen($p0) === 4 && ctype_digit($p0) && (int) $p0 >= 1900) {
            $year = (int) $p0;
            $month = (int) $p1;
            $day = (int) $p2;
        } else {
            $day = (int) $p0;
            $month = (int) $p1;
            $yearPart = $p2;

            if ($day < 1 || $day > 31 || $month < 1 || $month > 12) {
                return null;
            }

            $yearLength = mb_strlen($yearPart);
            if ($yearLength === 4) {
                $year = (int) $yearPart;
            } elseif ($yearLength === 2) {
                $year = 2000 + (int) $yearPart;
            } else {
                return null;
            }
        }

        if ($day < 1 || $day > 31 || $month < 1 || $month > 12) {
            return null;
        }

        if ($year < 2020 || $year > 2035) {
            return null;
        }

        if (! checkdate($month, $day, $year)) {
            return null;
        }

        try {
            return Carbon::createFromDate($year, $month, $day)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    private function parseDateTime(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        $formats = ['H:i / d.m.y', 'H:i / d.m.Y', 'H:i/d.m.y', 'H:i/d.m.Y', 'H:i/d/m/y', 'H:i / d/m/y'];
        $normalized = preg_replace('/\\s+/', ' ', mb_trim((string) $value));
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $normalized);
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    private function parseTimeOrDateTime(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        $dateTime = $this->parseDateTime($value);
        if ($dateTime instanceof Carbon) {
            return $dateTime;
        }

        $text = mb_trim((string) $value);
        try {
            return Carbon::createFromFormat('H:i', $text)->setDateFrom(now());
        } catch (Throwable) {
            return null;
        }
    }
}
