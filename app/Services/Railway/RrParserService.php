<?php

declare(strict_types=1);

namespace App\Services\Railway;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Spatie\PdfToText\Exceptions\CouldNotExtractText;
use Spatie\PdfToText\Pdf;

/**
 * Section-based RR PDF parser.
 *
 * Parses the document by logical sections to avoid mixing values from different areas.
 * - SECTION 1: RR Header → rr_documents
 * - SECTION 2: Rake Summary → rakes (wagon_count, total_weight, rr_date)
 * - SECTION 3: Wagon Table → wagons (only rows from wagon table, skip totals)
 * - SECTION 4: Charges → rr_charges
 * - SECTION 5: Penalties → applied_penalties (handled by RrImportService)
 */
final readonly class RrParserService
{
    private const WAGON_SECTION_START = 'Wagon details of the Railway Receipt';

    private const WAGON_TABLE_TOTAL_PATTERNS = ['Total:', 'Grand Total', 'TOTAL'];

    private const KNOWN_CHARGE_CODES = ['FREIGHT', 'OTC', 'POL1', 'POLA', 'DEM', 'GST', 'PCLA'];

    /**
     * Parse an RR PDF and return structured data in the format expected by RrImportService.
     *
     * @return array{
     *     rr_number: string,
     *     fnr: string|null,
     *     rr_date: string|null,
     *     rr_received_date: string|null,
     *     distance_km: float,
     *     total_weight: float,
     *     wagon_count: int,
     *     freight_total: float,
     *     from_station_code: string|null,
     *     to_station_code: string|null,
     *     commodity_code: string|null,
     *     commodity_description: string|null,
     *     invoice_number: string|null,
     *     invoice_date: string|null,
     *     wagons: array<int, array{sequence: int, wagon_number: string, wagon_type: string|null, pcc_weight: float, loaded_weight: float, permissible_weight: float, overload_weight: float}>,
     *     charges: array<int, array{code: string, name: string|null, amount: float}>,
     *     raw_text: string
     * }
     */
    public function parse(UploadedFile $file): array
    {
        $realPath = $file->getRealPath();
        $storedPath = null;
        if ($realPath && is_readable($realPath)) {
            $fullPath = $realPath;
        } else {
            $storedPath = $file->store('rr-parse-temp');
            $fullPath = storage_path('app/'.$storedPath);
        }

        try {
            $text = Pdf::getText($fullPath, null, ['-layout']);
        } catch (CouldNotExtractText $e) {
            Log::error('RR PDF text extraction failed', ['path' => $fullPath, 'error' => $e->getMessage()]);
            throw new InvalidArgumentException('Could not extract text from PDF. Ensure the file is a valid PDF and pdftotext is installed.', 0, $e);
        } finally {
            if ($storedPath !== null) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($storedPath);
            }
        }

        if (mb_trim($text) === '') {
            throw new InvalidArgumentException('PDF appears to be empty or could not extract any text.');
        }

        return $this->parseExtractedText($text);
    }

    /**
     * Parse extracted text into structured RR data using section-based extraction.
     */
    public function parseExtractedText(string $text): array
    {
        $this->assertRailwayReceiptDocument($text);

        $headerSection = $this->extractHeaderSection($text);
        $wagonSection = $this->extractWagonSection($text);
        $chargesSection = $this->extractChargesSection($text);

        $header = $this->parseHeaderSection($headerSection);
        $wagons = $this->parseWagonSection($wagonSection);
        $charges = $this->parseChargesSection($chargesSection);

        if (empty($header['rr_number'])) {
            throw new InvalidArgumentException('This does not appear to be a Railway Receipt PDF. RR number could not be found.');
        }

        $totalWeight = (float) ($header['total_weight'] ?? 0);
        if ($totalWeight === 0.0 && ! empty($wagons)) {
            $totalWeight = (float) array_sum(array_column($wagons, 'loaded_weight'));
        }

        $wagonCount = (int) ($header['wagon_count'] ?? count($wagons));
        if ($wagonCount === 0 && ! empty($wagons)) {
            $wagonCount = count($wagons);
        }

        $rrDate = $header['rr_received_date'] ?? null;

        return [
            'rr_number' => $header['rr_number'],
            'fnr' => $header['fnr'] ?? null,
            'rr_date' => $rrDate,
            'rr_received_date' => $rrDate,
            'rr_weight_mt' => $totalWeight,
            'distance_km' => (float) ($header['distance_km'] ?? 0),
            'total_weight' => $totalWeight,
            'wagon_count' => $wagonCount,
            'freight_total' => (float) ($header['freight_total'] ?? 0),
            'from_station_code' => $header['from_station_code'] ?? null,
            'to_station_code' => $header['to_station_code'] ?? null,
            'commodity_code' => $header['commodity_code'] ?? null,
            'commodity_description' => $header['commodity_description'] ?? null,
            'invoice_number' => $header['invoice_number'] ?? null,
            'invoice_date' => $header['invoice_date'] ?? null,
            'rate' => isset($header['rate']) ? (float) $header['rate'] : null,
            'class' => $header['class'] ?? null,
            'wagons' => $wagons,
            'charges' => $charges,
            'raw_text' => $text,
        ];
    }

    private function assertRailwayReceiptDocument(string $text): void
    {
        $lower = mb_strtolower($text);
        $hasEtRr = str_contains($lower, 'electronically transmitted railway receipt')
            || str_contains($lower, 'et-rr');

        if (! $hasEtRr) {
            throw new InvalidArgumentException('This does not appear to be a Railway Receipt PDF.');
        }
    }

    private function extractHeaderSection(string $text): string
    {
        $pos = mb_stripos($text, self::WAGON_SECTION_START);
        if ($pos !== false) {
            return mb_substr($text, 0, $pos);
        }

        return $text;
    }

    private function extractWagonSection(string $text): string
    {
        $start = mb_stripos($text, self::WAGON_SECTION_START);
        if ($start === false) {
            return '';
        }
        $start += mb_strlen(self::WAGON_SECTION_START);

        $end = mb_strlen($text);
        foreach (self::WAGON_TABLE_TOTAL_PATTERNS as $pattern) {
            $pos = mb_strpos($text, $pattern, $start);
            if ($pos !== false && $pos > $start && $pos < $end) {
                $end = $pos;
            }
        }
        if ($end === mb_strlen($text)) {
            $match = preg_match('/\nTotal\s*:\s*\d/m', mb_substr($text, $start), $m, PREG_OFFSET_CAPTURE);
            if ($match) {
                $end = $start + $m[0][1];
            }
        }

        return mb_substr($text, $start, $end - $start) ?: '';
    }

    private function extractChargesSection(string $text): string
    {
        $start = mb_strlen($text);
        foreach (['Freight:', 'Other Charges'] as $marker) {
            $pos = mb_stripos($text, $marker);
            if ($pos !== false && $pos < $start) {
                $start = $pos;
            }
        }
        if ($start === mb_strlen($text)) {
            return '';
        }

        $end = mb_strlen($text);
        $totalFreightPos = mb_stripos($text, 'Total Freight:', $start);
        if ($totalFreightPos !== false) {
            $end = $totalFreightPos + 200;
        }

        return mb_substr($text, $start, min($end - $start, 2500)) ?: '';
    }

    /**
     * @return array<string, mixed>
     */
    private function parseHeaderSection(string $headerText): array
    {
        $result = [];

        if (preg_match('/RR\s*No\.?\s*:\s*(\d{8,12})/i', $headerText, $m)) {
            $result['rr_number'] = mb_trim($m[1]);
        } elseif (preg_match('/RR\s*[#:]?\s*(\d{8,12})/i', $headerText, $m)) {
            $result['rr_number'] = mb_trim($m[1]);
        }

        if (preg_match('/FNR\s*:\s*(\S+)/i', $headerText, $m)) {
            $result['fnr'] = mb_trim($m[1]);
        } elseif (preg_match('/F\s*[\/]?\s*Note\s*(?:No\.?|Number)?\s*:\s*(\S+)/i', $headerText, $m)) {
            $result['fnr'] = mb_trim($m[1]);
        }

        if (preg_match('/RR\s*Date\s*:\s*(\d{2}[-\/]\d{2}[-\/]\d{4})/i', $headerText, $m)) {
            $result['rr_received_date'] = $this->normalizeDate($m[1]);
        } elseif (preg_match('/Invoice\s*Date\s*:\s*(\d{2}[-\/]\d{2}[-\/]\d{4})/i', $headerText, $m)) {
            $result['rr_received_date'] = $this->normalizeDate($m[1]);
        }

        // From Station / Siding: code is looked up in sidings table (alphabetical codes)
        if (! isset($result['from_station_code'])) {
            if (preg_match('/Station\s+From\s*:\s*([A-Za-z0-9]+)/i', $headerText, $m)) {
                $result['from_station_code'] = mb_trim($m[1]);
            } elseif (preg_match('/From\s+Station\s*[\/]?\s*Siding[^:]*[Cc]ode\s*:\s*([A-Za-z0-9]+)/i', $headerText, $m)) {
                $result['from_station_code'] = mb_trim($m[1]);
            }
        }
        // To Station / Siding: code is looked up in power_plants table (alphabetical codes)
        if (! isset($result['to_station_code'])) {
            if (preg_match('/Station\s+To\s*:\s*([A-Za-z0-9]+)/i', $headerText, $m)) {
                $result['to_station_code'] = mb_trim($m[1]);
            } elseif (preg_match('/To\s+Station\s*[\/]?\s*Siding[^:]*[Cc]ode\s*:\s*([A-Za-z0-9]+)/i', $headerText, $m)) {
                $result['to_station_code'] = mb_trim($m[1]);
            }
        }

        if (preg_match('/Distance\s*\(In\s*KM\)\s*:\s*(\d+)/i', $headerText, $m)) {
            $result['distance_km'] = (float) $m[1];
        } elseif (preg_match('/Distance\s*[#:]?\s*([\d,]+(?:\.\d+)?)\s*(?:km)?/i', $headerText, $m)) {
            $result['distance_km'] = (float) str_replace(',', '', $m[1]);
        }

        if (preg_match('/Wagons?\s*:\s*(\d+)/i', $headerText, $m)) {
            $result['wagon_count'] = (int) $m[1];
        }
        if (preg_match('/Total\s+Weight\s*:\s*([\d,]+(?:\.\d+)?)/i', $headerText, $m)) {
            $result['total_weight'] = (float) str_replace(',', '', $m[1]);
        } elseif (preg_match('/Chargeable\s+Weight\s*[^\d]*([\d,]+(?:\.\d+)?)/i', $headerText, $m)) {
            $result['total_weight'] = (float) str_replace(',', '', $m[1]);
        }

        if (preg_match('/Freight:\s*Rs\s*([\d,]+(?:\.\d+)?)/i', $headerText, $m)) {
            $result['freight_total'] = (float) str_replace(',', '', $m[1]);
        }
        if (preg_match('/Total\s+Freight:\s*Rs\s*([\d,]+)/i', $headerText, $m)) {
            $freight = (float) str_replace(',', '', $m[1]);
            if (($result['freight_total'] ?? 0) === 0.0) {
                $result['freight_total'] = $freight;
            }
        }

        if (preg_match('/Commodity\s*Code\s*:\s*(\d{6,10})/i', $headerText, $m)) {
            $result['commodity_code'] = mb_trim($m[1]);
        }
        if (preg_match('/Commodity\s*Description\s*:\s*([^\n]+?)(?:\s*\([^)]*\))?\s*$/im', $headerText, $m)) {
            $result['commodity_description'] = mb_trim($m[1]);
        }

        if (preg_match('/Invoice\s*(?:No\.?|Number)\s*:\s*(\d+)/i', $headerText, $m)) {
            $result['invoice_number'] = mb_trim($m[1]);
        }
        if (preg_match('/Invoice\s*Date\s*:\s*(\d{2}[-\/]\d{2}[-\/]\d{4})/i', $headerText, $m)) {
            $result['invoice_date'] = $this->normalizeDate($m[1]);
        }

        if (preg_match('/Class\s*:\s*(\S+)/i', $headerText, $m)) {
            $class = mb_trim($m[1]);
            if ($class !== '-' && $class !== '') {
                $result['class'] = $class;
            }
        }
        if (preg_match('/\bRate\s*:\s*([\d.]+)/i', $headerText, $m)) {
            $rate = (float) $m[1];
            if ($rate > 0) {
                $result['rate'] = $rate;
            }
        }

        return $result;
    }

    /**
     * Parse wagon table rows only from the wagon section. Each row has:
     * Sr No, Owning Rly, Type, Wagon Number (may span 2 lines), CC, Tare, ..., Actual Wt, Permissible CC, Over Weight, Chargeable Wt
     *
     * @return array<int, array{sequence: int, wagon_number: string, wagon_type: string|null, pcc_weight: float, loaded_weight: float, permissible_weight: float, overload_weight: float}>
     */
    private function parseWagonSection(string $wagonSection): array
    {
        $wagons = [];
        $lines = preg_split('/\r\n|\r|\n/', $wagonSection);
        $i = 0;

        while ($i < count($lines)) {
            $line = mb_trim($lines[$i]);
            $i++;

            if ($line === '') {
                continue;
            }

            if ($this->isTotalOrSummaryRow($line)) {
                continue;
            }

            $continuationRaw = ($i < count($lines)) ? $lines[$i] : null;
            if ($continuationRaw !== null && $this->isWagonContinuationLine($continuationRaw)) {
                $i++;
            } else {
                $continuationRaw = null;
            }

            $wagon = $this->parseWagonRow($line, $continuationRaw);
            if ($wagon !== null) {
                $wagons[] = $wagon;
            }
        }

        return $wagons;
    }

    private function isWagonContinuationLine(string $line): bool
    {
        return (bool) preg_match('/^\s{10,}\d{2,4}(?:\s+\d+\.?\d*)?\s*$/', $line);
    }

    private function isTotalOrSummaryRow(string $line): bool
    {
        $lower = mb_strtolower($line);
        foreach (self::WAGON_TABLE_TOTAL_PATTERNS as $pattern) {
            if (mb_strpos($lower, mb_strtolower($pattern)) === 0) {
                return true;
            }
        }
        if (preg_match('/^Total\s*:/i', $line)) {
            return true;
        }

        return false;
    }

    /**
     * Parse a wagon data row. Format: "N RLY TYPE WAGON_NUM CC TARE 0 COMMODITY GROSS DIP ACTUAL PERM OVERLOAD ... CHARGEABLE"
     * Wagon number may be split: first line has 7-8 digits, continuation has 2-3 digits.
     *
     * @return array{sequence: int, wagon_number: string, wagon_type: string|null, pcc_weight: float, loaded_weight: float, permissible_weight: float, overload_weight: float}|null
     */
    private function parseWagonRow(string $line, ?string $continuation): ?array
    {
        if (! preg_match('/^\s*(\d+)\s+([A-Z]{2,4})\s+([A-Z][A-Z0-9]{4,10})\s+(\d{7,8})\s+/i', $line, $m)) {
            return null;
        }

        $seq = (int) $m[1];
        $wagonType = mb_strtoupper($m[3]);
        $wagonNumMain = $m[4];

        $wagonNumSuffix = '';
        if ($continuation !== null && preg_match('/^\s{10,}(\d{2,4})(?:\s+\d+\.?\d*)?\s*$/', $continuation, $cm)) {
            $wagonNumSuffix = $cm[1];
        }
        $wagonNumber = $wagonNumMain.$wagonNumSuffix;

        $rest = mb_substr($line, mb_strlen($m[0]));
        $numbers = $this->extractNumbersFromWagonRest($rest);

        $pcc = $numbers['cc'] ?? 0.0;
        $loaded = $numbers['actual'] ?? $numbers['chargeable'] ?? $pcc;
        $permissible = $numbers['permissible'] ?? $pcc;
        $overload = $numbers['overload'] ?? 0.0;

        return [
            'sequence' => $seq,
            'wagon_number' => $wagonNumber,
            'wagon_type' => $wagonType,
            'pcc_weight' => $pcc,
            'loaded_weight' => $loaded,
            'permissible_weight' => $permissible,
            'overload_weight' => $overload,
        ];
    }

    /**
     * @return array{cc?: float, tare?: float, actual?: float, permissible?: float, overload?: float, chargeable?: float}
     */
    private function extractNumbersFromWagonRest(string $rest): array
    {
        $nums = [];
        if (preg_match_all('/(\d+\.?\d*)/', $rest, $matches)) {
            foreach ($matches[1] as $v) {
                $nums[] = (float) $v;
            }
        }

        $result = [];
        $n = count($nums);
        if ($n >= 1) {
            $result['cc'] = $nums[0];
        }
        if ($n >= 7) {
            $result['actual'] = $nums[6];
        }
        if ($n >= 8) {
            $result['permissible'] = $nums[7];
        }
        if ($n >= 9) {
            $result['overload'] = $nums[8];
        }
        if ($n >= 11) {
            $result['chargeable'] = $nums[10];
        }

        return $result;
    }

    /**
     * @return array<int, array{code: string, name: string|null, amount: float}>
     */
    private function parseChargesSection(string $chargesText): array
    {
        $charges = [];
        $seen = [];

        if (preg_match('/Freight:\s*Rs\s*([\d,]+)/i', $chargesText, $m) && ! isset($seen['FREIGHT'])) {
            $charges[] = [
                'code' => 'FREIGHT',
                'name' => 'Freight',
                'amount' => (float) str_replace(',', '', $m[1]),
            ];
            $seen['FREIGHT'] = true;
        }

        if (preg_match('/Total\s+Freight:\s*Rs\s*([\d,]+)/i', $chargesText, $m)) {
            $amount = (float) str_replace(',', '', $m[1]);
            if (! isset($seen['FREIGHT'])) {
                $charges[] = ['code' => 'FREIGHT', 'name' => 'Freight', 'amount' => $amount];
                $seen['FREIGHT'] = true;
            }
        }

        foreach (self::KNOWN_CHARGE_CODES as $code) {
            if ($code === 'FREIGHT' && isset($seen['FREIGHT'])) {
                continue;
            }
            if (preg_match('/\*?\s*'.preg_quote($code, '/').'\s+([\d,]+(?:\.\d+)?)/i', $chargesText, $m) && ! isset($seen[$code])) {
                $amount = (float) str_replace(',', '', $m[1]);
                $charges[] = [
                    'code' => $code,
                    'name' => $this->chargeCodeToName($code),
                    'amount' => $amount,
                ];
                $seen[$code] = true;
            }
        }

        if (preg_match_all('/([A-Z]{2,6})\s+([\d,]+(?:\.\d+)?)/', $chargesText, $matches, PREG_SET_ORDER)) {
            $skip = ['Code', 'RS', 'Amount', 'MT', 'KM', 'OR', 'NO'];
            foreach ($matches as $m) {
                $code = $m[1];
                if (isset($seen[$code]) || in_array($code, $skip, true)) {
                    continue;
                }
                $amount = (float) str_replace(',', '', $m[2]);
                if ($amount < 1 || $amount > 999_999_999) {
                    continue;
                }
                $charges[] = [
                    'code' => $code,
                    'name' => $this->chargeCodeToName($code),
                    'amount' => $amount,
                ];
                $seen[$code] = true;
            }
        }

        return $charges;
    }

    private function chargeCodeToName(string $code): ?string
    {
        return match (mb_strtoupper($code)) {
            'FREIGHT' => 'Freight',
            'OTC' => 'Other Charges',
            'POL1' => 'Punitive Overloading (Individual Wagon)',
            'POLA' => 'Punitive Overloading (Average)',
            'DEM' => 'Demurrage',
            'GST' => 'GST',
            'PCLA' => 'PCLA',
            default => null,
        };
    }

    private function normalizeDate(string $date): string
    {
        if (preg_match('/^(\d{2})[-\/](\d{2})[-\/](\d{4})$/', $date, $m)) {
            return $m[3].'-'.$m[2].'-'.$m[1];
        }
        if (preg_match('/^(\d{4})[-\/](\d{2})[-\/](\d{2})$/', $date, $m)) {
            return $m[1].'-'.$m[2].'-'.$m[3];
        }

        return $date;
    }
}
