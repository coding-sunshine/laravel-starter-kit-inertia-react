<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Loader;
use App\Models\Penalty;
use App\Models\Rake;
use App\Models\RrDocument;
use App\Models\Siding;
use App\Models\Wagon;
use App\Models\Weighment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Import historical rake/penalty/weighment data from PRD Excel files.
 *
 * Reads Excel files under config('rrmcs.prd_import') and creates Rake, Penalty,
 * Weighment, and RrDocument records. PDFs in the PRD folder are reference-only
 * (scanned RR samples); only .xlsx/.xls are imported.
 *
 * @return array{rakes: int, penalties: int, weighments: int, rr_documents: int, wagons: int, skipped_sheets: int, errors: list<string>}
 */
final readonly class ImportRakeDataFromExcelAction
{
    public function handle(?int $userId = null): array
    {
        ini_set('memory_limit', (string) config('rrmcs.prd_import.memory_limit', '512M'));

        $userId ??= \App\Models\User::query()->first()?->id;
        $basePath = config('rrmcs.prd_import.base_path');
        $stats = ['rakes' => 0, 'penalties' => 0, 'weighments' => 0, 'rr_documents' => 0, 'wagons' => 0, 'skipped_sheets' => 0, 'errors' => []];

        if (! $basePath || ! is_dir(base_path($basePath))) {
            $stats['errors'][] = 'PRD base path not found: '.($basePath ?? 'null');

            return $stats;
        }

        $basePath = base_path($basePath);

        DB::transaction(function () use ($basePath, $userId, &$stats): void {
            $sidingPakur = Siding::query()->where('code', 'PKUR')->first();
            $sidingDumka = Siding::query()->where('code', 'DUMK')->first();
            $sidingKurwa = Siding::query()->where('code', 'KURWA')->first();

            $pakurFile = config('rrmcs.prd_import.pakur_monthly');
            if ($pakurFile && $sidingPakur && is_file($basePath.'/'.$pakurFile)) {
                $result = $this->importPakurMonthly($basePath.'/'.$pakurFile, $sidingPakur->id, $userId);
                $stats['rakes'] += $result['rakes'];
                $stats['penalties'] += $result['penalties'];
                $stats['rr_documents'] += $result['rr_documents'];
                $stats['skipped_sheets'] += $result['skipped_sheets'];
                $stats['errors'] = array_merge($stats['errors'], $result['errors']);
            }

            $dumkaFile = config('rrmcs.prd_import.dumka_loading');
            if ($dumkaFile && $sidingDumka && is_file($basePath.'/'.$dumkaFile)) {
                $result = $this->importLoadingData($basePath.'/'.$dumkaFile, $sidingDumka->id, $userId, 'DUMK');
                $stats['rakes'] += $result['rakes'];
                $stats['weighments'] += $result['weighments'];
                $stats['errors'] = array_merge($stats['errors'], $result['errors']);
            }

            $kurwaFile = config('rrmcs.prd_import.kurwa_loading');
            if ($kurwaFile && $sidingKurwa && is_file($basePath.'/'.$kurwaFile)) {
                $result = $this->importLoadingData($basePath.'/'.$kurwaFile, $sidingKurwa->id, $userId, 'KURWA');
                $stats['rakes'] += $result['rakes'];
                $stats['weighments'] += $result['weighments'];
                $stats['errors'] = array_merge($stats['errors'], $result['errors']);
            }

            $imwbFile = config('rrmcs.prd_import.imwb_sensor');
            $imwbSidingCode = config('rrmcs.imwb_default_siding_code', 'DUMK');
            $sidingImwb = Siding::query()->where('code', $imwbSidingCode)->first();
            if ($imwbFile && $sidingImwb && is_file($basePath.'/'.$imwbFile)) {
                $result = $this->importImwbSensorReport($basePath.'/'.$imwbFile, $sidingImwb->id, $userId, $imwbSidingCode);
                $stats['rakes'] += $result['rakes'];
                $stats['wagons'] += $result['wagons'];
                $stats['errors'] = array_merge($stats['errors'], $result['errors']);
            }
        });

        return $stats;
    }

    /**
     * Pakur monthly format: row 1 title, row 2 header, row 3+ data.
     * Columns: A=SL, B=LOADING DATE, C=RAKE NO, E=R.R NUMBER, F=NO OF WAGON, G=NET WEIGHT, K=O/L CHARGE, L=DETENTION HOURS, M=DETENTION CHARGES.
     *
     * @return array{rakes: int, penalties: int, rr_documents: int, skipped_sheets: int, errors: list<string>}
     */
    private function importPakurMonthly(string $path, int $sidingId, ?int $userId): array
    {
        $stats = ['rakes' => 0, 'penalties' => 0, 'rr_documents' => 0, 'skipped_sheets' => 0, 'errors' => []];

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (Throwable $e) {
            $stats['errors'][] = 'Pakur file: '.$e->getMessage();

            return $stats;
        }

        $rate = (float) config('rrmcs.demurrage_rate_per_mt_hour', 50);

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $name = $sheet->getTitle();
            if (in_array(mb_strtoupper($name), ['KILOMETER', 'RAIL DISPATCH', 'ABSTRACT', 'AVG U.LOAD', 'FOREST PERMIT DATA', 'IMWB', 'D.C', 'DISTANCE'], true)) {
                $stats['skipped_sheets']++;

                continue;
            }

            $highestRow = $sheet->getHighestDataRow();
            if ($highestRow < 3) {
                $stats['skipped_sheets']++;

                continue;
            }

            for ($row = 3; $row <= $highestRow; $row++) {
                $rakeNo = $this->cellValue($sheet, 'C', $row);
                if ($rakeNo === null) {
                    continue;
                }
                if ($rakeNo === '') {
                    continue;
                }
                if (mb_stripos((string) $rakeNo, 'TOTAL') !== false) {
                    continue;
                }
                $rakeNoStr = mb_trim((string) $rakeNo);
                if (mb_strlen($rakeNoStr) > 15) {
                    continue;
                }
                if (! is_numeric($rakeNoStr) && mb_strlen($rakeNoStr) > 10) {
                    continue;
                }

                $loadingDate = $this->cellDate($sheet, 'B', $row);
                if (! $loadingDate instanceof Carbon) {
                    continue;
                }
                $rrNumber = $this->cellValue($sheet, 'E', $row);
                $wagonCount = (int) $this->cellNumeric($sheet, 'F', $row) ?: 59;
                $netWeight = $this->cellNumeric($sheet, 'G', $row);
                $detentionHours = $this->cellNumeric($sheet, 'L', $row);
                $detentionCharges = $this->cellNumeric($sheet, 'M', $row);
                $overloadCharge = $this->cellNumeric($sheet, 'K', $row);

                $rakeNumber = 'PKUR-'.$rakeNoStr;
                $rake = Rake::query()->firstOrCreate([
                    'siding_id' => $sidingId,
                    'rake_number' => $rakeNumber,
                ], [
                    'rake_type' => 'BOBRN',
                    'wagon_count' => $wagonCount,
                    'loading_end_time' => $loadingDate,
                    'loaded_weight_mt' => $netWeight > 0 ? round($netWeight, 2) : null,
                    'state' => 'delivered',
                    'loading_free_minutes' => (int) config('rrmcs.default_free_time_minutes', 180),
                    'rr_expected_date' => $loadingDate,
                    'rr_actual_date' => $loadingDate,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
                if ($rake->wasRecentlyCreated) {
                    $stats['rakes']++;
                }

                if ($rrNumber && mb_trim((string) $rrNumber) !== '' && mb_stripos((string) $rrNumber, 'TOTAL') === false) {
                    $rrNum = mb_trim((string) $rrNumber);
                    $doc = RrDocument::query()->firstOrCreate(['rr_number' => $rrNum], [
                        'rake_id' => $rake->id,
                        'rr_received_date' => $loadingDate,
                        'rr_weight_mt' => $netWeight > 0 ? round($netWeight, 2) : null,
                        'document_status' => 'verified',
                        'has_discrepancy' => false,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                    if ($doc->wasRecentlyCreated) {
                        $stats['rr_documents']++;
                    }
                }

                if ($detentionCharges > 0) {
                    $penaltyDate = $loadingDate instanceof Carbon ? $loadingDate->toDateString() : ($loadingDate ? (string) $loadingDate : now()->toDateString());
                    $pen = Penalty::query()->firstOrCreate([
                        'rake_id' => $rake->id,
                        'penalty_type' => 'DEM',
                        'penalty_date' => $penaltyDate,
                    ], [
                        'penalty_amount' => round($detentionCharges, 2),
                        'penalty_status' => 'incurred',
                        'description' => sprintf('Demurrage: %s h × %s MT × ₹%s/MT/h (imported)', $detentionHours, $netWeight, $rate),
                        'calculation_breakdown' => [
                            'formula' => 'demurrage_hours × weight_mt × rate_per_mt_hour',
                            'demurrage_hours' => round((float) $detentionHours, 2),
                            'weight_mt' => round((float) $netWeight, 2),
                            'rate_per_mt_hour' => $rate,
                            'free_hours' => 3,
                            'dwell_hours' => round(3 + (float) $detentionHours, 2),
                        ],
                    ]);
                    if ($pen->wasRecentlyCreated) {
                        $stats['penalties']++;
                    }
                }

                if ($overloadCharge > 0) {
                    $penaltyDate = $loadingDate instanceof Carbon ? $loadingDate->toDateString() : ($loadingDate ? (string) $loadingDate : now()->toDateString());
                    $pen = Penalty::query()->firstOrCreate([
                        'rake_id' => $rake->id,
                        'penalty_type' => 'POL1',
                        'penalty_date' => $penaltyDate,
                    ], [
                        'penalty_amount' => round($overloadCharge, 2),
                        'penalty_status' => 'incurred',
                        'description' => 'Overload charge (imported)',
                    ]);
                    if ($pen->wasRecentlyCreated) {
                        $stats['penalties']++;
                    }
                }
            }
        }

        return $stats;
    }

    /**
     * IMWB load sensor report: row 1 header; data from row 2.
     * Columns: A=SL NO, B=DATE, C=RAKE NUMBER, D=WAGON NUMBER, E=IMWB WEIGHT, F=LOADER SENSOR WEIGHT, G=DIFF WT, H=LOADER NUMBER, I=OP NAME.
     * Creates rakes by siding + rake number (sidingCode-{C}), wagons by wagon_number with weighment/loader weights and loader_id.
     *
     * @return array{rakes: int, wagons: int, errors: list<string>}
     */
    private function importImwbSensorReport(string $path, int $sidingId, ?int $userId, string $sidingCode): array
    {
        $stats = ['rakes' => 0, 'wagons' => 0, 'errors' => []];

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (Throwable $e) {
            $stats['errors'][] = 'IMWB sensor file: '.$e->getMessage();

            return $stats;
        }

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $highestRow = $sheet->getHighestDataRow();
            if ($highestRow < 2) {
                continue;
            }

            for ($row = 2; $row <= $highestRow; $row++) {
                $rakeNoRaw = $this->cellValue($sheet, 'C', $row);
                $wagonNumber = mb_trim((string) ($this->cellValue($sheet, 'D', $row) ?? ''));
                if ($rakeNoRaw === null) {
                    continue;
                }
                if ($rakeNoRaw === '') {
                    continue;
                }
                if ($wagonNumber === '') {
                    continue;
                }

                $rakeNumber = $sidingCode.'-'.mb_trim((string) $rakeNoRaw);
                $rake = Rake::query()->firstOrCreate([
                    'siding_id' => $sidingId,
                    'rake_number' => $rakeNumber,
                ], [
                    'rake_type' => 'BOBRN',
                    'wagon_count' => 0,
                    'state' => 'delivered',
                    'loading_free_minutes' => (int) config('rrmcs.default_free_time_minutes', 180),
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
                if ($rake->wasRecentlyCreated) {
                    $stats['rakes']++;
                }

                $imwbWeight = $this->cellNumeric($sheet, 'E', $row);
                $loaderSensorWeight = $this->cellNumeric($sheet, 'F', $row);
                $loaderNumberRaw = $this->cellValue($sheet, 'H', $row);
                $loaderCode = $loaderNumberRaw !== null && $loaderNumberRaw !== '' ? (string) $loaderNumberRaw : null;
                $loaderId = null;
                if ($loaderCode !== null) {
                    $loader = Loader::query()->where('siding_id', $sidingId)->where('code', $loaderCode)->first();
                    $loaderId = $loader?->id;
                }

                $wagon = Wagon::query()->where('wagon_number', $wagonNumber)->first();
                $wasNew = $wagon === null;
                $wagon = Wagon::query()->updateOrCreate(['wagon_number' => $wagonNumber], [
                    'rake_id' => $rake->id,
                    'wagon_sequence' => $wagon?->wagon_sequence ?? 0,
                    'loaded_weight_mt' => $imwbWeight !== null ? round($imwbWeight, 2) : null,
                ]);
                if ($wasNew) {
                    $stats['wagons']++;
                }
            }
        }

        return $stats;
    }

    /**
     * Loading data format (Dumka/Kurwa): row 1 title, row 2 header, row 3+ data.
     * Columns: A=SL, B=RAKE NO, C=WAGON TYPE, D=PLACEMENT TIME, E=LOADING COMPLETE TIME, (F=LOADING TIME), G/H=NET WT (varies).
     *
     * @return array{rakes: int, weighments: int, errors: list<string>}
     */
    private function importLoadingData(string $path, int $sidingId, ?int $userId, string $sidingCode): array
    {
        $stats = ['rakes' => 0, 'weighments' => 0, 'errors' => []];

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (Throwable $e) {
            $stats['errors'][] = $sidingCode.' file: '.$e->getMessage();

            return $stats;
        }

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $highestRow = $sheet->getHighestDataRow();
            if ($highestRow < 3) {
                continue;
            }

            for ($row = 3; $row <= $highestRow; $row++) {
                $rakeNo = $this->cellValue($sheet, 'B', $row);
                if ($rakeNo === null) {
                    continue;
                }
                if ($rakeNo === '') {
                    continue;
                }

                $wagonType = $this->cellValue($sheet, 'C', $row) ?: 'BOBRN';
                $placementTime = $this->cellDate($sheet, 'D', $row);
                $loadingCompleteTime = $this->cellDate($sheet, 'E', $row);
                $netWt = $this->cellNumeric($sheet, 'H', $row) ?: $this->cellNumeric($sheet, 'G', $row);

                $rakeNumber = $sidingCode.'-'.mb_trim((string) $rakeNo);
                $rake = Rake::query()->firstOrCreate([
                    'siding_id' => $sidingId,
                    'rake_number' => $rakeNumber,
                ], [
                    'rake_type' => str_starts_with(mb_strtoupper((string) $wagonType), 'BOX') ? 'BOXN' : 'BOBRN',
                    'wagon_count' => 59,
                    'loading_start_time' => $placementTime,
                    'loading_end_time' => $loadingCompleteTime,
                    'loaded_weight_mt' => $netWt > 0 ? round($netWt, 2) : null,
                    'state' => 'delivered',
                    'loading_free_minutes' => (int) config('rrmcs.default_free_time_minutes', 180),
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
                if ($rake->wasRecentlyCreated) {
                    $stats['rakes']++;
                }

                $weighTime = $loadingCompleteTime ?? $placementTime;
                if ($weighTime && $netWt > 0) {
                    $weigh = Weighment::query()->firstOrCreate(['rake_id' => $rake->id], [
                        'weighment_time' => $weighTime,
                        'total_weight_mt' => round($netWt, 2),
                        'average_wagon_weight_mt' => $rake->wagon_count > 0 ? round($netWt / $rake->wagon_count, 2) : null,
                        'weighment_status' => 'verified',
                    ]);
                    if ($weigh->wasRecentlyCreated) {
                        $stats['weighments']++;
                    }
                }
            }
        }

        return $stats;
    }

    private function cellValue($sheet, string $col, int $row): mixed
    {
        return $sheet->getCell($col.$row)->getValue();
    }

    private function cellNumeric($sheet, string $col, int $row): ?float
    {
        $v = $sheet->getCell($col.$row)->getValue();
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            return (float) $v;
        }

        return null;
    }

    private function cellDate($sheet, string $col, int $row): ?Carbon
    {
        $v = $sheet->getCell($col.$row)->getValue();
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            try {
                $date = ExcelDate::excelToDateTimeObject($v);

                return \Illuminate\Support\Facades\Date::instance($date);
            } catch (Throwable) {
                return null;
            }
        }
        if (is_string($v)) {
            try {
                return \Illuminate\Support\Facades\Date::parse($v);
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }
}
