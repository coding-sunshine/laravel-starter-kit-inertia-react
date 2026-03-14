<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DealDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Upload a deal document file and create the record.
 */
final class UploadDealDocumentAction
{
    public function handle(
        string $dealType,
        int $dealId,
        UploadedFile $file,
        string $documentType,
        string $title,
        ?int $organizationId = null,
    ): DealDocument {
        $path = "deal-docs/{$dealType}/{$dealId}/";
        $filePath = Storage::disk('private')->putFile($path, $file);

        /** @var DealDocument $document */
        $document = DealDocument::query()->create([
            'organization_id' => $organizationId,
            'deal_type' => $dealType,
            'deal_id' => $dealId,
            'document_type' => $documentType,
            'title' => $title,
            'file_path' => (string) $filePath,
            'file_size' => $file->getSize() ?: null,
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => auth()->id(),
        ]);

        return $document;
    }
}
