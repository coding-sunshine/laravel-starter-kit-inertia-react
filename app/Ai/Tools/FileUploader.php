<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class FileUploader implements Tool
{
    public function description(): string
    {
        return 'Note: Files are now uploaded directly via the web interface. This tool is no longer needed as file upload is handled automatically when users select files. Use the document_processor tool directly with the file path provided in the chat message.';
    }

    public function handle(Request $request): Stringable|string
    {
        $fileContent = $request->string('file_content')->trim();
        $fileName = $request->string('file_name', 'uploaded_file.pdf');
        $fileType = $request->string('file_type', 'application/pdf');

        if (empty($fileContent)) {
            return 'Error: File content is required. Please provide the base64 encoded file content.';
        }

        try {
            // Decode base64 content
            $decodedContent = base64_decode($fileContent, true);

            if ($decodedContent === false) {
                return 'Error: Invalid file content. Please provide valid base64 encoded file data.';
            }

            // Generate unique filename
            $extension = pathinfo($fileName, PATHINFO_EXTENSION) ?: 'bin';
            $uniqueName = Str::uuid() . '_' . Str::slug(pathinfo($fileName, PATHINFO_FILENAME)) . '.' . $extension;
            $filePath = 'uploads/documents/' . $uniqueName;

            // Store the file
            Storage::put($filePath, $decodedContent);

            // Verify the file was stored
            if (!Storage::exists($filePath)) {
                return 'Error: Failed to store the uploaded file.';
            }

            $fileSize = Storage::size($filePath);
            $fileSizeFormatted = $this->formatFileSize($fileSize);

            return "✅ **File uploaded successfully!**\n\n" .
                   "📁 **File Details:**\n" .
                   "- **Name:** {$fileName}\n" .
                   "- **Size:** {$fileSizeFormatted}\n" .
                   "- **Type:** {$fileType}\n" .
                   "- **Storage Path:** {$filePath}\n\n" .
                   "🔧 **Next Steps:**\n" .
                   "You can now use this file with the `document_processor` tool to extract property information from any file type.\n\n" .
                   "**Example usage:**\n" .
                   "To process this file automatically, use:\n" .
                   "`document_processor(file_path='{$filePath}', type='auto')`\n" .
                   "Or specify type: `document_processor(file_path='{$filePath}', type='project')` or `type='lot'`";

        } catch (\Exception $e) {
            return "Error uploading file: {$e->getMessage()}";
        }
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
            'file_content' => $schema->string()->description('Base64 encoded file content.'),
            'file_name' => $schema->string()->description('Original filename (e.g., "project_brochure.pdf").')->default('uploaded_file.pdf'),
            'file_type' => $schema->string()->description('MIME type of the file (e.g., "application/pdf").')->default('application/pdf'),
        ];
    }
}