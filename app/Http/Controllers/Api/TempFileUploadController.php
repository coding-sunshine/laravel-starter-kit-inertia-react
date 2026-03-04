<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TempFileUploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:102400', // 100MB max
        ]);

        try {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension() ?: 'bin';
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $uniqueName = Str::uuid() . '_' . Str::slug($originalName) . '.' . $extension;
            $filePath = 'uploads/documents/' . $uniqueName;

            // Store the file using Laravel's default storage
            $file->storeAs('uploads/documents', $uniqueName, 'local');

            // Verify the file was stored
            if (!Storage::exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to store the uploaded file.'
                ], 500);
            }

            $fileSize = Storage::size($filePath);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => [
                    'file_path' => $filePath,
                    'original_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $fileSize,
                    'file_size_formatted' => $this->formatFileSize($fileSize),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Error uploading file: {$e->getMessage()}"
            ], 500);
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
}
