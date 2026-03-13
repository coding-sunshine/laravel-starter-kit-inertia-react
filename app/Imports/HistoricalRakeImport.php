<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RrPenaltySnapshot;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Throwable;

final class HistoricalRakeImport implements ToCollection
{
    private int $sidingId;

    private array $penaltyTypes = [];

    // Tab flow control
    private bool $done = false;

    // After a full tab is imported we record its latest month/year.
    // Pattern per period: Tab A = full data (has RR) → imported
    //                     Tab B = summary/duplicate (no RR) → skipped
    // When Tab B is skipped AND last imported period = Jan 2025 → stop everything.
    private ?int $lastImportedYear = null;

    private ?int $lastImportedMonth = null;

    // The target endpoint
    private int $stopYear = 2025;

    private int $stopMonth = 1;

    public function __construct(int $sidingId)
    {
        $this->sidingId = $sidingId;

        // Cache penalty types
        $this->penaltyTypes = PenaltyType::pluck('id', 'code')->toArray();
    }

    public function collection(Collection $rows)
    {
        // Already finished — skip all remaining tabs silently
        if ($this->done) {
            echo "\n⏭ Import complete (Jan 2025 + summary done). Skipping tab.\n\n";

            return;
        }

        DB::beginTransaction();

        try {

            $headerIndex = $this->findHeaderRow($rows);
            $headerRow = $rows[$headerIndex];
            $map = $this->buildColumnMap($headerRow);

            echo "\nDetected Columns:\n";
            print_r($map);

            // ---------- Guard: skip tabs missing required columns ----------
            $requiredColumns = ['RAKE NO', 'R.R NUMBER', 'LOADING DATE'];
            foreach ($requiredColumns as $col) {
                if (! isset($map[$col])) {
                    echo "\n⚠ Required column '{$col}' not found. Skipping tab.\n\n";
                    DB::rollBack();

                    return;
                }
            }

            // ---------- Check if this sheet is a no-RR summary tab ----------
            // These are duplicate tabs of the previous period with fewer columns.
            // When we skip one and the last imported period was Jan 2025, we're done.
            foreach ($rows->slice($headerIndex + 1) as $row) {

                $rakeNumber = $this->cleanInt($row[$map['RAKE NO']] ?? null);
                $rrNumber = $this->cleanInt($row[$map['R.R NUMBER']] ?? null);

                if ($rakeNumber && ! $rrNumber) {
                    echo "\n⚠ Sheet is summary data (RR missing). Skipping tab.\n\n";
                    DB::rollBack();

                    // If the previous full tab was Jan 2025, this summary is its pair → done
                    if (
                        $this->lastImportedYear === $this->stopYear &&
                        $this->lastImportedMonth === $this->stopMonth
                    ) {
                        echo "✅ Jan 2025 summary tab skipped. Import complete. No further tabs will run.\n\n";
                        $this->done = true;
                    }

                    return;
                }
            }

            // ---------- Check if any RR exists at all ----------
            $hasRR = false;
            foreach ($rows->slice($headerIndex + 1) as $row) {
                if ($this->cleanInt($row[$map['R.R NUMBER']] ?? null)) {
                    $hasRR = true;
                    break;
                }
            }

            if (! $hasRR) {
                echo "\n⚠ No RR numbers found. Skipping tab.\n\n";
                DB::rollBack();

                return;
            }

            // ---------- Import rows ----------
            $count = 0;
            $latestDate = null;

            foreach ($rows->slice($headerIndex + 1) as $row) {

                if (! $this->isValidRow($row)) {
                    continue;
                }

                $rakeNumber = $this->cleanInt($row[$map['RAKE NO']] ?? null);
                $rrNumber = $this->cleanInt($row[$map['R.R NUMBER']] ?? null);

                if (! $rakeNumber || ! $rrNumber) {
                    continue;
                }

                if (Rake::where('rr_number', $rrNumber)->exists()) {
                    echo "Skipping duplicate RR: {$rrNumber}\n";

                    continue;
                }

                $loadingDate = $this->parseDate($row[$map['LOADING DATE']] ?? null);

                // Track the latest date seen in this tab
                if ($loadingDate && (! $latestDate || $loadingDate->gt($latestDate))) {
                    $latestDate = $loadingDate;
                }
                $timestamp = $loadingDate instanceof Carbon ? $loadingDate : now();
                $rake = Rake::create([
                    'siding_id' => $this->sidingId,
                    'rake_number' => $rakeNumber,
                    'priority_number' => $this->cleanInt($row[$map['PRIORTY NUMBER']] ?? null),
                    'rr_number' => $rrNumber,
                    'wagon_count' => $this->cleanInt($row[$map['NO OF WAGON']] ?? null),
                    'loaded_weight_mt' => $this->cleanNumber($row[$map['NET WEIGHT']] ?? null),
                    'under_load_mt' => $this->cleanNumber($row[$map['UNDER LOAD WT']] ?? null),
                    'over_load_mt' => $this->cleanNumber($row[$map['OVER LOAD WT']] ?? null),
                    'overload_wagon_count' => $this->cleanInt($row[$map['O/L WAGON']] ?? null),
                    'detention_hours' => $this->cleanNumber($row[$map['DETENTION HOURS']] ?? null),
                    'shunting_hours' => $this->cleanNumber($row[$map['SHUNTING HOURS']] ?? null),
                    'total_amount_rs' => isset($map['TOTAL AMOUNT RS'])
                        ? $this->cleanNumber($row[$map['TOTAL AMOUNT RS']] ?? null)
                        : null,
                    'destination' => isset($map['DESTINATION'])
                        ? $this->cleanText($row[$map['DESTINATION']] ?? null)
                        : null,
                    'pakur_imwb_period' => isset($map['PAKUR IMWB PERIOD'])
                        ? $this->cleanText($row[$map['PAKUR IMWB PERIOD']] ?? null)
                        : null,
                    'loading_date' => $loadingDate,
                    'data_source' => 'historical_excel',
                    'state' => 'completed',
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

                $this->createPenalties($rake, $row, $map, $loadingDate);

                $count++;

                echo "Imported Rake: {$rake->rake_number} | RR: {$rrNumber} | Date: {$loadingDate}\n";

                Log::info('Historical rake imported', [
                    'rake_number' => $rake->rake_number,
                    'rr_number' => $rrNumber,
                    'date' => $loadingDate,
                ]);
            }

            DB::commit();

            // Record the latest month/year from this tab so the next summary-skip check works
            if ($latestDate) {
                $this->lastImportedYear = (int) $latestDate->format('Y');
                $this->lastImportedMonth = (int) $latestDate->format('n');
            }

            echo "\n============================\n";
            echo "IMPORT SUCCESS\n";
            echo "Total Rakes Imported: {$count}\n";
            echo 'Last date in tab: '.($latestDate?->toDateString() ?? 'unknown')."\n";
            echo "============================\n\n";

        } catch (Throwable $e) {

            DB::rollBack();

            echo "\n============================\n";
            echo "IMPORT FAILED ❌\n";
            echo "Error: {$e->getMessage()}\n";
            echo "============================\n\n";

            Log::error('Historical rake import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Do NOT re-throw — one bad tab should not kill the full import.
        }
    }

    private function buildColumnMap($headerRow): array
    {
        $map = [];

        foreach ($headerRow as $index => $value) {

            if (! $value) {
                continue;
            }

            $key = mb_strtoupper(mb_trim($value));

            if (str_contains($key, 'RAKE NO')) {
                $map['RAKE NO'] = $index;
            }
            if (str_contains($key, 'PRIORTY')) {
                $map['PRIORTY NUMBER'] = $index;
            }
            if (str_contains($key, 'R.R')) {
                $map['R.R NUMBER'] = $index;
            }
            if (str_contains($key, 'LOADING DATE')) {
                $map['LOADING DATE'] = $index;
            }
            if (str_contains($key, 'NO OF WAGON')) {
                $map['NO OF WAGON'] = $index;
            }
            if (str_contains($key, 'NET WEIGHT')) {
                $map['NET WEIGHT'] = $index;
            }
            if (str_contains($key, 'UNDER LOAD')) {
                $map['UNDER LOAD WT'] = $index;
            }
            if (str_contains($key, 'OVER LOAD WT')) {
                $map['OVER LOAD WT'] = $index;
            }
            if (str_contains($key, 'O/L WAGON')) {
                $map['O/L WAGON'] = $index;
            }
            if (str_contains($key, 'DETENTION HOURS')) {
                $map['DETENTION HOURS'] = $index;
            }
            if (str_contains($key, 'SHUNTING HOURS')) {
                $map['SHUNTING HOURS'] = $index;
            }
            if (str_contains($key, 'TOTAL')) {
                $map['TOTAL AMOUNT RS'] = $index;
            }
            if (str_contains($key, 'DESTINATION')) {
                $map['DESTINATION'] = $index;
            }
            if (str_contains($key, 'PAKUR')) {
                $map['PAKUR IMWB PERIOD'] = $index;
            }
            if (str_contains($key, 'O/L CHARGE')) {
                $map['O/L CHARGE'] = $index;
            }
            if (str_contains($key, 'DETENTION CHARGES')) {
                $map['DETENTION CHARGES'] = $index;
            }
            if (str_contains($key, 'SHUNTING CHARGES')) {
                $map['SHUNTING CHARGES'] = $index;
            }
            if (str_contains($key, 'PILOT CHARGES')) {
                $map['PILOT CHARGES'] = $index;
            }
        }

        return $map;
    }

    private function createPenalties(Rake $rake, $row, $map, $loadingDate = null)
    {
        if (isset($map['O/L CHARGE'])) {
            $this->createPenalty($rake, 'PLO', $row[$map['O/L CHARGE']] ?? null, $loadingDate);
        }

        if (isset($map['DETENTION CHARGES'])) {
            $this->createPenalty($rake, 'DEM', $row[$map['DETENTION CHARGES']] ?? null, $loadingDate);
        }

        if (isset($map['SHUNTING CHARGES'])) {
            $this->createPenalty($rake, 'SHUNT', $row[$map['SHUNTING CHARGES']] ?? null, $loadingDate);
        }

        if (isset($map['PILOT CHARGES'])) {
            $this->createPenalty($rake, 'PILOT', $row[$map['PILOT CHARGES']] ?? null, $loadingDate);
        }
    }

    private function createPenalty(Rake $rake, string $code, $amount, $loadingDate = null)
    {
        $amount = $this->cleanNumber($amount);

        if (! $amount || $amount <= 0) {
            echo "Penalty {$code} amount is invalid: {$amount}\n";

            return;
        }
        if (! isset($this->penaltyTypes[$code])) {
            echo "Penalty type {$code} not found\n";

            return;
        }

        RrPenaltySnapshot::query()->create([
            'rr_document_id' => null,
            'rake_id' => $rake->id,
            'penalty_code' => $code,
            'amount' => $amount,
            'wagon_number' => null,
            'wagon_sequence' => null,
            'meta' => [
                'source' => 'historical_excel',
            ],
        ]);

        echo "Penalty {$code} added: {$amount}\n";
    }

    private function findHeaderRow(Collection $rows): int
    {
        foreach ($rows as $index => $row) {
            foreach ($row as $cell) {
                if (str_contains(mb_strtoupper((string) $cell), 'LOADING DATE')) {
                    return $index;
                }
            }
        }

        return 0;
    }

    private function isValidRow($row): bool
    {
        if (empty($row)) {
            return false;
        }

        if (isset($row[0]) && str_contains(mb_strtoupper((string) $row[0]), 'TOTAL')) {
            return false;
        }

        if (isset($row[0]) && str_contains(mb_strtoupper((string) $row[0]), 'NOTE')) {
            return false;
        }

        return true;
    }

    private function cleanNumber($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = mb_trim($value);
            if (str_starts_with($value, '=')) {
                return null;
            }
            $value = str_replace(',', '', $value);
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function cleanInt($value)
    {
        $num = $this->cleanNumber($value);

        return $num === null ? null : (int) $num;
    }

    private function cleanText($value)
    {
        if (! $value) {
            return null;
        }
        if (is_string($value) && str_starts_with($value, '=')) {
            return null;
        }

        return mb_trim((string) $value);
    }

    private function parseDate($value)
    {
        if (! $value) {
            return null;
        }

        // Excel serial date number
        if (is_numeric($value)) {
            try {
                return Carbon::createFromTimestamp(($value - 25569) * 86400);
            } catch (Exception $e) {
                // fall through
            }
        }

        // Try multiple string formats
        $formats = ['d.m.y', 'd.m.Y', 'd/m/Y', 'd/m/y', 'Y-m-d', 'd-m-Y'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, mb_trim((string) $value));
            } catch (Exception $e) {
                // try next
            }
        }

        return null;
    }
}
