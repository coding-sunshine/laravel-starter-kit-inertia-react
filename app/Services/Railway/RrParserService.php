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
    /** Standard electronically transmitted RR layout (ICR / FOIS canonical copy). */
    public const RR_FORMAT_ET_RR = 'et_rr';

    /** FOIS portal “RR Details” printout (different labels and wagon table layout). */
    public const RR_FORMAT_FOIS_PRINTED = 'fois_printed';

    /** Multi-page ET-RR (SR. OWNG wagon table; CC / CHBL / Actl. column layout). */
    public const RR_FORMAT_ET_RR_MULTIPAGE = 'et_rr_multipage';

    private const WAGON_SECTION_START = 'Wagon details of the Railway Receipt';

    private const FOIS_WAGON_SECTION_START = 'Wagon Details of the RR';

    private const FOIS_DETECT_WINDOW = 4000;

    private const WAGON_TABLE_TOTAL_PATTERNS = ['Total:', 'Grand Total', 'TOTAL'];

    private const KNOWN_CHARGE_CODES = ['FREIGHT', 'OTC', 'POL1', 'POLA', 'DEM', 'GST', 'PCLA'];

    /**
     * Parse an RR PDF and return structured data in the format expected by RrImportService.
     *
     * @return array{
     *     rr_format: 'et_rr'|'et_rr_multipage'|'fois_printed',
     *     rr_number: string,
     *     fnr: string|null,
     *     rr_date: string|null,
     *     rr_received_date: string|null,
     *     actual_weight_mt: float|null,
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
     *     wagons: array<int, array{sequence: int, wagon_number: string, wagon_type: string|null, pcc_weight: float, loaded_weight: float, permissible_weight: float, overload_weight: float, tare_weight?: float|null, gross_weight?: float|null}>,
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
        // Standard eT-RR duplicate PDF (rr_format: et_rr). Checked first.
        // Detect: "Wagon details of the Railway Receipt" + ET-RR title (e.g. 26060617723-unlocked.pdf).
        // Wagon weights: CC(T)→pcc, Actual Wt(T)→loaded, Permissible CC→permissible (not Chargeable Wt column).
        if ($this->detectStandardEtRrFormat($text)) {
            return $this->parseExtractedTextStandardEtRr($text);
        }

        // Multi-page eT-RR side layout (rr_format: et_rr_multipage). No standard wagon section title.
        // Detect: full ET-RR title + "SR. OWNG … WGON" header or compact wagon rows (e.g. BMGK_BTMT_RR_461001833).
        // Wagon weights: CC(T)→pcc, CHBL WT→permissible, Actl. Wt.→loaded; header Total Weight→chargeable total.
        if ($this->detectEtRrMultipageFormat($text)) {
            return $this->parseExtractedTextEtRrMultipage($text);
        }

        // FOIS portal print / RR from railway (rr_format: fois_printed).
        // Detect: "Wagon Details of the RR" + RR DETAILS / fois URL in header (not an eT-RR duplicate).
        // Wagon weights: PERM→permissible; CHBL column is chargeable only (header TOTAL CHRG WT / CHBL WGHT→total_weight).
        if ($this->detectFoisPrintedFormat($text)) {
            return $this->parseExtractedTextFoisPrinted($text);
        }

        throw new InvalidArgumentException('This does not appear to be a Railway Receipt PDF.');
    }

    private function detectStandardEtRrFormat(string $text): bool
    {
        if (mb_stripos($text, self::WAGON_SECTION_START) === false) {
            return false;
        }

        $lower = mb_strtolower($text);

        return str_contains($lower, 'electronically transmitted railway receipt')
            || str_contains($lower, 'et-rr')
            || str_contains($lower, '(et-rr)')
            || str_contains($lower, 'et_rr')
            || str_contains($lower, '(et_rr)');
    }

    private function detectFoisPrintedFormat(string $text): bool
    {
        if (mb_stripos($text, self::FOIS_WAGON_SECTION_START) === false) {
            return false;
        }

        $lead = mb_substr($text, 0, self::FOIS_DETECT_WINDOW);
        if (mb_stripos($lead, 'RR DETAILS') !== false || mb_stripos($lead, 'fois.indianrail.gov.in') !== false) {
            return true;
        }

        return (bool) preg_match('/^\s*RR\s*NO\.?\s+\d{8,12}/im', mb_substr($text, 0, self::FOIS_DETECT_WINDOW));
    }

    private function detectEtRrMultipageFormat(string $text): bool
    {
        $lower = mb_strtolower($text);

        if (! str_contains($lower, 'electronically transmitted railway receipt')) {
            return false;
        }

        if (mb_stripos($text, self::WAGON_SECTION_START) !== false) {
            return false;
        }

        if (mb_stripos($text, self::FOIS_WAGON_SECTION_START) !== false) {
            return false;
        }

        if (preg_match('/SR\.\s+OWNG.*WGON/is', $text)) {
            return true;
        }

        return (bool) preg_match('/^\s*\d+\s+[A-Z]{2,4}\s+[A-Z][A-Z0-9]+\s+\d{9,14}\s+/im', $text);
    }

    /**
     * Multi-page ET-RR layout (BMGK-style): CC → pcc, CHBL → permissible, Actl. → loaded.
     *
     * @return array<string, mixed>
     */
    private function parseExtractedTextEtRrMultipage(string $text): array
    {
        $header = $this->parseEtRrMultipageHeader($text);
        $chargesSlice = $this->extractEtRrMultipageChargesSection($text);
        $charges = $this->parseEtRrMultipageChargesSection($chargesSlice);

        $wagons = $this->parseEtRrMultipageWagonSection($text);

        if (empty($header['rr_number'])) {
            throw new InvalidArgumentException('This does not appear to be a Railway Receipt PDF. RR number could not be found.');
        }

        $totalWeight = (float) ($header['total_weight'] ?? 0);
        if ($totalWeight === 0.0 && ! empty($wagons)) {
            $chargeableSum = round((float) array_sum(array_column($wagons, 'permissible_weight')), 6);
            $totalWeight = $chargeableSum > 0.0
                ? $chargeableSum
                : (float) array_sum(array_column($wagons, 'loaded_weight'));
        }

        $wagonCount = (int) ($header['wagon_count'] ?? count($wagons));
        if ($wagonCount === 0 && ! empty($wagons)) {
            $wagonCount = count($wagons);
        }

        $rrDate = $header['rr_received_date'] ?? null;
        $freightTotalResolved = (float) ($header['freight_total'] ?? 0);
        if ($freightTotalResolved <= 0.0 && $charges !== []) {
            foreach ($charges as $chargeLine) {
                if (($chargeLine['code'] ?? '') === 'FREIGHT' && (($chargeLine['amount'] ?? 0) > 0)) {
                    $freightTotalResolved = (float) $chargeLine['amount'];
                    break;
                }
            }
        }

        return [
            'rr_format' => self::RR_FORMAT_ET_RR_MULTIPAGE,
            'rr_number' => $header['rr_number'],
            'fnr' => $header['fnr'] ?? null,
            'rr_date' => $rrDate,
            'rr_received_date' => $rrDate,
            'actual_weight_mt' => isset($header['actual_weight_mt']) ? (float) $header['actual_weight_mt'] : null,
            'rr_weight_mt' => $totalWeight,
            'distance_km' => (float) ($header['distance_km'] ?? 0),
            'total_weight' => $totalWeight,
            'wagon_count' => $wagonCount,
            'freight_total' => $freightTotalResolved,
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

    /**
     * @return array<string, mixed>
     */
    private function parseEtRrMultipageHeader(string $headerText): array
    {
        $result = [];

        if (preg_match('/\bRR\s*No\.?\s*:?\s*(\d{8,12})/i', $headerText, $m)) {
            $result['rr_number'] = mb_trim($m[1]);
        }

        if (preg_match('#\bRR\s*Date\s*:?\s*(\d{2}[.\-/]\d{2}[.\-/]\d{4})#i', $headerText, $m)) {
            $result['rr_received_date'] = $this->normalizeDate($m[1]);
        }

        if (preg_match('/\bFNR\s*:?\s*(\d{8,14})/i', $headerText, $m)) {
            $result['fnr'] = mb_trim($m[1]);
        }

        if (preg_match('/\bStation\s+From\s*:?\s*([A-Za-z0-9]{2,12})/i', $headerText, $m)) {
            $result['from_station_code'] = mb_strtoupper(mb_trim($m[1]));
        }

        if (preg_match('/\bStation\s+To\s*:?\s*([A-Za-z0-9]{2,12})/i', $headerText, $m)) {
            $result['to_station_code'] = mb_strtoupper(mb_trim($m[1]));
        }

        if (preg_match('/\bWagons\s+(\d+)/i', $headerText, $m)) {
            $result['wagon_count'] = (int) $m[1];
        }

        if (preg_match('/\bTotal\s+Weight\s+([\d,]+(?:\.\d+)?)/i', $headerText, $m)) {
            $result['total_weight'] = (float) str_replace(',', '', mb_trim($m[1]));
        }

        if (preg_match('/\bActual\s+([\d,]+(?:\.\d+)?)/i', $headerText, $m)) {
            $result['actual_weight_mt'] = (float) str_replace(',', '', mb_trim($m[1]));
        }

        if (preg_match('/\bChargeable\s+([\d,]+(?:\.\d+)?)/i', $headerText, $m)) {
            $chargeable = (float) str_replace(',', '', mb_trim($m[1]));
            if (($result['total_weight'] ?? 0) <= 0.0) {
                $result['total_weight'] = $chargeable;
            }
        }

        if (preg_match('/\bDistance\s*\(\s*In\s*Km\s*\)\s+([\d,]+(?:\.\d+)?)/i', $headerText, $m)) {
            $result['distance_km'] = (float) str_replace(',', '', mb_trim($m[1]));
        }

        if (preg_match('/\bClass\s+([A-Za-z0-9]+)/i', $headerText, $m)) {
            $cls = mb_trim($m[1]);
            if ($cls !== '' && $cls !== '-') {
                $result['class'] = mb_substr($cls, 0, 48);
            }
        }

        if (preg_match('/\bRate\s+([\d.]+)/i', $headerText, $m)) {
            $rate = (float) $m[1];
            if ($rate > 0) {
                $result['rate'] = $rate;
            }
        }

        if (preg_match('/\bComodity\s+Code\s+(\d{6,12})/i', $headerText, $m)) {
            $result['commodity_code'] = mb_trim($m[1]);
        } elseif (preg_match('/\bCommodity\s+Code\s+(\d{6,12})/i', $headerText, $m)) {
            $result['commodity_code'] = mb_trim($m[1]);
        }

        if (preg_match('/\bCommodity\s+Description\s*:\s*([^\n]+)/iu', $headerText, $m)) {
            $result['commodity_description'] = mb_trim($m[1]);
        }

        if (preg_match('/\bInvoice\s+Number\s+(\d+)/iu', $headerText, $m)) {
            $result['invoice_number'] = mb_trim($m[1]);
        }

        if (preg_match('#\bInvoice\s+Date\s+(\d{2}[.\-/]\d{2}[.\-/]\d{4})#iu', $headerText, $m)) {
            $result['invoice_date'] = $this->normalizeDate($m[1]);
        }

        if (preg_match('/\bFreight\s*\(\s*Rs\.?\s*\)\s+([\d,]+(?:\.\d+)?)/iu', $headerText, $m)) {
            $result['freight_base'] = (float) str_replace(',', '', mb_trim($m[1]));
        }

        if (preg_match('/\bTotal\s+Freight\s+(?::\s*)?(?:Rs\s*)?([\d,]+(?:\.\d+)?)/iu', $headerText, $m)) {
            $result['freight_total'] = (float) str_replace(',', '', mb_trim($m[1]));
        }

        if (! isset($result['from_station_code']) && preg_match('/From\s+Station\s*[\/]?\s*Siding[^:]*\bCode\s+([A-Za-z0-9]{2,12})/iu', $headerText, $m)) {
            $result['from_station_code'] = mb_strtoupper(mb_trim($m[1]));
        }

        if (! isset($result['to_station_code']) && preg_match('/To\s+Station\s*[\/]?\s*Siding[^:]*\bCode\s+([A-Za-z0-9]{2,12})/iu', $headerText, $m)) {
            $result['to_station_code'] = mb_strtoupper(mb_trim($m[1]));
        }

        return $result;
    }

    private function extractEtRrMultipageChargesSection(string $text): string
    {
        $start = mb_strlen($text);
        foreach (['Other Charges', 'Freight(Rs.)', 'Freight (Rs.)'] as $marker) {
            $pos = mb_stripos($text, $marker);
            if ($pos !== false && $pos < $start) {
                $start = $pos;
            }
        }

        if ($start === mb_strlen($text)) {
            return '';
        }

        $slice = mb_substr($text, $start);

        return $this->truncateEtRrMultipageChargesNarrativeTail($slice);
    }

    private function truncateEtRrMultipageChargesNarrativeTail(string $slice): string
    {
        $cut = mb_strlen($slice);
        $patterns = [
            '/\*?\s*GST\s+PARTICULARS/is',
            '/\bREMARKS\s*:/i',
            '/^ALLOCATION AND OTHER DETAILS/im',
            '/^Additional Details/im',
            '/^Net Zero by 2030/im',
            '/^Payment Mode/im',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $slice, $m, PREG_OFFSET_CAPTURE)) {
                $pos = $m[0][1];
                if ($pos >= 0 && $pos < $cut) {
                    $cut = $pos;
                }
            }
        }

        return mb_substr($slice, 0, $cut) ?: $slice;
    }

    /**
     * @return array<int, array{code: string, name: string|null, amount: float}>
     */
    private function parseEtRrMultipageChargesSection(string $chargesText): array
    {
        $charges = $this->parseChargesSection($chargesText);

        if (preg_match('/\bFreight\s*\(\s*Rs\.?\s*\)\s+([\d,]+(?:\.\d+)?)/iu', $chargesText, $m)) {
            $amount = (float) str_replace(',', '', mb_trim($m[1]));
            $hasFreight = false;
            foreach ($charges as $charge) {
                if (($charge['code'] ?? '') === 'FREIGHT') {
                    $hasFreight = true;
                    break;
                }
            }
            if (! $hasFreight && $amount > 0) {
                array_unshift($charges, [
                    'code' => 'FREIGHT',
                    'name' => 'Freight',
                    'amount' => $amount,
                ]);
            }
        }

        return $charges;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseEtRrMultipageWagonSection(string $text): array
    {
        $wagons = [];
        $seenWagonNumbers = [];

        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            $trimLine = mb_trim((string) $line);
            if ($trimLine === '' || $this->isEtRrMultipageWagonSkipLine($trimLine)) {
                continue;
            }

            $wagon = $this->parseEtRrMultipageWagonRow($trimLine);
            if ($wagon === null) {
                continue;
            }

            $wagonNumber = (string) ($wagon['wagon_number'] ?? '');
            if ($wagonNumber === '' || isset($seenWagonNumbers[$wagonNumber])) {
                continue;
            }

            $seenWagonNumbers[$wagonNumber] = true;
            $wagons[] = $wagon;
        }

        usort($wagons, static fn (array $a, array $b): int => ((int) ($a['sequence'] ?? 0)) <=> ((int) ($b['sequence'] ?? 0)));

        return $wagons;
    }

    private function isEtRrMultipageWagonSkipLine(string $line): bool
    {
        if ($this->isTotalOrSummaryRow($line)) {
            return true;
        }

        $upper = mb_strtoupper($line);
        $lower = mb_strtolower($line);

        if (str_contains($upper, 'DISCLAIMER')) {
            return true;
        }

        if (preg_match('/Page\s+\d+\s+of\s+\d+/i', $line)) {
            return true;
        }

        if (preg_match('/^SR\.\s+OWNG/is', $line)) {
            return true;
        }

        if (str_contains($upper, 'OWNG') && str_contains($upper, 'WGON') && str_contains($upper, 'NUMB')) {
            return true;
        }

        if (preg_match('/^Total\s*$/i', $line)) {
            return true;
        }

        if (str_contains($lower, 'electronically transmitted railway receipt')
            && ! preg_match('/^\s*\d+\s+[A-Z]{2,4}\s+[A-Z][A-Z0-9]+\s+\d{9,14}\s+/i', $line)) {
            return true;
        }

        if (str_contains($upper, 'TOTL') && str_contains($upper, 'NORM') && str_contains($upper, 'POL')) {
            return true;
        }

        return false;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseEtRrMultipageWagonRow(string $line): ?array
    {
        if (! preg_match('/^\s*(\d+)\s+([A-Z]{2,4})\s+([A-Z][A-Z0-9]{3,13})\s+(\d{9,14})\s+(.+)$/i', $line, $m)) {
            return null;
        }

        $seq = (int) $m[1];
        $wagonType = mb_strtoupper(mb_trim($m[3]));
        $wagonNumber = mb_trim($m[4]);
        $rest = mb_trim($m[5]);
        $numbers = $this->extractNumbersFromEtRrMultipageWagonRest($rest);

        return [
            'sequence' => $seq,
            'wagon_number' => $wagonNumber,
            'wagon_type' => $wagonType,
            'pcc_weight' => $numbers['cc'] ?? 0.0,
            'loaded_weight' => $numbers['actual'] ?? 0.0,
            'permissible_weight' => $numbers['chargeable'] ?? 0.0,
            'overload_weight' => $numbers['overload'] ?? 0.0,
            'tare_weight' => $numbers['tare'] ?? null,
            'gross_weight' => $numbers['gross'] ?? null,
        ];
    }

    /**
     * Multipage ET-RR wagon row: CC, Tare, CMDT, Gross, DIP, Actl., PERM CC, OVER×3, CHBL.
     *
     * @return array{cc?: float, tare?: float, gross?: float, actual?: float, overload?: float, chargeable?: float}
     */
    private function extractNumbersFromEtRrMultipageWagonRest(string $rest): array
    {
        $nums = [];
        if (preg_match_all('/(\d+\.\d+|\.\d+|\d+)/', $rest, $matches)) {
            foreach ($matches[1] as $v) {
                $nums[] = (float) $v;
            }
        }

        $result = [];
        $n = count($nums);
        if ($n >= 1) {
            $result['cc'] = $nums[0];
        }
        if ($n >= 2) {
            $result['tare'] = $nums[1];
        }
        if ($n >= 4) {
            $result['gross'] = $nums[3];
        }
        if ($n >= 6) {
            $result['actual'] = $nums[5];
        }
        if ($n >= 8) {
            $result['overload'] = $nums[7];
        }
        if ($n >= 11) {
            $result['chargeable'] = $nums[10];
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseExtractedTextStandardEtRr(string $text): array
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
            'rr_format' => self::RR_FORMAT_ET_RR,
            'rr_number' => $header['rr_number'],
            'fnr' => $header['fnr'] ?? null,
            'rr_date' => $rrDate,
            'rr_received_date' => $rrDate,
            'actual_weight_mt' => null,
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

    /**
     * FOIS RR Details portal print layout.
     *
     * @return array<string, mixed>
     */
    private function parseExtractedTextFoisPrinted(string $text): array
    {
        $wagonPos = mb_stripos($text, self::FOIS_WAGON_SECTION_START);
        if ($wagonPos === false) {
            throw new InvalidArgumentException('FOIS wagon section anchor not found.');
        }

        $beforeWagon = mb_substr($text, 0, $wagonPos);
        $afterMarker = mb_substr($text, $wagonPos + mb_strlen(self::FOIS_WAGON_SECTION_START));
        $headerSlice = mb_substr($beforeWagon, 0);

        $chargeStartMin = mb_strlen($headerSlice);
        foreach (['OTHR CHRG', 'FREIGHT:'] as $marker) {
            $p = mb_stripos($headerSlice, $marker);
            if ($p !== false && $p < $chargeStartMin) {
                $chargeStartMin = $p;
            }
        }

        $fieldHeader = mb_substr($headerSlice, 0, $chargeStartMin);
        $chargesSliceFromHeader = $this->truncateFoisChargesNarrativeTail(mb_substr($headerSlice, $chargeStartMin));

        $header = $this->parseFoisHeaderSection($fieldHeader);
        $charges = $this->parseChargesSection($chargesSliceFromHeader);

        if (($header['freight_total'] ?? 0) <= 0.0 && preg_match('/Freight:\s*(?:Rs\s*)?([\d,]+(?:\.\d+)?)/i', $chargesSliceFromHeader, $freightLine)) {
            $header['freight_total'] ??= (float) str_replace(',', '', $freightLine[1]);
        }

        $wagonSection = mb_substr($afterMarker, 0);
        $wagonsParsed = $this->parseFoisWagonSection($wagonSection);

        if (empty($header['rr_number'])) {
            throw new InvalidArgumentException('This does not appear to be a Railway Receipt PDF. RR number could not be found.');
        }

        $totalWeight = (float) ($header['total_weight'] ?? 0);
        if ($totalWeight === 0.0 && ! empty($wagonsParsed)) {
            $chargeableSum = $this->sumWagonChargeableWeight($wagonsParsed);
            $totalWeight = $chargeableSum > 0.0 ? $chargeableSum : (float) array_sum(array_column($wagonsParsed, 'loaded_weight'));
        }

        $wagonCount = (int) ($header['wagon_count'] ?? count($wagonsParsed));
        if ($wagonCount === 0 && ! empty($wagonsParsed)) {
            $wagonCount = count($wagonsParsed);
        }

        $wagonsForImport = [];

        foreach ($wagonsParsed as $row) {
            unset($row['chargeable_weight_pdf']);
            $wagonsForImport[] = $row;
        }

        $rrDate = $header['rr_received_date'] ?? null;
        $freightTotalResolved = (float) ($header['freight_total'] ?? 0);
        if ($freightTotalResolved <= 0.0 && $charges !== []) {
            foreach ($charges as $chargeLine) {
                if (($chargeLine['code'] ?? '') === 'FREIGHT' && (($chargeLine['amount'] ?? 0) > 0)) {
                    $freightTotalResolved = (float) $chargeLine['amount'];
                    break;
                }
            }
        }

        return [
            'rr_format' => self::RR_FORMAT_FOIS_PRINTED,
            'rr_number' => $header['rr_number'],
            'fnr' => $header['fnr'] ?? null,
            'rr_date' => $rrDate,
            'rr_received_date' => $rrDate,
            'actual_weight_mt' => isset($header['actual_weight_mt']) ? (float) $header['actual_weight_mt'] : null,
            'rr_weight_mt' => $totalWeight,
            'distance_km' => (float) ($header['distance_km'] ?? 0),
            'total_weight' => $totalWeight,
            'wagon_count' => $wagonCount,
            'freight_total' => $freightTotalResolved,
            'from_station_code' => $header['from_station_code'] ?? null,
            'to_station_code' => $header['to_station_code'] ?? null,
            'commodity_code' => $header['commodity_code'] ?? null,
            'commodity_description' => $header['commodity_description'] ?? null,
            'invoice_number' => $header['invoice_number'] ?? null,
            'invoice_date' => $header['invoice_date'] ?? null,
            'rate' => isset($header['rate']) ? (float) $header['rate'] : null,
            'class' => $header['class'] ?? null,
            'wagons' => $wagonsForImport,
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

    /**
     * @return array<string, mixed>
     */
    private function parseFoisHeaderSection(string $headerText): array
    {
        $result = [];

        if (preg_match('/\bRR\s*NO\.?\s*[:\t ]*\s*(\d{8,12})/i', $headerText, $m)) {
            $result['rr_number'] = mb_trim($m[1]);
        }

        if (preg_match('/\bRR\s*DATE\s*[:\t ]*\s*(\d{2}[-\/]\d{2}[-\/]\d{4})/i', $headerText, $m)) {
            $result['rr_received_date'] = $this->normalizeDate($m[1]);
        }

        if (preg_match('/\bFNR\s*[:\t ]*\s*(\d{8,14})/i', $headerText, $m)) {
            $result['fnr'] = mb_trim($m[1]);
        }

        if (preg_match('/\bACTL\s+WGHT\s*[:\t ]*\s*([\d,]+(?:\.\d+)?)/i', $headerText, $m)) {
            $result['actual_weight_mt'] = (float) str_replace(',', '', mb_trim($m[1]));
        }

        if (preg_match('/\bTOTAL\s+CHRG\s+WT\.?\s*[:\t ]*\s*([\d,]+(?:\.\d+)?)/i', $headerText, $m)) {
            $result['total_weight'] = (float) str_replace(',', '', mb_trim($m[1]));
        } elseif (preg_match('/\bCHBL\s+WGHT\s+AT\s+NORM(?:\.?\s+RATE)?\s*[:\t ]*\s*([\d,]+(?:\.\d+)?)/i', $headerText, $m)) {
            $result['total_weight'] = (float) str_replace(',', '', mb_trim($m[1]));
        }

        if (preg_match('/\bCHRG\s+DIST\s*[:\t ]*\s*([\d,]+(?:\.\d+)?)/i', $headerText, $m)) {
            $result['distance_km'] = (float) str_replace(',', '', mb_trim($m[1]));
        }

        if (preg_match('/\bWGON\s+FRWH\s*[:\t ]*\s*(\d+)/i', $headerText, $m)) {
            $result['wagon_count'] = (int) $m[1];
        }

        $fromPos = mb_stripos($headerText, 'FROM STATION');
        $toPos = mb_stripos($headerText, 'TO STATION');
        if ($fromPos !== false && $toPos !== false && $toPos > $fromPos) {
            $fromBlock = mb_substr($headerText, $fromPos, $toPos - $fromPos);
            if (preg_match('/\bCODE\s*:\s*[ \t]*([A-Za-z0-9]{2,12})\b/u', $fromBlock, $m)) {
                $result['from_station_code'] = mb_trim($m[1]);
            }
            if (! isset($result['from_station_code']) && preg_match('/FROM\s+STATION\s*\/\s*SIDING\s*:[^\r\n]*\b([A-Za-z]{2,8})\s+\d{6,}/iu', $fromBlock, $m)) {
                $result['from_station_code'] = mb_strtoupper(mb_trim($m[1]));
            }
        }

        if (! isset($result['to_station_code']) && $toPos !== false) {
            $toBlock = mb_substr($headerText, $toPos, 2800);
            if (preg_match('/\bCODE\s*:\s*[ \t]*([A-Za-z0-9]{2,12})\b/u', $toBlock, $m)) {
                $result['to_station_code'] = mb_trim($m[1]);
            }
        }

        if (preg_match('/\bCMDT\s+CODE\s*[:\t ]*\s*(\d{6,12})/i', $headerText, $m)) {
            $result['commodity_code'] = mb_trim($m[1]);
        }

        if (preg_match('/\bCOMMODITY\s+DESCRIPTION\s*:\s*([^\n]+)/iu', $headerText, $m)) {
            $result['commodity_description'] = mb_trim($m[1]);
        }

        if (preg_match('/\bINVC\s+NO\.?\s*[:\t ]*\s*(\d+)/iu', $headerText, $m)) {
            $result['invoice_number'] = mb_trim($m[1]);
        }

        if (preg_match('/\bINVC\s+DATE\s*[:\t ]*\s*(\d{2}[-\/]\d{2}[-\/]\d{4})/iu', $headerText, $m)) {
            $result['invoice_date'] = $this->normalizeDate($m[1]);
        }

        foreach (preg_split('/\r\n|\r|\n/', $headerText) as $line) {
            if (! preg_match('/RISK\s+RATE\s*:/i', $line)) {
                continue;
            }
            if (preg_match('/\bCLASS\s*:\s*([^\t\n\r]+)/i', $line, $cm)) {
                $cls = mb_trim(str_replace(',', '', preg_replace('/\s+/', ' ', $cm[1])));
                $clsParts = preg_split('/\s+RATE\s*:/iu', $cls, 2);
                $cls = mb_trim(($clsParts === false) ? $cls : $clsParts[0]);
                if ($cls !== '' && $cls !== '-') {
                    $result['class'] = mb_trim(mb_substr($cls, 0, 48));
                }
            }
            if (preg_match_all('/\bRATE\s*:\s*([\d.]+)/iu', $line, $rms) && isset($rms[1][count($rms[1]) - 1])) {
                $result['rate'] = (float) $rms[1][count($rms[1]) - 1];
                if ($result['rate'] <= 0) {
                    unset($result['rate']);
                }
            }
            break;
        }

        if (preg_match('/\bFREIGHT\s*:\s*[ \t]*(?:Rs\s*)?([\d,]+(?:\.\d+)?)/iu', $headerText, $m)) {
            $result['freight_total'] ??= (float) str_replace(',', '', mb_trim($m[1]));
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseFoisWagonSection(string $wagonSection): array
    {
        $wagons = [];
        $lines = preg_split('/\r\n|\r|\n/', $wagonSection);

        foreach ($lines as $line) {
            $line = preg_replace('/\[[\d]+\]/', '', (string) $line);
            if (mb_stripos((string) $line, 'fois.indianrail.gov.in') !== false) {
                continue;
            }

            $trimLine = mb_trim((string) $line);
            if ($trimLine === '' || preg_match('#^--+[\s\d/]+-+$#', $trimLine)) {
                continue;
            }

            if ($this->isLikelyFoisWagonColumnHeaderRow($trimLine)) {
                continue;
            }

            if ($this->isTotalOrSummaryRow($trimLine)) {
                continue;
            }

            if (preg_match('/^\s*\d{1,2}\/\d/', $trimLine)) {
                continue;
            }

            $wagon = $this->parseFoisWagonRow($trimLine);
            if ($wagon !== null) {
                $wagons[] = $wagon;
            }
        }

        return $wagons;
    }

    private function isLikelyFoisWagonColumnHeaderRow(string $line): bool
    {
        $upper = mb_strtoupper($line);

        return str_contains($upper, 'SR.NO')
            || str_contains($upper, 'OWNG')
            || str_contains($upper, 'PARTY NUMB')
            || (str_contains($upper, 'CMDT')
                && str_contains($upper, 'GROSS')
                && str_contains($upper, 'CHBL'));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseFoisWagonRow(string $line): ?array
    {
        if (! preg_match('/^\s*(\d+)\s+([A-Z]{2,4})\s+([A-Z][A-Z0-9]{3,13})\s+(\d{9,14})\s+(.+)$/i', $line, $m)) {
            return null;
        }

        $seq = (int) $m[1];
        $wagonType = mb_strtoupper(mb_trim($m[3]));
        $wagonNumber = mb_trim($m[4]);

        $rest = mb_trim($m[5]);
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
            'tare_weight' => $numbers['tare'] ?? null,
            'gross_weight' => $numbers['gross'] ?? null,
            'chargeable_weight_pdf' => $numbers['chargeable'] ?? null,
        ];
    }

    /**
     * Sum per-wagon CHBL/chargeable column when present on parsed FOIS rows (for header total_weight fallback).
     */
    private function sumWagonChargeableWeight(array $wagons): float
    {
        $sum = 0.0;
        foreach ($wagons as $wagon) {
            $c = $wagon['chargeable_weight_pdf'] ?? null;
            if ($c !== null && is_numeric($c)) {
                $sum += (float) $c;
            }
        }

        return round($sum, 6);
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
        if ($totalFreightPos === false) {
            $totalFreightPos = mb_stripos($text, 'TOTAL FREIGHT:', $start);
        }
        if ($totalFreightPos !== false) {
            $end = $totalFreightPos + 220;
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
     * Some receipts omit a dedicated commodity token before gross (compact layout); others insert a numeric commodity code (large int),
     * shifting gross one token right — handled in {@see extractNumbersFromWagonRest}.
     * Wagon number may be split: first line has 7-8 digits, continuation has 2-3 digits.
     *
     * @return array{sequence: int, wagon_number: string, wagon_type: string|null, pcc_weight: float, loaded_weight: float, permissible_weight: float, overload_weight: float, tare_weight: float|null, gross_weight: float|null}|null
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
            'tare_weight' => $numbers['tare'] ?? null,
            'gross_weight' => $numbers['gross'] ?? null,
        ];
    }

    /**
     * Numeric token positions after wagon number:
     * - Compact ET-RR: CC, Tare, article count (often 0), Gross, …, Actual at {@see extractNumbersFromWagonRest} indices for permissibles.
     * - With commodity column: CC, Tare, article count, **commodity code** (large int), Gross, … — gross shifts one slot after code.
     */
    private function wagonRestHasCommodityCodeBeforeGross(array $nums): bool
    {
        if (count($nums) < 5) {
            return false;
        }

        $candidate = $nums[3];

        if ($candidate < 100_000) {
            return false;
        }

        return abs($candidate - round($candidate)) < 0.000_001;
    }

    /**
     * Tokenize numeric cells after wagon number. Layout extraction may omit a leading zero (e.g. {@code .85}); order alternatives so {@code digits.digits} is one token.
     *
     * @return array{cc?: float, tare?: float, gross?: float, actual?: float, permissible?: float, overload?: float, chargeable?: float}
     */
    private function extractNumbersFromWagonRest(string $rest): array
    {
        $nums = [];
        if (preg_match_all('/(\d+\.\d+|\.\d+|\d+)/', $rest, $matches)) {
            foreach ($matches[1] as $v) {
                $nums[] = (float) $v;
            }
        }

        $result = [];
        $n = count($nums);
        if ($n >= 1) {
            $result['cc'] = $nums[0];
        }
        if ($n >= 2) {
            $result['tare'] = $nums[1];
        }
        if ($this->wagonRestHasCommodityCodeBeforeGross($nums)) {
            if ($n >= 5) {
                $result['gross'] = $nums[4];
            }
        } elseif ($n >= 4) {
            $result['gross'] = $nums[3];
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

        if (preg_match('/Freight:\s*(?:Rs\s*)?([\d,]+(?:\.\d+)?)/i', $chargesText, $m) && ! isset($seen['FREIGHT'])) {
            $charges[] = [
                'code' => 'FREIGHT',
                'name' => 'Freight',
                'amount' => (float) str_replace(',', '', $m[1]),
            ];
            $seen['FREIGHT'] = true;
        }

        if (preg_match('/Total\s+Freight:\s*(?:Rs\s*)?([\d,]+(?:\.\d+)?)/i', $chargesText, $m)) {
            $amount = (float) str_replace(',', '', $m[1]);
            if (! isset($seen['FREIGHT'])) {
                $charges[] = ['code' => 'FREIGHT', 'name' => 'Freight', 'amount' => $amount];
                $seen['FREIGHT'] = true;
            }
        }

        if (preg_match('/Total\s+Freight\s+(?::\s*)?(?:Rs\s*)?([\d,]+(?:\.\d+)?)/i', $chargesText, $m)) {
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

        return $charges;
    }

    /**
     * FOIS prints real charge lines (FREIGHT, POL1, *GST) then long narrative (*GST PARTICULARS, REMARKS).
     * Do not feed that prose into {@see parseChargesSection} or word+number patterns match "FOR 19", "RC 19", "WT 3699", etc.
     */
    private function truncateFoisChargesNarrativeTail(string $slice): string
    {
        $cut = mb_strlen($slice);
        $patterns = [
            '/\*?\s*GST\s+PARTICULARS/is',
            '/\bREMARKS\s*:/i',
            '/^ALLOCATION AND OTHER DETAILS/im',
            '/fois\.indianrail\.gov\.in/i',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $slice, $m, PREG_OFFSET_CAPTURE)) {
                $pos = $m[0][1];
                if ($pos >= 0 && $pos < $cut) {
                    $cut = $pos;
                }
            }
        }

        return mb_substr($slice, 0, $cut) ?: $slice;
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
        if (preg_match('/^(\d{2})[-\/.](\d{2})[-\/.](\d{4})$/', $date, $m)) {
            return $m[3].'-'.$m[2].'-'.$m[1];
        }
        if (preg_match('/^(\d{4})[-\/.](\d{2})[-\/.](\d{2})$/', $date, $m)) {
            return $m[1].'-'.$m[2].'-'.$m[3];
        }

        return $date;
    }
}
