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
    public function __construct() {}

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
            $rake = Rake::findOrFail($data['rake_id']);
            $file = $data['document'];

            // Store document temporarily
            $storedPath = $file->store('rr-documents', 'local');

            try {
                // Extract data from RR document using Laravel AI SDK
                $extractedData = $this->extractRrData($storedPath, $file->getMimeType());

                // Create RrDocument record
                $rrDocument = RrDocument::create([
                    'rake_id' => $rake->id,
                    'rr_number' => $data['rr_number'] ?? $extractedData['rr_number'] ?? 'RR-'.now()->timestamp,
                    'rr_received_date' => now(),
                    'rr_weight_mt' => $extractedData['rr_weight_mt'] ?? null,
                    'rr_details' => json_encode($extractedData),
                    'document_status' => 'received',
                    'has_discrepancy' => false,
                    'created_by' => $userId,
                ]);

                return $rrDocument->refresh();
            } catch (Exception $e) {
                // Clean up on failure
                Storage::disk('local')->delete($storedPath);

                throw new InvalidArgumentException("Failed to process RR document: {$e->getMessage()}");
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
            ];
            if (isset($extracted['receipt_date']) && $extracted['receipt_date']) {
                $out['rr_received_date'] = $extracted['receipt_date'];
            }

            return array_filter($out, fn ($v) => $v !== null && $v !== '');
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
        return RrDocument::whereHas('rake', function ($query) use ($sidingId) {
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
        return RrDocument::whereHas('rake', function ($query) use ($sidingId) {
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
        $base64Content = base64_encode($fileContent);

        // Determine media type for AI processing
        $mediaType = $this->getMediaType($mimeType);

        // Use Laravel AI SDK with structured output to extract RR data
        $response = agent(
            instructions: <<<'PROMPT'
            You are an expert in railway documentation. Extract key information from Railway Receipt (RR) documents.

            Extract and return the following fields:
            - RR number/reference
            - Total weight in MT (metric tonnes)
            - Number of wagons
            - Coal quality/grade
            - Sender/origin siding
            - Receiver/destination siding
            - Receipt date/time

            Return null for any field you cannot find. Focus on accuracy.
            PROMPT
        )->prompt(
            message: 'Please extract information from this Railway Receipt document. Return the data as JSON.',
            attachments: [
                [
                    'type' => 'image',
                    'media_type' => $mediaType,
                    'data' => $base64Content,
                ],
            ]
        );

        // Parse the response
        try {
            $text = $response->text;

            // Try to extract JSON from response
            if (str_contains($text, '{')) {
                $jsonStart = mb_strpos($text, '{');
                $jsonEnd = mb_strrpos($text, '}');
                if ($jsonStart !== false && $jsonEnd !== false) {
                    $jsonStr = mb_substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);
                    $parsed = json_decode($jsonStr, true);

                    return [
                        'rr_number' => $parsed['rr_number'] ?? $parsed['RR_number'] ?? null,
                        'rr_weight_mt' => is_numeric($parsed['weight'] ?? $parsed['total_weight_mt'] ?? null)
                            ? (float) ($parsed['weight'] ?? $parsed['total_weight_mt'])
                            : null,
                        'wagon_count' => is_numeric($parsed['wagon_count'] ?? $parsed['wagons'] ?? null)
                            ? (int) ($parsed['wagon_count'] ?? $parsed['wagons'])
                            : null,
                        'coal_grade' => $parsed['coal_grade'] ?? $parsed['quality'] ?? null,
                        'origin_siding' => $parsed['origin_siding'] ?? $parsed['sender'] ?? null,
                        'destination_siding' => $parsed['destination_siding'] ?? $parsed['receiver'] ?? null,
                        'receipt_date' => $parsed['receipt_date'] ?? null,
                        'raw_extraction' => $text,
                    ];
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
