<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PowerPlant;
use App\Models\Rake;
use App\Models\RakeWagonWeighment;
use App\Models\Siding;
use App\Models\Wagon;
use App\Models\Weighment;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Spatie\PdfToText\Pdf;
use Throwable;

final readonly class WeighmentPdfImporter
{
    /**
     * Import a historical rake weighment from an uploaded PDF.
     */
    public function import(UploadedFile $pdf, int $userId): Weighment
    {
        Log::info('Weighment PDF import: starting', [
            'user_id' => $userId,
            'original_name' => $pdf->getClientOriginalName(),
            'size' => $pdf->getSize(),
        ]);

        $storedPath = $pdf->store('weighment-pdfs', 'public');
        $absolutePath = storage_path('app/public/'.$storedPath);

        $text = Pdf::getText($absolutePath, null, ['-layout']);

        // dd($text);
        return $this->importFromText($text, $storedPath, $userId);
    }

    /**
     * Parse a weighment PDF for use with an existing rake (no Rake/Wagon creation).
     * Returns parsed header, totals, wagon rows and the stored file path.
     *
     * @return array{stored_path: string, header: array<string, mixed>, totals: array<string, float|null>, wagon_rows: array<int, array<string, mixed>>}
     */
    public function parsePdfForRake(UploadedFile $pdf): array
    {
        $storedPath = $pdf->store('weighment-pdfs', 'public');
        $absolutePath = storage_path('app/public/'.$storedPath);
        $text = Pdf::getText($absolutePath, null, ['-layout']);
        $lines = preg_split("/\r\n|\r|\n/", $text) ?: [];
        $header = $this->parseHeader($lines);
        $totals = $this->parseTotalsFooter($text);
        $wagonRows = $this->parseWagonRows($lines);
        if ($wagonRows === []) {
            throw new InvalidArgumentException('No wagon rows detected in weighment PDF.');
        }

        return [
            'stored_path' => $storedPath,
            'header' => $header,
            'totals' => $totals,
            'wagon_rows' => $wagonRows,
        ];
    }

    /**
     * Import a historical rake weighment from already-extracted PDF text.
     *
     * Exposed primarily for testing so we can bypass the external PDF binary.
     */
    public function importFromText(string $text, string $storedPdfPath, int $userId): Weighment
    {
        $lines = preg_split("/\r\n|\r|\n/", $text) ?: [];

        $header = $this->parseHeader($lines);
        $siding = $this->detectSiding($text);
        $this->ensureSidingFound($siding);
        $powerPlant = $this->detectPowerPlant($text);
        $totals = $this->parseTotalsFooter($text);

        Log::info('Weighment PDF import: parsed header and detection', [
            'user_id' => $userId,
            'rake_number' => $header['rake_number'] ?? null,
            'from_station' => $header['from_station'] ?? null,
            'to_station' => $header['to_station'] ?? null,
            'siding_id' => $siding?->id,
            'siding_station_code' => $siding?->station_code,
            'power_plant_id' => $powerPlant?->id,
            'power_plant_code' => $powerPlant?->code,
        ]);

        $wagonRows = $this->parseWagonRows($lines);
        if ($wagonRows === []) {
            throw new InvalidArgumentException('No wagon rows detected in weighment PDF.');
        }

        if (empty($header['rake_number'])) {
            throw new InvalidArgumentException('Rake number could not be detected from weighment PDF.');
        }

        $this->ensureRakeNumberIsUnique($header['rake_number']);

        try {
            return DB::transaction(function () use ($header, $siding, $wagonRows, $storedPdfPath, $userId, $totals) {
                Log::info('Weighment PDF import: transaction started', [
                    'user_id' => $userId,
                    'rake_number' => $header['rake_number'],
                    'wagon_count' => count($wagonRows),
                ]);

                $rake = $this->createRake($header, $siding, $wagonRows, $userId);
                $wagons = $this->insertWagons($rake, $wagonRows);
                $weighment = $this->createWeighment($rake, $header, $storedPdfPath, $userId, $totals);
                $this->insertRakeWagonWeighments($weighment, $wagons, $wagonRows);

                Log::info('Weighment PDF import: transaction committing', [
                    'user_id' => $userId,
                    'rake_id' => $rake->id,
                    'weighment_id' => $weighment->id,
                    'wagon_inserted_count' => count($wagons),
                ]);

                return $weighment->fresh(['rake', 'rakeWagonWeighments']);
            });
        } catch (Throwable $e) {
            Log::error('Weighment PDF import: transaction rolled back due to error', [
                'user_id' => $userId,
                'rake_number' => $header['rake_number'] ?? null,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Extract header values from PDF text. Handles two-column layout where a single line
     * may contain e.g. "Gross Weighment Date & Time : 08-12-2025 10:07:30    Commodity : COAL".
     * Values are captured up to the next label (2+ spaces + letter) or end of line.
     *
     * @param  array<int, string>  $lines
     * @return array{
     *     rake_number?: string,
     *     rake_type?: string|null,
     *     train_name?: string|null,
     *     direction?: string|null,
     *     commodity?: string|null,
     *     from_station?: string|null,
     *     to_station?: string|null,
     *     priority_number?: string|null,
     *     gross_weighment_datetime?: CarbonInterface|null,
     *     tare_weighment_datetime?: CarbonInterface|null,
     * }
     */
    private function parseHeader(array $lines): array
    {
        $header = [];
        $fullText = implode("\n", $lines);

        foreach ($lines as $line) {
            $trimmed = mb_trim($line);
            if ($trimmed === '') {
                continue;
            }

            // Rake Number: allow spaces in value (e.g. "PKRZ 230825045801"); stop at next column (2+ spaces + letter)
            if (! isset($header['rake_number']) && preg_match('/Rake\s*Number\s*[:\-]\s*(.+?)(?=\s{2,}[A-Za-z]|$)/s', $trimmed, $m)) {
                $header['rake_number'] = mb_substr(mb_trim($m[1]), 0, 20);

                continue;
            }
            if (! isset($header['rake_number']) && preg_match('/Rake\s*(No\.?|ID)\s*[:\-]\s*(\S+)/i', $trimmed, $m)) {
                $header['rake_number'] = mb_substr(mb_trim($m[2]), 0, 20);

                continue;
            }
            if (! isset($header['rake_number']) && preg_match('/FOIS\s*Rake\s*ID\s*[:\-]\s*(\S+)/i', $trimmed, $m)) {
                $header['rake_number'] = mb_substr($m[1], 0, 20);

                continue;
            }
            if (! isset($header['rake_number']) && preg_match('/Gross\s*Rake\s*No\s*[:\-]\s*(\S+)/i', $trimmed, $m)) {
                $header['rake_number'] = mb_substr($m[1], 0, 20);

                continue;
            }

            if (! isset($header['rake_type']) && preg_match('/Rake\s*Type\s*[:\-]\s*(.+?)(?=\s{2,}[A-Za-z]|$)/s', $trimmed, $m)) {
                $header['rake_type'] = mb_trim($m[1]);

                continue;
            }

            // Train Name/Loco Name (WBPD style)
            if (! isset($header['train_name']) && preg_match('/Train\s*Name\/?Loco\s*Name\s*[:\-]\s*(.+?)(?=\s{2,}[A-Za-z]|$)/s', $trimmed, $m)) {
                $header['train_name'] = mb_trim($m[1]);

                continue;
            }
            if (! isset($header['train_name']) && preg_match('/(Train|Loco)\s*Name\s*[:\-]\s*(.+?)(?=\s{2,}[A-Za-z]|$)/s', $trimmed, $m)) {
                $header['train_name'] = mb_trim($m[2]);

                continue;
            }

            if (! isset($header['direction']) && preg_match('/Direction\s*[:\-]\s*(.+?)(?=\s{2,}[A-Za-z]|$)/s', $trimmed, $m)) {
                $header['direction'] = mb_trim($m[1]);

                continue;
            }

            if (! isset($header['commodity']) && preg_match('/(?:Commodity|Product)\s*[:\-]\s*(.+?)(?=\s{2,}[A-Za-z]|$)/s', $trimmed, $m)) {
                $header['commodity'] = mb_trim($m[1]);

                continue;
            }

            if (! isset($header['from_station']) && preg_match('/From\s*Station\s*[:\-]\s*(\S+)/i', $trimmed, $m)) {
                $header['from_station'] = mb_trim($m[1]);

                continue;
            }

            if (! isset($header['to_station']) && preg_match('/To\s*Station\s*[:\-]\s*(\S+)/i', $trimmed, $m)) {
                $header['to_station'] = mb_trim($m[1]);

                continue;
            }

            if (! isset($header['from_station']) && preg_match('/Source\s*[:\-]\s*(\S+)/i', $trimmed, $m)) {
                $header['from_station'] = mb_trim($m[1]);

                continue;
            }

            if (! isset($header['to_station']) && preg_match('/Destination\s*[:\-]\s*(\S+)/i', $trimmed, $m)) {
                $header['to_station'] = mb_trim($m[1]);

                continue;
            }

            // Priority No./Rake No. (e.g. "299.001/29")
            if (! isset($header['priority_number']) && preg_match('/Priority\s*No\.?(\/.*?)?\s*[:\-]\s*(\S+)/i', $trimmed, $m)) {
                $header['priority_number'] = mb_trim($m[2]);

                continue;
            }

            // Gross Weighment Date & Time - capture date-time only (stop at next column)
            if (! isset($header['gross_weighment_datetime']) && preg_match('/Gross\s*Weighment\s*Date\s*&?\s*Time\s*[:\-]\s*(.+?)(?=\s{2,}[A-Za-z]|$)/s', $trimmed, $m)) {
                $header['gross_weighment_datetime'] = $this->parseDate(mb_trim($m[1]));

                continue;
            }

            if (! isset($header['tare_weighment_datetime']) && preg_match('/Tare\s*Weighment\s*Date\s*&?\s*Time\s*[:\-]\s*(.+?)(?=\s{2,}[A-Za-z]|$)/s', $trimmed, $m)) {
                $header['tare_weighment_datetime'] = $this->parseDate(mb_trim($m[1]));

                continue;
            }

            if (! isset($header['gross_weighment_datetime']) && preg_match('/DateIn\s*[:\-]\s*(.+?)(?=\s{2,}[A-Za-z]|$)/s', $trimmed, $m)) {
                $header['gross_weighment_datetime'] = $this->parseDate(mb_trim($m[1]));

                continue;
            }
        }

        // Fallback: scan full text for labels that may span line breaks (e.g. value on next line)
        if (empty($header['rake_number']) && preg_match('/Rake\s*Number\s*[:\-]\s*([^\n]+?)(?=\s{2,}[A-Za-z]|\n|$)/s', $fullText, $m)) {
            $header['rake_number'] = mb_substr(mb_trim($m[1]), 0, 20);
        }
        if (empty($header['from_station']) && preg_match('/From\s*Station\s*[:\-]\s*(\S+)/i', $fullText, $m)) {
            $header['from_station'] = mb_trim($m[1]);
        }
        if (empty($header['to_station']) && preg_match('/To\s*Station\s*[:\-]\s*(\S+)/i', $fullText, $m)) {
            $header['to_station'] = mb_trim($m[1]);
        }
        if (empty($header['gross_weighment_datetime']) && preg_match('/Gross\s*Weighment\s*Date\s*&?\s*Time\s*[:\-]\s*(\d{2}-\d{2}-\d{4}\s+\d{1,2}:\d{2}(?::\d{2})?)/', $fullText, $m)) {
            $header['gross_weighment_datetime'] = $this->parseDate($m[1]);
        }
        if (empty($header['tare_weighment_datetime']) && preg_match('/Tare\s*Weighment\s*Date\s*&?\s*Time\s*[:\-]\s*(\d{2}-\d{2}-\d{4}\s+\d{1,2}:\d{2}(?::\d{2})?)/', $fullText, $m)) {
            $header['tare_weighment_datetime'] = $this->parseDate($m[1]);
        }
        if (empty($header['train_name']) && preg_match('/Train\s*Name\/?Loco\s*Name\s*[:\-]\s*(\S+)/i', $fullText, $m)) {
            $header['train_name'] = mb_trim($m[1]);
        }
        if (empty($header['direction']) && preg_match('/Direction\s*[:\-]\s*(\S+)/i', $fullText, $m)) {
            $header['direction'] = mb_trim($m[1]);
        }
        if (empty($header['commodity']) && preg_match('/Commodity\s*[:\-]\s*(\S+)/i', $fullText, $m)) {
            $header['commodity'] = mb_trim($m[1]);
        }
        if (empty($header['priority_number']) && preg_match('/Priority\s*No\.?[^:\-]*[:\-]\s*([^\s\n]+(?:\/[^\s\n]+)?)/i', $fullText, $m)) {
            $header['priority_number'] = mb_trim($m[1]);
        }

        return $header;
    }

    private function parseDate(string $value): ?CarbonInterface
    {
        $value = mb_trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return Date::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function detectSiding(string $text): ?Siding
    {
        $upper = mb_strtoupper($text);

        $keywords = [
            'DMK' => 'DMK',
            'DUMK' => 'DMK',
            'KRW' => 'KRW',
            'KURWA' => 'KRW',
            'PKR' => 'PKR',
            'PKUR' => 'PKUR',
        ];

        foreach ($keywords as $needle => $stationCode) {
            if (str_contains($upper, $needle)) {
                $siding = Siding::query()
                    ->where('station_code', $stationCode)
                    ->orWhere('code', $stationCode)
                    ->first();
                if ($siding !== null) {
                    return $siding;
                }
            }
        }

        return $this->detectSidingByName($upper);
    }

    /**
     * Fallback: detect siding by matching PDF text against siding names in the database
     * (e.g. "Pakur", "Dumka", "Kurwa" or "Pakur Siding", "Dumka Siding").
     */
    private function detectSidingByName(string $textUpper): ?Siding
    {
        $sidings = Siding::query()->where('is_active', true)->get();

        foreach ($sidings as $siding) {
            $nameUpper = mb_strtoupper(mb_trim($siding->name ?? ''));
            if ($nameUpper === '') {
                continue;
            }
            if (str_contains($textUpper, $nameUpper)) {
                return $siding;
            }
            $firstWord = explode(' ', $nameUpper)[0] ?? '';
            if ($firstWord !== '' && mb_strlen($firstWord) >= 3 && str_contains($textUpper, $firstWord)) {
                return $siding;
            }
        }

        return null;
    }

    private function ensureSidingFound(?Siding $siding): void
    {
        if (! $siding) {
            throw new InvalidArgumentException(
                'Unable to detect siding from weighment PDF. Ensure station codes (DMK, KRW, PKR) or siding names (e.g. Pakur, Dumka, Kurwa) are present.'
            );
        }
    }

    private function detectPowerPlant(string $text): ?PowerPlant
    {
        $upper = mb_strtoupper($text);
        $codes = ['BTMT', 'BTPC', 'KPPS', 'PSPM', 'STPS'];

        foreach ($codes as $code) {
            if (str_contains($upper, $code)) {
                return PowerPlant::query()
                    ->where('code', $code)
                    ->first();
            }
        }

        return null;
    }

    private function ensureRakeNumberIsUnique(string $rakeNumber): void
    {
        if (Rake::query()->where('rake_number', $rakeNumber)->exists()) {
            throw new InvalidArgumentException("Rake number '{$rakeNumber}' already exists.");
        }
    }

    /**
     * @param  array<int, string>  $lines
     * @return array<int, array<string, mixed>>
     */
    private function parseWagonRows(array $lines): array
    {
        $rows = [];
        $format = null;
        $startIndex = null;

        foreach ($lines as $index => $line) {
            $normalized = mb_trim(preg_replace('/\s+/', ' ', $line) ?? '');
            if ($normalized === '') {
                continue;
            }

            if ($format === null) {
                $headerLower = mb_strtolower($normalized);

                if ($this->isFormatBHeader($headerLower)) {
                    $format = 'B';
                    $startIndex = $index + 1;

                    continue;
                }

                if ($this->isFormatAHeader($headerLower)) {
                    $format = 'A';
                    $startIndex = $index + 1;

                    continue;
                }
            } else {
                if ($index < $startIndex) {
                    continue;
                }

                // Stop at totals line
                if (preg_match('/^Total/i', $normalized)) {
                    break;
                }

                $parts = $this->splitLineIntoParts($normalized);

                // Skip continuation header lines (non-numeric first token or too few parts)
                if (count($parts) < 5 || ! is_numeric($parts[0])) {
                    continue;
                }

                if ($format === 'A') {
                    $rows[] = $this->mapFormatA($parts);
                } elseif ($format === 'B') {
                    $rows[] = $this->mapFormatB($parts);
                }
            }
        }

        $rows = array_values(array_filter($rows, fn (array $row): bool => ($row['wagon_number'] ?? '') !== ''));

        if ($rows !== []) {
            return $rows;
        }

        return $this->parseWagonRowsFallback($lines);
    }

    /**
     * Fallback: look for any line starting with a sequence number followed by a wagon number.
     * Does not rely on header detection at all.
     *
     * @param  array<int, string>  $lines
     * @return array<int, array<string, mixed>>
     */
    private function parseWagonRowsFallback(array $lines): array
    {
        $rows = [];

        foreach ($lines as $line) {
            $normalized = mb_trim(preg_replace('/\s+/', ' ', $line) ?? '');
            if ($normalized === '') {
                continue;
            }

            if (preg_match('/^Total/i', $normalized)) {
                break;
            }

            $parts = $this->splitLineIntoParts($normalized);

            // Must start with a sequence number, followed by something that looks like a wagon number
            if (
                count($parts) >= 8
                && is_numeric($parts[0])
                && preg_match('/^[A-Z]{1,6}\d{4,}$/i', preg_replace('/\s+UNFIT$/i', '', $parts[1]))
            ) {
                $rows[] = $this->mapFormatA($parts);
            }
        }

        return array_values(array_filter($rows, fn (array $row): bool => ($row['wagon_number'] ?? '') !== ''));
    }

    /**
     * Format A detection.
     *
     * The PDF header may span multiple lines, e.g.:
     *   Line 1: "Wagon  Wagon  Wagon  Wagon  Wagon  Printed  Actual  Actual  Net  Under  Over Load  Speed"
     *   Line 2: "Seq. No  Number  Type  Axles  CC  Tare  Gross  Tare  Wt  Load  First"
     *
     * We detect either the combined single-line form OR the multi-wagon-keyword form.
     */
    private function isFormatAHeader(string $headerLower): bool
    {
        $hasSeqOrSl = str_contains($headerLower, 'seq')
            || str_contains($headerLower, 'sl no')
            || str_contains($headerLower, 'slno');

        $hasWagonNo = str_contains($headerLower, 'wagonno')
            || str_contains($headerLower, 'wagon no')
            || str_contains($headerLower, 'wagon');

        $hasColumnHint = str_contains($headerLower, 'axles')
            || str_contains($headerLower, 'gross')
            || str_contains($headerLower, 'wagontype')
            || str_contains($headerLower, 'wagon type')
            || str_contains($headerLower, 'tare')
            || str_contains($headerLower, 'cc')
            || str_contains($headerLower, 'printed')
            || str_contains($headerLower, 'actual')
            || str_contains($headerLower, 'speed')
            || str_contains($headerLower, 'under')
            || str_contains($headerLower, 'net');

        // Catch the split multi-line header: "Wagon Wagon Wagon Wagon Wagon Printed Actual Actual Net Under Over Load Speed"
        $isMultiWagonLine = mb_substr_count($headerLower, 'wagon') >= 3;

        return $isMultiWagonLine || ($hasSeqOrSl && $hasWagonNo && $hasColumnHint);
    }

    /**
     * Format B detection (Shikaripara / Eastern Railway style).
     * Header: "Slno Wagontype Wagon No CC Tare Gross Net OLoad ULoad Speed"
     */
    private function isFormatBHeader(string $headerLower): bool
    {
        return (str_contains($headerLower, 'slno') || str_contains($headerLower, 'sl no') || str_contains($headerLower, 'sino'))
            && (str_contains($headerLower, 'wagontype') || str_contains($headerLower, 'wagon type'))
            && (str_contains($headerLower, 'wagonno') || str_contains($headerLower, 'wagon no') || str_contains($headerLower, 'cc'));
    }

    /**
     * Split a line into non-empty parts (handles -layout output with multiple spaces).
     *
     * @return array<int, string>
     */
    private function splitLineIntoParts(string $normalizedLine): array
    {
        $parts = explode(' ', $normalizedLine);

        return array_values(array_filter($parts, fn (string $p): bool => $p !== ''));
    }

    /**
     * Format A:
     * Seq WagonNo [UNFIT] WagonType Axles CC Tare Gross PrintedTare Net UnderLoad OverLoad Speed
     *
     * Wagons marked UNFIT have their wagon_number as "WAGONNO UNFIT" in the PDF,
     * which after splitting inserts an extra "UNFIT" token at index 2, shifting all
     * subsequent columns right by 1. We detect and compensate for this.
     *
     * @param  array<int, string>  $parts
     * @return array<string, mixed>
     */
    private function mapFormatA(array $parts): array
    {
        // Detect UNFIT marker which shifts columns by 1
        $unfit = isset($parts[2]) && mb_strtoupper($parts[2]) === 'UNFIT';
        $offset = $unfit ? 1 : 0;

        return [
            'sequence' => (int) ($parts[0] ?? 0),
            'wagon_number' => $parts[1] ?? null,
            'wagon_type' => $parts[2 + $offset] ?? null,
            'axles' => isset($parts[3 + $offset]) ? (int) $parts[3 + $offset] : null,
            'cc_capacity_mt' => $this->toFloat(Arr::get($parts, 4 + $offset)),
            'tare_weight_mt' => $this->toFloat(Arr::get($parts, 5 + $offset)),
            'actual_gross_mt' => $this->toFloat(Arr::get($parts, 6 + $offset)),
            'printed_tare_mt' => $this->toFloat(Arr::get($parts, 7 + $offset)),
            'net_weight_mt' => $this->toFloat(Arr::get($parts, 8 + $offset)),
            'under_load_mt' => $this->toFloat(Arr::get($parts, 9 + $offset)),
            'over_load_mt' => $this->toFloat(Arr::get($parts, 10 + $offset)),
            'speed_kmph' => $this->toFloat(Arr::get($parts, 11 + $offset)),
        ];
    }

    /**
     * Format B:
     * SlNo Wagontype WagonNo CC Tare Gross Net OLoad ULoad Speed
     *
     * @param  array<int, string>  $parts
     * @return array<string, mixed>
     */
    private function mapFormatB(array $parts): array
    {
        return [
            'sequence' => (int) ($parts[0] ?? 0),
            'wagon_type' => $parts[1] ?? null,
            'wagon_number' => $parts[2] ?? null,
            'cc_capacity_mt' => $this->toFloat(Arr::get($parts, 3)),
            'tare_weight_mt' => $this->toFloat(Arr::get($parts, 4)),
            'actual_gross_mt' => $this->toFloat(Arr::get($parts, 5)),
            'net_weight_mt' => $this->toFloat(Arr::get($parts, 6)),
            'over_load_mt' => $this->toFloat(Arr::get($parts, 7)),
            'under_load_mt' => $this->toFloat(Arr::get($parts, 8)),
            'speed_kmph' => $this->toFloat(Arr::get($parts, 9)),
            'printed_tare_mt' => null,
            'axles' => null,
        ];
    }

    /**
     * Parse totals and summary values from the footer of the PDF text.
     *
     * Looks for labels like:
     *  - Gross Weight
     *  - Tare Weight
     *  - Net Weight
     *  - Under Load Wt.
     *  - Over Load Wt.
     *  - Maximum Train Speed
     *  - Maximum Weight
     *  - Total CC Weight
     *
     * and extracts the numeric value near each label.
     *
     * @return array<string, float|null>
     */
    private function parseTotalsFooter(string $text): array
    {
        $startPos = mb_stripos($text, 'Total Weights');
        $footer = $startPos !== false ? mb_substr($text, $startPos) : $text;

        $extract = function (string $pattern) use ($footer): ?float {
            if (preg_match($pattern, $footer, $matches)) {
                return $this->toFloat($matches[1]);
            }

            return null;
        };

        return [
            'total_gross_weight_mt' => $extract('/Gross\\s+Weight\\s*([0-9.,]+)/i'),
            'total_tare_weight_mt' => $extract('/Tare\\s+Weight\\s*([0-9.,]+)/i'),
            'total_net_weight_mt' => $extract('/Net\\s+Weight\\s*([0-9.,]+)/i'),
            'total_under_load_mt' => $extract('/Under\\s+Load\\s+Wt\\.?\\s*:?\\s*([0-9.,]+)/i'),
            'total_over_load_mt' => $extract('/Over\\s+Load\\s+Wt\\.?\\s*:?\\s*([0-9.,]+)/i'),
            'maximum_train_speed_kmph' => $extract('/Maximum\\s+Train\\s+Speed\\s*:?\\s*([0-9.,]+)/i'),
            'maximum_weight_mt' => $extract('/Maximum\\s+Weight\\s*:?\\s*([0-9.,]+)/i'),
            'total_cc_weight_mt' => $extract('/Total\\s+CC\\s+Weight\\s*:?\\s*([0-9.,]+)/i'),
        ];
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
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
     * @return array<int, Wagon>
     */
    private function insertWagons(Rake $rake, array $wagonRows): array
    {
        $wagons = [];
        $seenWagonNumbers = [];

        foreach ($wagonRows as $row) {
            $wagonNumber = (string) ($row['wagon_number'] ?? '');
            if ($wagonNumber === '' || isset($seenWagonNumbers[$wagonNumber])) {
                continue;
            }

            $seenWagonNumbers[$wagonNumber] = true;

            $wagons[] = Wagon::query()->create([
                'rake_id' => $rake->id,
                'wagon_sequence' => (int) ($row['sequence'] ?? 0),
                'wagon_number' => $wagonNumber,
                'wagon_type' => $row['wagon_type'] ?? null,
                'tare_weight_mt' => $row['tare_weight_mt'] ?? null,
                'pcc_weight_mt' => $row['cc_capacity_mt'] ?? null,
                'state' => 'historical',
            ]);
        }

        return $wagons;
    }

    /**
     * @param  array<int, array<string, mixed>>  $wagonRows
     * @param  array<int, Wagon>  $wagons
     */
    private function insertRakeWagonWeighments(Weighment $weighment, array $wagons, array $wagonRows): void
    {
        $wagonsByNumber = [];
        foreach ($wagons as $wagon) {
            $wagonsByNumber[$wagon->wagon_number] = $wagon;
        }

        foreach ($wagonRows as $row) {
            $wagonNumber = (string) ($row['wagon_number'] ?? '');
            if ($wagonNumber === '') {
                continue;
            }

            $wagon = $wagonsByNumber[$wagonNumber] ?? null;

            RakeWagonWeighment::query()->create([
                'rake_weighment_id' => $weighment->id,
                'wagon_id' => $wagon?->id,
                'wagon_number' => $wagonNumber,
                'wagon_sequence' => (int) ($row['sequence'] ?? 0),
                'wagon_type' => $row['wagon_type'] ?? null,
                'axles' => $row['axles'] ?? null,
                'cc_capacity_mt' => $row['cc_capacity_mt'] ?? null,
                'printed_tare_mt' => $row['printed_tare_mt'] ?? null,
                'actual_gross_mt' => $row['actual_gross_mt'] ?? null,
                'actual_tare_mt' => $row['tare_weight_mt'] ?? null,
                'net_weight_mt' => $row['net_weight_mt'] ?? null,
                'under_load_mt' => $row['under_load_mt'] ?? null,
                'over_load_mt' => $row['over_load_mt'] ?? null,
                'speed_kmph' => $row['speed_kmph'] ?? null,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $header
     * @param  array<int, array<string, mixed>>  $wagonRows
     */
    private function createRake(array $header, Siding $siding, array $wagonRows, int $userId): Rake
    {
        return Rake::query()->create([
            'siding_id' => $siding->id,
            'rake_number' => (string) $header['rake_number'],
            'rake_type' => $header['rake_type'] ?? null,
            'wagon_count' => count($wagonRows),
            'state' => 'historical',
            'data_source' => 'historical_weighment_pdf',
            'created_by' => $userId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $header
     * @param  array<string, float|null>  $totals
     */
    private function createWeighment(Rake $rake, array $header, string $storedPdfPath, int $userId, array $totals): Weighment
    {
        return Weighment::query()->create([
            'rake_id' => $rake->id,
            'attempt_no' => 1,
            'gross_weighment_datetime' => $header['gross_weighment_datetime'] ?? null,
            'tare_weighment_datetime' => $header['tare_weighment_datetime'] ?? null,
            'train_name' => $header['train_name'] ?? null,
            'direction' => $header['direction'] ?? null,
            'commodity' => $header['commodity'] ?? null,
            'from_station' => $header['from_station'] ?? null,
            'to_station' => $header['to_station'] ?? null,
            'priority_number' => $header['priority_number'] ?? null,
            'total_gross_weight_mt' => $totals['total_gross_weight_mt'] ?? null,
            'total_tare_weight_mt' => $totals['total_tare_weight_mt'] ?? null,
            'total_net_weight_mt' => $totals['total_net_weight_mt'] ?? null,
            'total_cc_weight_mt' => $totals['total_cc_weight_mt'] ?? null,
            'total_under_load_mt' => $totals['total_under_load_mt'] ?? null,
            'total_over_load_mt' => $totals['total_over_load_mt'] ?? null,
            'maximum_train_speed_kmph' => $totals['maximum_train_speed_kmph'] ?? null,
            'maximum_weight_mt' => $totals['maximum_weight_mt'] ?? null,
            'pdf_file_path' => $storedPdfPath,
            'status' => 'success',
            'created_by' => $userId,
        ]);
    }
}
