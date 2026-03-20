<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DiverrtDestination;
use App\Models\Rake;
use Carbon\Carbon;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

final class ImportBmgkKurwaRakesCommand extends Command
{
    private const int SIDING_ID = 3;

    protected $signature = 'rrmcs:import-bmgk-kurwa {--file= : Absolute or relative xlsx path}';

    protected $description = 'Import BMGK Kurwa rake Excel into rakes and divert destinations';

    /**
     * @var array<string, int>
     */
    private array $columnMap = [];

    public function handle(): int
    {
        $file = $this->resolveFilePath();
        if (! is_file($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $spreadsheet = IOFactory::load($file);
        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'diverts' => 0,
            'sheets' => 0,
        ];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $headerRow = $this->detectHeaderRow($sheet);
            if ($headerRow === null) {
                continue;
            }

            $stats['sheets']++;
            $this->columnMap = $this->buildColumnMap($sheet, $headerRow);
            $highestRow = $sheet->getHighestRow();

            for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
                if (! $this->isDataRow($sheet, $row)) {
                    $stats['skipped']++;

                    continue;
                }

                $payload = $this->extractRakePayload($sheet, $row);
                if ($payload === null) {
                    $stats['skipped']++;

                    continue;
                }

                $rake = $this->upsertRake($payload, $stats);
                if ($rake === null) {
                    $stats['skipped']++;

                    continue;
                }

                $divertedText = $this->extractDiversionText($sheet, $row);
                if ($divertedText === null) {
                    continue;
                }

                $this->upsertDivertDestination($rake, $divertedText, $stats);
            }
        }

        $this->info('BMGK Kurwa import completed.');
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

    private function resolveFilePath(): string
    {
        $option = $this->option('file');
        if (is_string($option) && $option !== '') {
            if (str_starts_with($option, '/')) {
                return $option;
            }

            return base_path($option);
        }

        return database_path('excel/RAKE DETAILS BMGK KURWA.xlsx');
    }

