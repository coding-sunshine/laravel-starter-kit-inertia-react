<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\BrochureProcessing;
use App\Services\TenantContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Laravel\Ai\Facades\Ai;
use Stringable;

final class BrochureProcessor implements Tool
{
    public function description(): string
    {
        return 'Upload and process PDF brochures to extract project or lot information. Returns extracted data for admin approval before creating projects/lots.';
    }

    public function handle(Request $request): Stringable|string
    {
        $filePath = $request->string('file_path')->trim();
        $processingType = $request->string('type', 'project');

        if (empty($filePath)) {
            return 'Error: File path is required.';
        }

        if (!Storage::exists($filePath)) {
            return 'Error: File not found. Please ensure the PDF is uploaded first.';
        }

        try {
            // Extract text from PDF (assuming Laravel AI can handle PDF)
            $pdfContent = $this->extractTextFromPdf($filePath);

            if (empty($pdfContent)) {
                return 'Error: Could not extract text from PDF. Please ensure it\'s a valid PDF with readable text.';
            }

            // Use AI to extract structured data
            $extractedData = $this->extractDataWithAi($pdfContent, $processingType);

            // Store the processing record for admin approval
            $user = auth()->user();
            $organizationId = TenantContext::id()
                ?? $user?->defaultOrganization()?->id;

            if ($organizationId === null) {
                return 'Error: No organization selected. Please select an organization (or ensure you have a default organization) before processing documents.';
            }

            $processing = BrochureProcessing::create([
                'organization_id' => $organizationId,
                'file_path' => $filePath,
                'type' => $processingType,
                'extracted_data' => $extractedData,
                'status' => 'pending_approval',
                'processed_by_user_id' => auth()->id(),
            ]);

            return "PDF processed successfully! 🎉\n\n" .
                   "**Extracted {$processingType} Information:**\n" .
                   $this->formatExtractedData($extractedData, $processingType) . "\n\n" .
                   "**Next Steps:**\n" .
                   "- Review the extracted information above\n" .
                   "- Admin approval is required before creating the {$processingType}\n" .
                   "- Processing ID: {$processing->id}\n\n" .
                   "Would you like to submit this for admin approval? The admin will be notified to review and approve the creation of this {$processingType}.";

        } catch (\Exception $e) {
            return "Error processing PDF: {$e->getMessage()}";
        }
    }

    private function extractTextFromPdf(string $filePath): string
    {
        // For now, we'll simulate PDF text extraction
        // In production, you might use libraries like smalot/pdfparser or spatie/pdf-to-text
        $fullPath = Storage::path($filePath);

        // Simple text extraction simulation
        // You should replace this with actual PDF parsing logic
        return "Sample extracted text from PDF. This would contain the actual brochure content.";
    }

    private function extractDataWithAi(string $pdfContent, string $type): array
    {
        $prompt = $type === 'project' ? $this->getProjectExtractionPrompt() : $this->getLotExtractionPrompt();

        try {
            $response = Ai::using('openrouter')
                ->text(model: 'openai/gpt-4o-mini')
                ->withSystemPrompt($prompt)
                ->generate("Extract information from this brochure content:\n\n{$pdfContent}");

            // Parse the AI response into structured data
            return $this->parseAiResponse($response->content, $type);

        } catch (\Exception $e) {
            throw new \Exception("AI processing failed: {$e->getMessage()}");
        }
    }

    private function getProjectExtractionPrompt(): string
    {
        return "You are a real estate data extraction expert. Extract project information from brochure content and return a JSON object with these fields:
        {
            \"title\": \"Project name\",
            \"estate\": \"Estate name\",
            \"stage\": \"Development stage\",
            \"description\": \"Project description\",
            \"developer\": \"Developer name\",
            \"projecttype\": \"Project type (e.g., residential, commercial)\",
            \"total_lots\": \"Number of lots (integer)\",
            \"min_price\": \"Minimum price (numeric)\",
            \"max_price\": \"Maximum price (numeric)\",
            \"location\": \"Project location\",
            \"features\": [\"List of key features\"],
            \"confidence\": \"High/Medium/Low - your confidence in the extraction\"
        }
        Only extract information that is explicitly mentioned in the content. Use null for missing values.";
    }

    private function getLotExtractionPrompt(): string
    {
        return "You are a real estate data extraction expert. Extract lot information from brochure content and return a JSON object with these fields:
        {
            \"title\": \"Lot number/name\",
            \"price\": \"Lot price (numeric)\",
            \"land_price\": \"Land component price (numeric)\",
            \"stage\": \"Development stage\",
            \"bedrooms\": \"Number of bedrooms (integer)\",
            \"bathrooms\": \"Number of bathrooms (integer)\",
            \"land_size\": \"Land size in sqm\",
            \"project_title\": \"Associated project name\",
            \"features\": [\"List of lot features\"],
            \"confidence\": \"High/Medium/Low - your confidence in the extraction\"
        }
        Only extract information that is explicitly mentioned in the content. Use null for missing values.";
    }

    private function parseAiResponse(string $response, string $type): array
    {
        // Try to extract JSON from the AI response
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');

        if ($jsonStart !== false && $jsonEnd !== false) {
            $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
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
            'extraction_error' => 'Could not parse structured data from AI response'
        ];
    }

    private function formatExtractedData(array $data, string $type): string
    {
        $formatted = "";

        if ($type === 'project') {
            $formatted .= "**Title:** " . ($data['title'] ?? 'Not found') . "\n";
            $formatted .= "**Estate:** " . ($data['estate'] ?? 'Not found') . "\n";
            $formatted .= "**Developer:** " . ($data['developer'] ?? 'Not found') . "\n";
            $formatted .= "**Stage:** " . ($data['stage'] ?? 'Not found') . "\n";
            $formatted .= "**Total Lots:** " . ($data['total_lots'] ?? 'Not found') . "\n";
            $formatted .= "**Price Range:** $" . number_format($data['min_price'] ?? 0) . " - $" . number_format($data['max_price'] ?? 0) . "\n";
            $formatted .= "**Location:** " . ($data['location'] ?? 'Not found') . "\n";
        } else {
            $formatted .= "**Lot Title:** " . ($data['title'] ?? 'Not found') . "\n";
            $formatted .= "**Project:** " . ($data['project_title'] ?? 'Not found') . "\n";
            $formatted .= "**Price:** $" . number_format($data['price'] ?? 0) . "\n";
            $formatted .= "**Bedrooms:** " . ($data['bedrooms'] ?? 'Not found') . "\n";
            $formatted .= "**Bathrooms:** " . ($data['bathrooms'] ?? 'Not found') . "\n";
            $formatted .= "**Land Size:** " . ($data['land_size'] ?? 'Not found') . "\n";
        }

        $formatted .= "**Confidence:** " . ($data['confidence'] ?? 'Unknown') . "\n";

        return $formatted;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'file_path' => $schema->string()->description('Path to the uploaded PDF brochure file.'),
            'type' => $schema->string()->description('Type of data to extract: "project" or "lot".')->default('project'),
        ];
    }
}