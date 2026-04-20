<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\ProvisionRakeForIndent;
use App\Models\Indent;
use App\Models\PowerPlant;
use App\Models\Rake;
use App\Models\Siding;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Spatie\PdfToText\Pdf;
use Throwable;

final readonly class IndentPdfImporter
{
    /**
     * Parse an uploaded PDF and return form prefill data without creating records.
     *
     * @param  array<int>  $allowedSidingIds  If non-empty, the detected siding must be in this list.
     * @return array<string, mixed>
     */
    public function previewImport(UploadedFile $pdf, int $userId, array $allowedSidingIds = []): array
    {
        Log::info('Indent PDF preview: starting', [
            'user_id' => $userId,
            'original_name' => $pdf->getClientOriginalName(),
            'size' => $pdf->getSize(),
        ]);

        $absolutePath = $pdf->getRealPath();
        $text = Pdf::getText($absolutePath, null, ['-layout']);

        return $this->previewFromText($text, $userId, $allowedSidingIds);
    }

    /**
     * Parse extracted PDF text and return form prefill data without creating records (e.g. for tests).
     *
     * @param  array<int>  $allowedSidingIds  If non-empty, the detected siding must be in this list.
     * @return array<string, mixed>
     */
    public function previewFromText(string $text, int $userId, array $allowedSidingIds = []): array
    {
        $this->assertIndentPdfDocument($text);

        $parsed = $this->parsePdfText($text);
        $eDemandRef = isset($parsed['e_demand_reference_id']) ? mb_trim((string) $parsed['e_demand_reference_id']) : '';
        if ($eDemandRef === '') {
            throw new InvalidArgumentException('This does not appear to be an indent PDF. e-Demand Reference Id could not be found.');
        }

        if (Indent::query()->where('e_demand_reference_id', $eDemandRef)->exists()) {
            throw new InvalidArgumentException('This e-Demand reference has already been imported.');
        }

        $siding = $this->detectSiding($text, $parsed);
        if (! $siding) {
            throw new InvalidArgumentException(
                'Unable to detect siding from indent PDF. Ensure "Station From" (e.g. WBPC, DMK, PKR) or siding name is present.'
            );
        }

        if ($allowedSidingIds !== [] && ! in_array($siding->id, $allowedSidingIds, true)) {
            $sidingLabel = $siding->name.($siding->code ? " ({$siding->code})" : '');
            throw new InvalidArgumentException(
                'The PDF was parsed but the detected siding ('.$sidingLabel.') is not accessible to you. Ask an admin to assign you to that siding.'
            );
        }

        $rakeSqNumber = isset($parsed['rake_sq_number']) ? mb_trim((string) $parsed['rake_sq_number']) : '';
        if ($rakeSqNumber === '') {
            throw new InvalidArgumentException('This does not appear to be a complete indent PDF. Rake Sq. Number could not be found.');
        }

        $indentDateParsed = isset($parsed['indent_date']) && $parsed['indent_date'] instanceof DateTimeInterface
            ? $parsed['indent_date']
            : null;
        $expectedLoadingParsed = isset($parsed['expected_loading_date']) && $parsed['expected_loading_date'] instanceof DateTimeInterface
            ? $parsed['expected_loading_date']
            : null;

        $referenceMonth = ProvisionRakeForIndent::referenceDateFromParsedPdf($indentDateParsed, $expectedLoadingParsed);

        app(ProvisionRakeForIndent::class)->assertRakeNumberFreeForSidingInIndentMonth(
            $rakeSqNumber,
            (int) $siding->id,
            $referenceMonth
        );

        $rakePriorityNumber = $this->suggestedRakePriorityNumber((int) $siding->id, $indentDateParsed, $expectedLoadingParsed);

        Log::info('Indent PDF preview: parsed and siding detected', [
            'user_id' => $userId,
            'indent_number' => $parsed['indent_number'] ?? null,
            'e_demand_reference_id' => $parsed['e_demand_reference_id'] ?? null,
            'rake_sq_number' => $rakeSqNumber,
            'siding_id' => $siding->id,
        ]);

        $indentAtCarbon = $this->resolveIndentAtForForm($indentDateParsed, $expectedLoadingParsed);

        $expectedLoadingForForm = $expectedLoadingParsed instanceof DateTimeInterface
            ? Date::parse($expectedLoadingParsed)->format('Y-m-d')
            : null;

        $destinationCode = isset($parsed['destination']) ? mb_trim((string) $parsed['destination']) : '';

        return [
            'siding_id' => (int) $siding->id,
            'rake_number' => $rakeSqNumber,
            'rake_serial_number' => $rakeSqNumber,
            'rake_priority_number' => $rakePriorityNumber,
            'expected_loading_date' => $expectedLoadingForForm,
            'demanded_stock' => isset($parsed['demanded_stock']) ? (string) $parsed['demanded_stock'] : '',
            'total_units' => isset($parsed['total_units']) ? (int) $parsed['total_units'] : 1,
            'target_quantity_mt' => isset($parsed['target_quantity_mt']) ? (float) $parsed['target_quantity_mt'] : 0.0,
            'destination' => $destinationCode,
            'indent_at' => $indentAtCarbon->format('Y-m-d\TH:i'),
            'indent_number' => isset($parsed['indent_number']) ? (string) $parsed['indent_number'] : '',
            'e_demand_reference_id' => isset($parsed['e_demand_reference_id']) ? (string) $parsed['e_demand_reference_id'] : '',
            'fnr_number' => isset($parsed['fnr_number']) ? (string) $parsed['fnr_number'] : '',
            'remarks' => isset($parsed['remarks']) && $parsed['remarks'] !== null ? (string) $parsed['remarks'] : '',
            'railway_reference_no' => isset($parsed['railway_reference_no']) ? (string) $parsed['railway_reference_no'] : '',
            'state' => 'pending',
        ];
    }

    private function suggestedRakePriorityNumber(int $sidingId, ?DateTimeInterface $indentDate, ?DateTimeInterface $expectedLoading): int
    {
        try {
            $reference = ProvisionRakeForIndent::referenceDateFromParsedPdf($indentDate, $expectedLoading);
        } catch (InvalidArgumentException) {
            return 0;
        }

        $maxPriorityThisMonthOnSiding = Rake::query()
            ->where('siding_id', $sidingId)
            ->whereYear('loading_date', $reference->year)
            ->whereMonth('loading_date', $reference->month)
            ->max('priority_number');

        return (int) $maxPriorityThisMonthOnSiding + 1;
    }

    private function resolveIndentAtForForm(?DateTimeInterface $indentDate, ?DateTimeInterface $expectedLoading): CarbonInterface
    {
        if ($indentDate instanceof DateTimeInterface) {
            return Date::parse($indentDate->format('Y-m-d H:i:s'));
        }

        if ($expectedLoading instanceof DateTimeInterface) {
            return Date::parse($expectedLoading->format('Y-m-d'))->startOfDay();
        }

        return Date::now();
    }

    private function assertIndentPdfDocument(string $text): void
    {
        if (! str_contains(mb_strtolower($text), 'e-demand confirmation slip')) {
            throw new InvalidArgumentException('This does not appear to be an indent PDF.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function parsePdfText(string $text): array
    {
        $out = [];

        if (preg_match('/e-Demand\s+Reference\s+Id\s*[:\-]\s*(\S+)/i', $text, $m)) {
            $out['e_demand_reference_id'] = mb_trim($m[1]);
        }
        if (preg_match('/Rake\s+Sq\.?\s*Number\s*[:\-]\s*(\S+)/iu', $text, $m)) {
            $out['rake_sq_number'] = mb_trim($m[1]);
        } elseif (preg_match('/Rake\s+Sequence\s+Number\s*[:\-]\s*(\S+)/iu', $text, $m)) {
            $out['rake_sq_number'] = mb_trim($m[1]);
        }
        if (preg_match('/Forwarding\s+Note\s+Number\s*[:\-]\s*([\d.]+)/i', $text, $m)) {
            $out['indent_number'] = mb_trim($m[1]);
        }
        if (preg_match('/Priority\s+Class\s*\/\s*Number\s*[:\-]\s*(.+?)(?=\s{2,}[A-Za-z]|\n|$)/s', $text, $m)) {
            $out['railway_reference_no'] = mb_trim($m[1]);
        }
        if (preg_match('/FNR\s+Number\s*[:\-]\s*(\S+)/i', $text, $m)) {
            $out['fnr_number'] = mb_trim($m[1]);
        }

        $demandedStockMatches = [];
        if (preg_match_all('/Demanded\s+Stock\s*[:\-]\s*(\S+)/i', $text, $m)) {
            $demandedStockMatches = array_merge($demandedStockMatches, $m[1]);
        }
        if (preg_match_all('/Stock\s+Demanded\s*[:\-]\s*(\S+)/i', $text, $m)) {
            $demandedStockMatches = array_merge($demandedStockMatches, $m[1]);
        }
        $invalidStockValues = ['units', 'units:'];
        $validStock = array_values(array_filter($demandedStockMatches, function (string $v) use ($invalidStockValues): bool {
            $v = mb_strtolower(mb_trim($v));

            return $v !== '' && ! in_array($v, $invalidStockValues, true);
        }));
        if ($validStock !== []) {
            $out['demanded_stock'] = mb_trim($validStock[count($validStock) - 1]);
        }

        $unitsMatches = [];
        if (preg_match_all('/Total\s+Units\s*[:\-]\s*(\d+)/i', $text, $m)) {
            $unitsMatches = array_merge($unitsMatches, $m[1]);
        }
        if (preg_match_all('/Units\s*[:\-]\s*(\d+)/i', $text, $m)) {
            $unitsMatches = array_merge($unitsMatches, $m[1]);
        }
        if ($unitsMatches !== []) {
            $out['total_units'] = (int) $unitsMatches[count($unitsMatches) - 1];
        }

        $weightMt = $this->extractWeightInTonnesMt($text);
        if ($weightMt !== null) {
            $out['target_quantity_mt'] = $weightMt;
        }

        if (preg_match('/Demand\s+Date\/Time\s*[:\-]\s*([0-9\-:\s]+)/i', $text, $m)) {
            $dt = $this->parseDate(mb_trim($m[1]));
            $out['indent_date'] = $dt;
            $out['indent_time'] = $dt;
        }
        if (preg_match('/Expected\s+Loading\s+Date\s*[:\-]\s*([0-9\-]+)/i', $text, $m)) {
            $out['expected_loading_date'] = $this->parseDate(mb_trim($m[1]));
        }

        if (preg_match('/Station\s+From\s*[:\-]\s*(\S+)/i', $text, $m)) {
            $out['station_from'] = mb_trim($m[1]);
        }
        if (preg_match('/Destination\s*[:\-]\s*(.+?)(?=\s{2,}[A-Za-z]|\n\n|$)/s', $text, $m)) {
            $raw = mb_trim($m[1]);
            $out['destination'] = $this->normalizeDestinationToPowerPlantCode($raw);
        }
        if (preg_match('/Booking\s+Remark\s*[:\-]\s*(.+?)(?=\n\n|\n[A-Z][a-z]+[\s:]|$)/s', $text, $m)) {
            $out['booking_remark'] = mb_trim($m[1]);
        }

        $remarks = [];
        if (! empty($out['booking_remark'])) {
            $remarks[] = 'Booking Remark: '.$out['booking_remark'];
        }
        $out['remarks'] = implode(' ', $remarks) ?: null;

        return $out;
    }

    private function normalizeDestinationToPowerPlantCode(string $raw): ?string
    {
        $raw = mb_trim(preg_replace('/\s+/', ' ', $raw) ?? $raw);
        if ($raw === '') {
            return null;
        }

        $upper = mb_strtoupper($raw);

        $powerPlantCodes = PowerPlant::query()
            ->pluck('code')
            ->filter()
            ->map(fn ($c) => mb_strtoupper(mb_trim((string) $c)))
            ->filter(fn ($c) => $c !== '')
            ->unique()
            ->values()
            ->all();

        // If the destination includes something in parentheses, only trust it when it matches
        // an existing power plant code (avoids false positives like "(Bihar)").
        if ($powerPlantCodes !== [] && preg_match('/\(([^)]+)\)/', $upper, $m)) {
            $inside = mb_trim($m[1]);
            $insideUpper = mb_strtoupper($inside);

            usort($powerPlantCodes, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
            foreach ($powerPlantCodes as $code) {
                if (mb_strlen($code) < 3) {
                    continue;
                }

                if (mb_strpos($insideUpper, $code) !== false) {
                    return $code;
                }
            }
        }

        if ($powerPlantCodes !== []) {
            // Prefer longest codes first; match anywhere in the string.
            usort($powerPlantCodes, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

            foreach ($powerPlantCodes as $code) {
                // Avoid very short codes causing false positives.
                if (mb_strlen($code) < 3) {
                    continue;
                }

                if (mb_strpos($upper, $code) !== false) {
                    return $code;
                }
            }
        }

        if (preg_match('/^\s*([A-Z0-9]{2,10})\s*$/', $upper, $m)) {
            return $m[1];
        }

        if (preg_match('/\b([A-Z0-9]{2,10})\b/', $upper, $m)) {
            return $m[1];
        }

        return $raw;
    }

    /**
     * e-Demand PDFs often lay out "Weight (In Tonnes):" on the header row and put the value on the
     * next line as the last column (e.g. "BOBRN                        60                   3960").
     */
    private function extractWeightInTonnesMt(string $text): ?float
    {
        $lines = preg_split("/\r\n|\n|\r/", $text) ?: [];

        // Prefer the structured stock table section:
        // "Stock Demanded:  Units:  Weight (In Tonnes):" then the next row has "... 60 3960".
        // In some extracted text, these header tokens may be split across multiple lines, so we
        // check a small 3-line window.
        for ($i = 0; $i < count($lines); $i++) {
            $line = mb_trim((string) $lines[$i]);
            if ($line === '') {
                continue;
            }

            $window = $line;
            if (isset($lines[$i + 1])) {
                $window .= ' '.mb_trim((string) $lines[$i + 1]);
            }
            if (isset($lines[$i + 2])) {
                $window .= ' '.mb_trim((string) $lines[$i + 2]);
            }

            $isHeader = preg_match('/Stock\s+D(?:emanded|emand)\s*:/iu', $window) === 1
                && preg_match('/Units\s*:/iu', $window) === 1
                && preg_match('/Weight\s*\(\s*In\s*Tonne?s?\s*\)/iu', $window) === 1;

            if (! $isHeader) {
                continue;
            }

            for ($j = $i + 1; $j < min($i + 10, count($lines)); $j++) {
                $row = mb_trim((string) $lines[$j]);
                if ($row === '' || preg_match('/page\s+\d+\s+of\s+\d+/i', $row)) {
                    continue;
                }

                // OCR sometimes inserts spaces inside thousands (e.g. "3 960"). Only collapse the
                // specific pattern "<1-2 digits> <3 digits>" to avoid merging table columns like "60 3960".
                $row = preg_replace('/(?<!\d)(\d{1,2})\s+(\d{3})(?!\d)/u', '$1$2', $row) ?? $row;

                if (preg_match_all('/\d[\d.,]*/', $row, $nums) && $nums[0] !== []) {
                    $last = $nums[0][array_key_last($nums[0])];
                    $weight = $this->toFloat($last);
                    if ($weight !== null) {
                        return $weight;
                    }
                }
            }
        }

        // Fallback: sometimes the text includes "Weight (In Tonnes): 3960" on the same line.
        if (preg_match_all(
            '/Weight\s*\(\s*In\s*Tonne?s?\s*\)\s*[:\-]?\s*([\d., ]+)/iu',
            $text,
            $m
        )) {
            return $this->toFloat($m[1][array_key_last($m[1])]);
        }

        return null;
    }

    private function detectSiding(string $text, array $parsed): ?Siding
    {
        $stationFrom = $parsed['station_from'] ?? null;
        if ($stationFrom !== null && mb_trim((string) $stationFrom) !== '') {
            $tokenUpper = mb_strtoupper(mb_trim((string) $stationFrom));
            $candidates = array_values(array_unique(array_filter(
                [$this->applyPdfStationCodeAliases($tokenUpper), $tokenUpper],
                static fn (string $c): bool => $c !== ''
            )));
            foreach ($candidates as $candidate) {
                $siding = Siding::query()
                    ->where('station_code', $candidate)
                    ->orWhere('code', $candidate)
                    ->first();
                if ($siding !== null) {
                    return $siding;
                }
            }
        }

        $upper = mb_strtoupper($text);
        $keywords = ['DMK' => 'DMK', 'DUMK' => 'DMK', 'KRW' => 'KRW', 'KURWA' => 'KRW', 'PKR' => 'PKR', 'PKUR' => 'PKUR', 'WBPC' => 'WBPC'];
        $aliases = config('rrmcs.rr_from_station_pdf_code_aliases', []);
        if (is_array($aliases)) {
            foreach ($aliases as $from => $to) {
                if (! is_string($from) || ! is_string($to)) {
                    continue;
                }
                $fromUpper = mb_strtoupper(mb_trim($from));
                if ($fromUpper === '' || isset($keywords[$fromUpper])) {
                    continue;
                }
                $keywords[$fromUpper] = mb_strtoupper(mb_trim($to));
            }
        }
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
     * Map e-Demand / IR tokens to sidings.station_code (or siding code), same as Railway Receipt uploads.
     */
    private function applyPdfStationCodeAliases(string $stationCodeUpper): string
    {
        $aliases = config('rrmcs.rr_from_station_pdf_code_aliases', []);
        if (! is_array($aliases)) {
            return $stationCodeUpper;
        }
        if (isset($aliases[$stationCodeUpper]) && is_string($aliases[$stationCodeUpper])) {
            return mb_strtoupper(mb_trim($aliases[$stationCodeUpper]));
        }

        return $stationCodeUpper;
    }

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

    private function parseDate(?string $value): ?CarbonInterface
    {
        if ($value === null || mb_trim($value) === '') {
            return null;
        }
        try {
            return Date::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }
        $value = str_replace([',', ' '], '', (string) $value);
        if (! is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
    }
}