    private function detectHeaderRow(Worksheet $sheet): ?int
    {
        $maxColumn = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $limit = min($sheet->getHighestRow(), 60);

        for ($row = 1; $row <= $limit; $row++) {
            $hasLoadingDate = false;
            $hasRakeNo = false;

            for ($column = 1; $column <= $maxColumn; $column++) {
                $value = mb_strtoupper((string) $sheet->getCellByColumnAndRow($column, $row)->getFormattedValue());

                if (str_contains($value, 'LOADING') && str_contains($value, 'DATE')) {
                    $hasLoadingDate = true;
                }

                if (str_contains($value, 'RAKE') && str_contains($value, 'NO')) {
                    $hasRakeNo = true;
                }
            }

            if ($hasLoadingDate && $hasRakeNo) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return array<string, int>
     */
    private function buildColumnMap(Worksheet $sheet, int $headerRow): array
    {
        $map = [];
        $maxColumn = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        for ($column = 1; $column <= $maxColumn; $column++) {
            $header = mb_strtoupper(mb_trim((string) $sheet->getCellByColumnAndRow($column, $headerRow)->getFormattedValue()));
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
            }
            if (str_contains($header, 'DESTINATION')) {
                $map['destination'] = $column;
            }
            if (str_contains($header, 'INV') && str_contains($header, 'NO')) {
                $map['invoice_no'] = $column;
            }
            if (str_contains($header, 'R R') && str_contains($header, 'NUM')) {
                $map['rr_number'] = $column;
            }
            if (str_contains($header, 'R R') && str_contains($header, 'DATE')) {
                $map['rr_date'] = $column;
            }
            if (str_contains($header, 'PRIORITY')) {
                $map['priority_number'] = $column;
            }
            if (str_contains($header, 'OUT') && str_contains($header, 'WARD')) {
                $map['out_ward_wt'] = $column;
            }
            if (str_contains($header, 'C. C. WT')) {
                $map['chargeable_weight'] = $column;
            }
            if (str_contains($header, 'NET') && str_contains($header, 'WT')) {
                $map['loaded_weight_mt'] = $column;
            }
            if (str_contains($header, 'E. MINING')) {
                $map['e_mining_chalan'] = $column;
            }
            if (str_contains($header, 'PLACEMENT')) {
                $map['placement_time'] = $column;
            }
            if (str_contains($header, 'LOADING') && str_contains($header, 'COMPLETE')) {
                $map['loading_end_time'] = $column;
            }
            if (str_contains($header, 'WEIGHMENT') && str_contains($header, 'PLACE')) {
                $map['weighment_place_and_time'] = $column;
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
        }

        return $map;
    }

    private function isDataRow(Worksheet $sheet, int $row): bool
    {
        $slColumn = 1;
        $slValue = mb_strtoupper(mb_trim((string) $sheet->getCellByColumnAndRow($slColumn, $row)->getFormattedValue()));

        if ($slValue === '' || str_contains($slValue, 'TOTAL') || str_contains($slValue, 'NOTE')) {
            return false;
        }

        if (isset($this->columnMap['rake_number'])) {
            $rake = $this->cleanInt($sheet->getCellByColumnAndRow($this->columnMap['rake_number'], $row)->getFormattedValue());

            return $rake !== null;
        }

        return false;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractRakePayload(Worksheet $sheet, int $row): ?array
    {
        $rakeNumber = $this->getIntByKey($sheet, $row, 'rake_number');
        if ($rakeNumber === null) {
            return null;
        }

        $loadingDate = $this->getDateByKey($sheet, $row, 'loading_date');
        $rrNumber = $this->getTextByKey($sheet, $row, 'rr_number');
        $rrDate = $this->getDateByKey($sheet, $row, 'rr_date');
        [$weighmentPlace, $weighmentAt] = $this->parseWeighmentValue($this->getTextByKey($sheet, $row, 'weighment_place_and_time'));

        return [
            'siding_id' => self::SIDING_ID,
            'rake_number' => $rakeNumber,
            'loading_date' => $loadingDate?->toDateString(),
            'rake_type' => $this->getTextByKey($sheet, $row, 'rake_type'),
            'wagon_count' => $this->getIntByKey($sheet, $row, 'wagon_count'),
            'destination' => $this->getTextByKey($sheet, $row, 'destination'),
            'invoice_no' => $this->getTextByKey($sheet, $row, 'invoice_no'),
            'rr_number' => $rrNumber,
            'rr_actual_date' => $rrDate?->startOfDay()->toDateTimeString(),
            'priority_number' => $this->getIntByKey($sheet, $row, 'priority_number'),
            'out_ward_wt' => $this->getFloatByKey($sheet, $row, 'out_ward_wt'),
            'chargeable_weight' => $this->getFloatByKey($sheet, $row, 'chargeable_weight'),
            'loaded_weight_mt' => $this->getFloatByKey($sheet, $row, 'loaded_weight_mt'),
            'e_mining_chalan' => $this->getTextByKey($sheet, $row, 'e_mining_chalan'),
            'placement_time' => $this->getDateTimeByKey($sheet, $row, 'placement_time')?->toDateTimeString(),
            'loading_end_time' => $this->getDateTimeByKey($sheet, $row, 'loading_end_time')?->toDateTimeString(),
            'weighment_place' => $weighmentPlace,
            'weighment_end_time' => $weighmentAt?->toDateTimeString(),
            'arrival_time' => $this->getTimeOrDateTimeByKey($sheet, $row, 'arrival_time')?->toDateTimeString(),
            'drawn_out' => $this->getTimeOrDateTimeByKey($sheet, $row, 'drawn_out')?->toDateTimeString(),
            'under_load_mt' => $this->getFloatByKey($sheet, $row, 'under_load_mt'),
            'over_load_mt' => $this->getFloatByKey($sheet, $row, 'over_load_mt'),
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
        $query = Rake::query()->where('siding_id', self::SIDING_ID);
        $rrNumber = $payload['rr_number'];

        if (is_string($rrNumber) && $rrNumber !== '') {
            $query->where('rr_number', $rrNumber);
        } else {
            $query->where('rake_number', (string) $payload['rake_number']);
            if (is_string($payload['loading_date']) && $payload['loading_date'] !== '') {
                $query->whereDate('loading_date', $payload['loading_date']);
            }
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
            $srrDate = $this->parseDate($matches[3])?->toDateString();
        }

        $divert = DiverrtDestination::query()
            ->where('rake_id', $rake->id)
            ->where('location', $location)
            ->where('rr_number', $srrRrNumber)
            ->where('stt_no', $sttNo)
            ->whereDate('srr_date', $srrDate)
            ->first();

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

    private function extractDiversionText(Worksheet $sheet, int $row): ?string
    {
        $maxColumn = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        for ($column = 1; $column <= $maxColumn; $column++) {
            $value = mb_trim((string) $sheet->getCellByColumnAndRow($column, $row)->getFormattedValue());
            if ($value === '') {
                continue;
            }

            if (mb_stripos($value, 'DIVERTED TO') !== false) {
                return $value;
            }
        }

        return null;
    }

    private function getTextByKey(Worksheet $sheet, int $row, string $key): ?string
    {
        if (! isset($this->columnMap[$key])) {
            return null;
        }

        return $this->cleanText($sheet->getCellByColumnAndRow($this->columnMap[$key], $row)->getFormattedValue());
    }

    private function getIntByKey(Worksheet $sheet, int $row, string $key): ?int
    {
        if (! isset($this->columnMap[$key])) {
            return null;
        }

        return $this->cleanInt($sheet->getCellByColumnAndRow($this->columnMap[$key], $row)->getFormattedValue());
    }

    private function getFloatByKey(Worksheet $sheet, int $row, string $key): ?float
    {
        if (! isset($this->columnMap[$key])) {
            return null;
        }

        return $this->cleanFloat($sheet->getCellByColumnAndRow($this->columnMap[$key], $row)->getFormattedValue());
    }

    private function getDateByKey(Worksheet $sheet, int $row, string $key): ?Carbon
    {
        if (! isset($this->columnMap[$key])) {
            return null;
        }

        return $this->parseDate($sheet->getCellByColumnAndRow($this->columnMap[$key], $row)->getFormattedValue());
    }

    private function getDateTimeByKey(Worksheet $sheet, int $row, string $key): ?Carbon
    {
        if (! isset($this->columnMap[$key])) {
            return null;
        }

        return $this->parseDateTime($sheet->getCellByColumnAndRow($this->columnMap[$key], $row)->getFormattedValue());
    }

    private function getTimeOrDateTimeByKey(Worksheet $sheet, int $row, string $key): ?Carbon
    {
        if (! isset($this->columnMap[$key])) {
            return null;
        }

        return $this->parseTimeOrDateTime($sheet->getCellByColumnAndRow($this->columnMap[$key], $row)->getFormattedValue());
    }

    /**
     * @return array{0: string|null, 1: Carbon|null}
     */
    private function parseWeighmentValue(?string $value): array
    {
        if (! is_string($value) || $value === '') {
            return [null, null];
        }

        if (preg_match('/^([A-Z0-9]+)\\s+([0-9]{1,2}:[0-9]{2})\\s*\\/\\s*([0-9\\.]{8,10})$/i', $value, $matches) === 1) {
            $parsedDate = $this->parseDate($matches[3]);
            if ($parsedDate instanceof Carbon) {
                $dateTime = $this->parseDateTime($matches[2].' / '.$parsedDate->format('d.m.y'));

                return [mb_strtoupper($matches[1]), $dateTime];
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

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        $formats = ['d.m.Y', 'd.m.y', 'd-m-y', 'd-m-Y', 'Y-m-d'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, mb_trim((string) $value));
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    private function parseDateTime(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        $formats = ['H:i / d.m.y', 'H:i / d.m.Y', 'H:i/d.m.y', 'H:i/d.m.Y'];
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
