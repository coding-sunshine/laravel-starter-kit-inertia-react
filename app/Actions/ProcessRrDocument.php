<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Rake;
use App\Models\RrDocument;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Throwable;

use function Laravel\Ai\agent;

/**
 * ProcessRrDocument - Extract and process Railway Receipt documents
 *
 * Uses Laravel AI SDK to:
 * - Extract text/data from RR document (PDF, image)
 * - Perform OCR if needed
 * - Parse structured data (RR number, weight, wagon count, etc.)
 * - Create RrDocument record with extracted data
 */
final readonly class ProcessRrDocument
{
    /**
     * Process an uploaded RR document
     *
     * @param array{
     *     rake_id: int,
     *     document: UploadedFile,
     *     rr_number?: string,
     * } $data
     */
    public function handle(array $data, int $userId): RrDocument
    {
        return DB::transaction(function () use ($data, $userId): RrDocument {
            $rake = Rake::query()->findOrFail($data['rake_id']);
            $file = $data['document'];

            // Store document temporarily
            $storedPath = $file->store('rr-documents', 'local');

            try {
                // Extract data from RR document using Laravel AI SDK
                $extractedData = $this->extractRrData($storedPath, $file->getMimeType());

                $rrReceivedDate = isset($extractedData['receipt_date']) && $extractedData['receipt_date']
                    ? $extractedData['receipt_date']
                    : now()->toDateTimeString();
                if (is_string($rrReceivedDate)) {
                    try {
                        $rrReceivedDate = \Illuminate\Support\Facades\Date::parse($rrReceivedDate);
                    } catch (Throwable) {
                        $rrReceivedDate = now();
                    }
                }

                $rrDocument = RrDocument::query()->create([
                    'rake_id' => $rake->id,
                    'rr_number' => $data['rr_number'] ?? $extractedData['rr_number'] ?? 'RR-'.now()->timestamp,
                    'rr_received_date' => $rrReceivedDate,
                    'rr_weight_mt' => $extractedData['rr_weight_mt'] ?? null,
                    'fnr' => $extractedData['fnr'] ?? null,
                    'from_station_code' => $extractedData['from_station_code'] ?? null,
                    'to_station_code' => $extractedData['to_station_code'] ?? null,
                    'freight_total' => isset($extractedData['freight_total']) ? (float) $extractedData['freight_total'] : null,
                    'rr_details' => $extractedData,
                    'document_status' => 'received',
                    'has_discrepancy' => false,
                    'created_by' => $userId,
                ]);

                return $rrDocument->refresh();
            } catch (Exception $e) {
                // Clean up on failure
                Storage::disk('local')->delete($storedPath);

                throw new InvalidArgumentException("Failed to process RR document: {$e->getMessage()}", $e->getCode(), $e);
            }
        });
    }

    /**
     * Extract RR data from an uploaded PDF/image. Returns extracted fields or null if AI is not
     * configured or extraction fails. Use when a PDF is uploaded to auto-fill RR details.
     *
     * @return array{rr_number?: string, rr_weight_mt?: float, rr_received_date?: string, ...}|null
     */
    public function extractFromUpload(UploadedFile $file): ?array
    {
        if (! $this->isAiConfigured()) {
            return null;
        }

        $storedPath = $file->store('rr-documents-temp', 'local');
        try {
            $extracted = $this->extractRrData($storedPath, $file->getMimeType());
            $out = [
                'rr_number' => $extracted['rr_number'] ?? null,
                'rr_weight_mt' => isset($extracted['rr_weight_mt']) ? (float) $extracted['rr_weight_mt'] : null,
                'fnr' => $extracted['fnr'] ?? null,
                'from_station_code' => $extracted['from_station_code'] ?? null,
                'to_station_code' => $extracted['to_station_code'] ?? null,
                'freight_total' => isset($extracted['freight_total']) ? (float) $extracted['freight_total'] : null,
                'charges' => $extracted['charges'] ?? null,
                'wagons' => $extracted['wagons'] ?? null,
                'rr_details' => $extracted,
            ];
            if (isset($extracted['receipt_date']) && $extracted['receipt_date']) {
                $out['rr_received_date'] = $extracted['receipt_date'];
            }

            return array_filter($out, fn ($v, $k): bool => $k === 'rr_details' || ($v !== null && $v !== ''), ARRAY_FILTER_USE_BOTH);
        } catch (Exception $e) {
            Log::warning('RR document extraction failed', ['error' => $e->getMessage()]);

            return null;
        } finally {
            Storage::disk('local')->delete($storedPath);
        }
    }

    /**
     * Whether AI is configured for document extraction (e.g. OpenAI/Anthropic key set).
     */
    public function isAiConfigured(): bool
    {
        $default = config('ai.default');
        if (! $default) {
            return false;
        }
        $key = config("ai.providers.{$default}.key");

        return ! empty($key);
    }

    /**
     * Get pending RR documents for a siding
     */
    public function getPendingRrDocuments(int $sidingId): \Illuminate\Database\Eloquent\Collection
    {
        return RrDocument::query()->whereHas('rake', function ($query) use ($sidingId): void {
            $query->where('siding_id', $sidingId);
        })
            ->where('document_status', 'received')
            ->with('rake.siding')
            ->get();
    }

    /**
     * Get RR documents with discrepancies
     */
    public function getDocumentsWithDiscrepancies(int $sidingId): \Illuminate\Database\Eloquent\Collection
    {
        return RrDocument::query()->whereHas('rake', function ($query) use ($sidingId): void {
            $query->where('siding_id', $sidingId);
        })
            ->where('has_discrepancy', true)
            ->with('rake.siding')
            ->get();
    }

    /**
     * Extract structured data from RR document using AI
     */
    private function extractRrData(string $filePath, string $mimeType): array
    {
        // Read file content for processing
        $fileContent = Storage::disk('local')->get($filePath);
        $base64Content = base64_encode((string) $fileContent);

        // Determine media type for AI processing
        $mediaType = $this->getMediaType($mimeType);

        // Use Laravel AI SDK with structured output to extract RR data
        $response = agent(
            instructions: <<<'PROMPT'
            You are an expert in railway documentation. Extract key information from Indian Railway Receipt (eT-RR) documents.

            Extract and return a single JSON object with:

            Header fields:
            - rr_number (RR No)
            - fnr (F/Note Number or FNR)
            - rr_weight_mt (Actual Weight or Chargeable Weight in MT - use the main weight total)
            - wagon_count (number of wagons)
            - receipt_date (RR Date in ISO or Y-m-d format)
            - from_station_code (Station From code, e.g. BMGK, DUMK)
            - to_station_code (Station To code, e.g. PSPM, KPPS)
            - freight_total (total freight amount in rupees, number)
            - charges: object with keys POL1, OTC, GST (amounts as numbers; use 0 if absent)

            Wagon table (if present): wagons as array of objects, each with:
            - wagon_number, wagon_type, cc_mt, tare_mt, gross_mt, actual_mt, permissible_mt, over_weight_mt, chargeable_mt
            (use null for missing numeric fields; keep wagon_number and wagon_type as strings)

            Also include if found: coal_grade, origin_siding, destination_siding.

            Return only valid JSON. Use null for any field you cannot find.
            PROMPT
        )->prompt(
            attachments: [
                [
                    'type' => 'image',
                    'media_type' => $mediaType,
                    'data' => $base64Content,
                ],
            ],
            message: 'Extract all header and wagon-table data from this Railway Receipt. Return one JSON object.'
        );

        // Parse the response
        try {
            $text = $response->text;

            // Try to extract JSON from response (handle nested braces for wagons array)
            if (str_contains($text, '{')) {
                $jsonStart = mb_strpos($text, '{');
                $depth = 0;
                $jsonEnd = $jsonStart;
                $len = mb_strlen($text);
                for ($i = $jsonStart; $i < $len; $i++) {
                    $ch = mb_substr($text, $i, 1);
                    if ($ch === '{') {
                        $depth++;
                    } elseif ($ch === '}') {
                        $depth--;
                        if ($depth === 0) {
                            $jsonEnd = $i;
                            break;
                        }
                    }
                }
                $jsonStr = mb_substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);
                $parsed = json_decode($jsonStr, true);
                if (is_array($parsed)) {
                    $wagons = $parsed['wagons'] ?? null;
                    if (is_array($wagons)) {
                        $wagons = array_values(array_map(function ($w): ?array {
                            if (! is_array($w)) {
                                return null;
                            }

                            return [
                                'wagon_number' => $w['wagon_number'] ?? $w['wagonNumber'] ?? null,
                                'wagon_type' => $w['wagon_type'] ?? $w['wagonType'] ?? null,
                                'cc_mt' => isset($w['cc_mt']) ? (float) $w['cc_mt'] : (isset($w['ccMt']) ? (float) $w['ccMt'] : null),
                                'tare_mt' => isset($w['tare_mt']) ? (float) $w['tare_mt'] : null,
                                'gross_mt' => isset($w['gross_mt']) ? (float) $w['gross_mt'] : null,
                                'actual_mt' => isset($w['actual_mt']) ? (float) $w['actual_mt'] : null,
                                'permissible_mt' => isset($w['permissible_mt']) ? (float) $w['permissible_mt'] : null,
                                'over_weight_mt' => isset($w['over_weight_mt']) ? (float) $w['over_weight_mt'] : null,
                                'chargeable_mt' => isset($w['chargeable_mt']) ? (float) $w['chargeable_mt'] : null,
                            ];
                        }, $wagons));
                        $wagons = array_filter($wagons);
                    }
                    $charges = $parsed['charges'] ?? null;
                    if (is_array($charges)) {
                        $charges = array_filter([
                            'POL1' => isset($charges['POL1']) ? (float) $charges['POL1'] : null,
                            'OTC' => isset($charges['OTC']) ? (float) $charges['OTC'] : null,
                            'GST' => isset($charges['GST']) ? (float) $charges['GST'] : null,
                        ], fn (?float $v): bool => $v !== null);
                    }

                    return array_filter([
                        'rr_number' => $parsed['rr_number'] ?? $parsed['RR_number'] ?? null,
                        'rr_weight_mt' => is_numeric($parsed['rr_weight_mt'] ?? $parsed['weight'] ?? $parsed['total_weight_mt'] ?? null)
                            ? (float) ($parsed['rr_weight_mt'] ?? $parsed['weight'] ?? $parsed['total_weight_mt'])
                            : null,
                        'wagon_count' => is_numeric($parsed['wagon_count'] ?? $parsed['wagons'] ?? null)
                            ? (int) ($parsed['wagon_count'] ?? $parsed['wagons'])
                            : null,
                        'fnr' => $parsed['fnr'] ?? null,
                        'receipt_date' => $parsed['receipt_date'] ?? null,
                        'from_station_code' => $parsed['from_station_code'] ?? null,
                        'to_station_code' => $parsed['to_station_code'] ?? null,
                        'freight_total' => is_numeric($parsed['freight_total'] ?? null) ? (float) $parsed['freight_total'] : null,
                        'charges' => $charges,
                        'wagons' => empty($wagons) ? null : $wagons,
                        'coal_grade' => $parsed['coal_grade'] ?? $parsed['quality'] ?? null,
                        'origin_siding' => $parsed['origin_siding'] ?? $parsed['sender'] ?? null,
                        'destination_siding' => $parsed['destination_siding'] ?? $parsed['receiver'] ?? null,
                        'raw_extraction' => $text,
                    ], fn ($v): bool => $v !== null && $v !== '');
                }
            }

            // Fallback: return raw text if JSON not found
            return [
                'raw_extraction' => $text,
                'parsing_note' => 'Could not parse structured JSON from response',
            ];
        } catch (Exception $e) {
            return [
                'raw_extraction' => $response->text ?? '',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Map MIME type to media type for AI attachments
     */
    private function getMediaType(string $mimeType): string
    {
        return match ($mimeType) {
            'application/pdf' => 'application/pdf',
            'image/jpeg', 'image/jpg' => 'image/jpeg',
            'image/png' => 'image/png',
            'image/webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }
}
