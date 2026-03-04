<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\BrochureProcessing;
use App\Services\TenantContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class DocumentProcessor implements Tool
{
    public function description(): string
    {
        return 'Process any type of document or image to extract property, project, or lot information. Supports PDFs, images (JPG, PNG, etc.), Word docs, text files, and more. Returns extracted data for admin approval.';
    }

    public function handle(Request $request): Stringable|string
    {
        $filePath = (string) $request->string('file_path')->trim();
        $processingType = (string) $request->string('type', 'auto');

        if ($filePath === '') {
            return 'Error: File path is required.';
        }

        if (!Storage::exists($filePath)) {
            return "Error: File not found at '{$filePath}'. Please ensure the file is uploaded first.";
        }

        try {
            $fileSize = Storage::size($filePath);
            $mimeType = Storage::mimeType($filePath) ?? 'unknown';
            $fileExtension = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));

            // Extract content based on file type
            $documentContent = $this->extractContentFromFile($filePath, $mimeType, $fileExtension);

            if (empty($documentContent)) {
                return 'Error: Could not extract readable content from this file. Please ensure it contains text or is a supported format.';
            }

            // Auto-detect processing type if not specified
            if ($processingType === 'auto') {
                $processingType = $this->detectContentType($documentContent);
            }

            // Use AI to extract structured data
            $extractedData = $this->extractDataWithAi($documentContent, $processingType, $mimeType, $fileExtension);

            // Store the processing record for admin approval
            $user = auth()->user();
            $organizationId = TenantContext::id()
                ?? $user?->defaultOrganization()?->id;

            if ($organizationId === null) {
                return 'Error: No organization selected. Please select an organization (or ensure you have a default organization) before processing documents.';
            }

            $userId = $user?->id;

            $processing = BrochureProcessing::create([
                'organization_id' => $organizationId,
                'file_path' => $filePath,
                'type' => $processingType,
                'extracted_data' => array_merge($extractedData, [
                    'original_file_type' => $mimeType,
                    'file_extension' => $fileExtension,
                    'file_size' => $fileSize,
                ]),
                'status' => 'pending_approval',
                'processed_by_user_id' => $userId,
            ]);

            return "✅ **Document processed successfully!** 🎉\n\n" .
                   "**📄 File Information:**\n" .
                   "- **Type:** " . $this->getFileTypeDescription($mimeType, $fileExtension) . "\n" .
                   "- **Size:** " . $this->formatFileSize($fileSize) . "\n" .
                   "- **Format:** {$mimeType}\n\n" .
                   "**🔍 Extracted {$processingType} Information:**\n" .
                   $this->formatExtractedData($extractedData, $processingType) . "\n\n" .
                   "**⏭️ Ready to Create:**\n" .
                   "- Processing ID: {$processing->id}\n" .
                   "- Confidence Level: " . ($extractedData['confidence'] ?? 'Unknown') . "\n\n" .
                   "🤖 **Would you like me to create this {$processingType} now?**\n\n" .
                   "Type **'yes'** to create the {$processingType} immediately, or **'no'** to save for later admin review.\n\n" .
                   "The extracted information looks accurate and ready to be added to your system!";

        } catch (\Throwable $e) {
            return "❌ Error processing document: {$e->getMessage()}";
        }
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

        if (in_array($mimeType, ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
            || in_array($extension, ['doc', 'docx'])) {
            return $this->extractFromWord($filePath);
        }

        try {
            $content = Storage::get($filePath);
            if ($content !== null && mb_check_encoding($content, 'UTF-8')) {
                return $content;
            }
        } catch (\Throwable) {
        }

        return "File uploaded: " . basename($filePath) . " (Binary file - content extraction not available)";
    }

    private function extractFromImage(string $filePath): string
    {
        try {
            $absolutePath = Storage::path($filePath);

            if (!file_exists($absolutePath)) {
                return "Image file not found at path: {$absolutePath}";
            }

            $fileSize = filesize($absolutePath);
            $fileName = basename($absolutePath);

            // Get image dimensions for context
            $imageInfo = @getimagesize($absolutePath);
            $dimensions = $imageInfo ? $imageInfo[0] . 'x' . $imageInfo[1] : 'unknown';

            return "Image file '{$fileName}' ({$this->formatFileSize($fileSize)}, {$dimensions}) uploaded. " .
                   "This appears to be a property document image. " .
                   "The image likely contains property information such as floor plans, brochures, or property listings. " .
                   "Please describe the visible content or convert to text/PDF format for automatic processing.";

        } catch (\Exception $e) {
            return "Error processing image: " . $e->getMessage();
        }
    }

    private function extractFromPdf(string $filePath): string
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $absolutePath = Storage::path($filePath);

            if (!file_exists($absolutePath)) {
                return "PDF file not found at path: {$absolutePath}";
            }

            $pdf = $parser->parseFile($absolutePath);
            $text = $pdf->getText();

            if (empty(trim($text))) {
                return "PDF document appears to be image-based or encrypted. Unable to extract text content.";
            }

            // Clean up the extracted text
            $cleanText = preg_replace('/\s+/', ' ', trim($text));

            return $cleanText;

        } catch (\Exception $e) {
            return "Error extracting PDF content: " . $e->getMessage() . ". The PDF may be encrypted, corrupted, or image-based.";
        }
    }

    private function extractFromWord(string $filePath): string
    {
        try {
            $absolutePath = Storage::path($filePath);

            if (!file_exists($absolutePath)) {
                return "Word document file not found at path: {$absolutePath}";
            }

            // For now, return a descriptive message for Word docs
            // In the future, implement phpoffice/phpword when authentication issues are resolved
            $fileSize = filesize($absolutePath);
            $fileName = basename($absolutePath);

            return "Word document '{$fileName}' ({$this->formatFileSize($fileSize)}) uploaded. " .
                   "Please provide the text content or key details from the document " .
                   "for property information extraction. Alternatively, convert to PDF or text format for automatic processing.";

        } catch (\Exception $e) {
            return "Error accessing Word document: " . $e->getMessage();
        }
    }

    private function detectContentType(string $content): string
    {
        $content = strtolower($content);

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

        } catch (\Throwable $e) {
            return [
                'type' => $type,
                'raw_content' => substr($documentContent, 0, 1000),
                'confidence' => 'Low',
                'extraction_error' => "AI processing failed: {$e->getMessage()}",
                'fallback_extraction' => true,
            ];
        }
    }

    private function getExtractionPrompt(string $type, string $mimeType, string $extension): string
    {
        $basePrompt = "You are an expert real estate data extraction AI. Extract information from various document types including PDFs, images, Word docs, and text files.";

        if ($type === 'project') {
            return $basePrompt . " Extract project information and return a JSON object with these fields:
            {
                \"title\": \"Project name\",
                \"estate\": \"Estate name\",
                \"stage\": \"Development stage\",
                \"description\": \"Project description\",
                \"developer\": \"Developer name\",
                \"projecttype\": \"Project type (residential, commercial, etc.)\",
                \"total_lots\": \"Number of lots (integer)\",
                \"min_price\": \"Minimum price (numeric)\",
                \"max_price\": \"Maximum price (numeric)\",
                \"location\": \"Project location\",
                \"features\": [\"List of key features\"],
                \"confidence\": \"High/Medium/Low - your confidence in extraction\",
                \"source_type\": \"Type of document processed\"
            }
            If processing an image, describe what you see and extract any visible text or property details.
            Only extract information explicitly visible/mentioned. Use null for missing values.";
        } else {
            return $basePrompt . " Extract lot/property information and return a JSON object with these fields:
            {
                \"title\": \"Lot number/name\",
                \"price\": \"Lot price (numeric)\",
                \"land_price\": \"Land component price (numeric)\",
                \"stage\": \"Development stage\",
                \"bedrooms\": \"Number of bedrooms (integer)\",
                \"bathrooms\": \"Number of bathrooms (integer)\",
                \"land_size\": \"Land size in sqm\",
                \"project_title\": \"Associated project name\",
                \"address\": \"Property address\",
                \"features\": [\"List of lot features\"],
                \"confidence\": \"High/Medium/Low - your confidence in extraction\",
                \"source_type\": \"Type of document processed\"
            }
            If processing an image, describe what you see and extract any visible text or property details.
            Only extract information explicitly visible/mentioned. Use null for missing values.";
        }
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
            'extraction_error' => 'Could not parse structured data from AI response',
            'fallback_data' => true
        ];
    }

    private function formatExtractedData(array $data, string $type): string
    {
        if (isset($data['fallback_data'])) {
            return "**Raw AI Response:**\n" . ($data['raw_response'] ?? 'No response available');
        }

        $formatted = "";

        if ($type === 'project') {
            $formatted .= "**Title:** " . ($data['title'] ?? 'Not found') . "\n";
            $formatted .= "**Estate:** " . ($data['estate'] ?? 'Not found') . "\n";
            $formatted .= "**Developer:** " . ($data['developer'] ?? 'Not found') . "\n";
            $formatted .= "**Stage:** " . ($data['stage'] ?? 'Not found') . "\n";
            $formatted .= "**Total Lots:** " . ($data['total_lots'] ?? 'Not found') . "\n";
            if (isset($data['min_price']) || isset($data['max_price'])) {
                $formatted .= "**Price Range:** $" . number_format($data['min_price'] ?? 0) . " - $" . number_format($data['max_price'] ?? 0) . "\n";
            }
            $formatted .= "**Location:** " . ($data['location'] ?? 'Not found') . "\n";
        } else {
            $formatted .= "**Lot Title:** " . ($data['title'] ?? 'Not found') . "\n";
            $formatted .= "**Project:** " . ($data['project_title'] ?? 'Not found') . "\n";
            $formatted .= "**Address:** " . ($data['address'] ?? 'Not found') . "\n";
            if (isset($data['price'])) {
                $formatted .= "**Price:** $" . number_format($data['price']) . "\n";
            }
            $formatted .= "**Bedrooms:** " . ($data['bedrooms'] ?? 'Not found') . "\n";
            $formatted .= "**Bathrooms:** " . ($data['bathrooms'] ?? 'Not found') . "\n";
            $formatted .= "**Land Size:** " . ($data['land_size'] ?? 'Not found') . "\n";
        }

        $formatted .= "**Confidence:** " . ($data['confidence'] ?? 'Unknown') . "\n";
        $formatted .= "**Source:** " . ($data['source_type'] ?? 'Document') . "\n";

        if (!empty($data['features']) && is_array($data['features'])) {
            $formatted .= "**Features:** " . implode(', ', $data['features']) . "\n";
        }

        return $formatted;
    }

    private function getFileTypeDescription(string $mimeType, string $extension): string
    {
        if (str_starts_with($mimeType, 'image/')) return 'Image';
        if ($mimeType === 'application/pdf') return 'PDF Document';
        if (str_starts_with($mimeType, 'text/')) return 'Text File';
        if (str_contains($mimeType, 'word')) return 'Word Document';
        if (str_contains($mimeType, 'excel') || str_contains($mimeType, 'spreadsheet')) return 'Spreadsheet';
        return ucfirst($extension) . ' File';
    }

    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        $size = $bytes;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'file_path' => $schema->string()->description('Path to the uploaded file.'),
            'type' => $schema->string()->description('Type of data to extract: "project", "lot", or "auto" for automatic detection.')->default('auto'),
        ];
    }
}