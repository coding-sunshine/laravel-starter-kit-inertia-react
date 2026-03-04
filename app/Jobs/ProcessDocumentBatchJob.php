<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Ai\Tools\DocumentProcessor;
use App\Models\BrochureProcessing;
use App\Services\TenantContext;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ProcessDocumentBatchJob implements ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $brochureProcessingId,
        public readonly int $organizationId,
        public readonly int $userId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        try {
            // Set the tenant context
            TenantContext::setId($this->organizationId);

            $processing = BrochureProcessing::find($this->brochureProcessingId);

            if (! $processing) {
                Log::error('BrochureProcessing not found', ['id' => $this->brochureProcessingId]);

                return;
            }

            // Update status to processing
            $processing->update([
                'queue_status' => 'processing',
                'processing_started_at' => now(),
            ]);

            // Check if file exists
            if (! Storage::exists($processing->file_path)) {
                throw new Exception("File not found: {$processing->file_path}");
            }

            // Extract content and process with AI
            $documentProcessor = new DocumentProcessor();
            $extractedData = $this->processDocument($processing, $documentProcessor);

            // Update the processing record with extracted data
            DB::transaction(function () use ($processing, $extractedData) {
                $processing->update([
                    'extracted_data' => $extractedData,
                    'status' => 'pending_approval',
                    'queue_status' => 'completed',
                    'processing_completed_at' => now(),
                ]);
            });

            Log::info('Document processing completed', [
                'processing_id' => $processing->id,
                'batch_id' => $processing->batch_id,
            ]);

        } catch (Throwable $e) {
            Log::error('Document processing failed', [
                'processing_id' => $this->brochureProcessingId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update status to failed
            BrochureProcessing::where('id', $this->brochureProcessingId)
                ->update([
                    'queue_status' => 'failed',
                    'processing_completed_at' => now(),
                    'admin_notes' => 'Processing failed: '.$e->getMessage(),
                ]);

            throw $e;
        }
    }

    private function processDocument(BrochureProcessing $processing, DocumentProcessor $documentProcessor): array
    {
        $filePath = $processing->file_path;
        $mimeType = Storage::mimeType($filePath) ?? 'unknown';
        $fileExtension = mb_strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));
        $fileSize = Storage::size($filePath);

        // Extract content based on file type
        $documentContent = $this->extractContentFromFile($filePath, $mimeType, $fileExtension);

        if (empty($documentContent)) {
            throw new Exception('Could not extract readable content from file');
        }

        // Auto-detect processing type if not specified
        $processingType = $processing->type === 'auto'
            ? $this->detectContentType($documentContent)
            : $processing->type;

        // Use AI to extract structured data
        $extractedData = $this->extractDataWithAi($documentContent, $processingType, $mimeType, $fileExtension);

        return array_merge($extractedData, [
            'original_file_type' => $mimeType,
            'file_extension' => $fileExtension,
            'file_size' => $fileSize,
        ]);
    }

    private function extractContentFromFile(string $filePath, string $mimeType, string $extension): string
    {
        // Handle different file types
        if (str_starts_with($mimeType, 'image/')) {
            return $this->extractFromImage($filePath);
        }

        if ($mimeType === 'application/pdf' || $extension === 'pdf') {
            return $this->extractFromPdf($filePath);
        }

        if (str_starts_with($mimeType, 'text/') || in_array($extension, ['txt', 'csv'])) {
            return Storage::get($filePath);
        }

        try {
            $content = Storage::get($filePath);
            if ($content !== null && mb_check_encoding($content, 'UTF-8')) {
                return $content;
            }
        } catch (Throwable) {
        }

        return 'File uploaded: '.basename($filePath).' (Binary file - content extraction not available)';
    }

    private function extractFromImage(string $filePath): string
    {
        try {
            $absolutePath = Storage::path($filePath);
            $fileName = basename($absolutePath);
            $fileSize = Storage::size($filePath);

            return "Image file '{$fileName}' uploaded. ".
                   'This appears to be a property document image. '.
                   'The image likely contains property information such as floor plans, brochures, or property listings.';
        } catch (Exception $e) {
            throw new Exception('Error processing image: '.$e->getMessage());
        }
    }

    private function extractFromPdf(string $filePath): string
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $absolutePath = Storage::path($filePath);

            if (! file_exists($absolutePath)) {
                throw new Exception("PDF file not found at path: {$absolutePath}");
            }

            $pdf = $parser->parseFile($absolutePath);
            $text = $pdf->getText();

            if (empty(mb_trim($text))) {
                throw new Exception('PDF document appears to be image-based or encrypted. Unable to extract text content.');
            }

            // Clean up the extracted text
            return preg_replace('/\s+/', ' ', mb_trim($text));
        } catch (Exception $e) {
            throw new Exception('Error extracting PDF content: '.$e->getMessage());
        }
    }

    private function detectContentType(string $content): string
    {
        $content = mb_strtolower($content);

        // Look for lot-specific keywords
        $lotKeywords = ['lot', 'bedroom', 'bathroom', 'sqm', 'square', 'land size'];
        $lotMatches = 0;
        foreach ($lotKeywords as $keyword) {
            if (str_contains($content, $keyword)) {
                $lotMatches++;
            }
        }

        // Look for project-specific keywords
        $projectKeywords = ['project', 'development', 'estate', 'stage', 'total lots', 'developer'];
        $projectMatches = 0;
        foreach ($projectKeywords as $keyword) {
            if (str_contains($content, $keyword)) {
                $projectMatches++;
            }
        }

        return $projectMatches >= $lotMatches ? 'project' : 'lot';
    }

    private function extractDataWithAi(string $documentContent, string $type, string $mimeType, string $extension): array
    {
        $prompt = $this->getExtractionPrompt($type, $mimeType, $extension);

        try {
            $prismService = resolve(\App\Services\PrismService::class);
            $response = $prismService->text()
                ->withSystemPrompt($prompt)
                ->withPrompt("Extract information from this document content:\n\nFile Type: {$mimeType}\nExtension: {$extension}\n\nContent:\n{$documentContent}")
                ->asText();

            return $this->parseAiResponse($response->text, $type);
        } catch (Throwable $e) {
            return [
                'type' => $type,
                'raw_content' => mb_substr($documentContent, 0, 1000),
                'confidence' => 'Low',
                'extraction_error' => "AI processing failed: {$e->getMessage()}",
                'fallback_extraction' => true,
            ];
        }
    }

    private function getExtractionPrompt(string $type, string $mimeType, string $extension): string
    {
        $basePrompt = 'You are an expert real estate data extraction AI. Extract information from various document types including PDFs, images, Word docs, and text files.';

        if ($type === 'project') {
            return $basePrompt.' Extract project information and return a JSON object with these fields:
            {
                "title": "Project name",
                "estate": "Estate name",
                "stage": "Development stage",
                "description": "Project description",
                "developer": "Developer name",
                "projecttype": "Project type (residential, commercial, etc.)",
                "total_lots": "Number of lots (integer)",
                "min_price": "Minimum price (numeric)",
                "max_price": "Maximum price (numeric)",
                "location": "Project location",
                "features": ["List of key features"],
                "confidence": "High/Medium/Low - your confidence in extraction",
                "source_type": "Type of document processed"
            }
            Only extract information explicitly visible/mentioned. Use null for missing values.';
        }

        return $basePrompt.' Extract lot/property information and return a JSON object with these fields:
            {
                "title": "Lot number/name",
                "price": "Lot price (numeric)",
                "land_price": "Land component price (numeric)",
                "stage": "Development stage",
                "bedrooms": "Number of bedrooms (integer)",
                "bathrooms": "Number of bathrooms (integer)",
                "land_size": "Land size in sqm",
                "project_title": "Associated project name",
                "address": "Property address",
                "features": ["List of lot features"],
                "confidence": "High/Medium/Low - your confidence in extraction",
                "source_type": "Type of document processed"
            }
            Only extract information explicitly visible/mentioned. Use null for missing values.';

    }

    private function parseAiResponse(string $response, string $type): array
    {
        // Try to extract JSON from the AI response
        $jsonStart = mb_strpos($response, '{');
        $jsonEnd = mb_strrpos($response, '}');

        if ($jsonStart !== false && $jsonEnd !== false) {
            $jsonString = mb_substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
            $parsed = json_decode($jsonString, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $parsed;
            }
        }

        // Fallback: create a basic structure with the raw response
        return [
            'raw_response' => $response,
            'type' => $type,
            'confidence' => 'Low',
            'extraction_error' => 'Could not parse structured data from AI response',
            'fallback_data' => true,
        ];
    }
}
