<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UploadDealDocumentAction;
use App\Models\DealDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class DealDocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'deal_type' => ['required', 'string', 'in:reservation,sale'],
            'deal_id' => ['required', 'integer'],
        ]);

        $documents = DealDocument::query()
            ->where('deal_type', $validated['deal_type'])
            ->where('deal_id', $validated['deal_id'])
            ->with('uploader')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($documents);
    }

    public function store(Request $request, UploadDealDocumentAction $action): JsonResponse
    {
        $validated = $request->validate([
            'deal_type' => ['required', 'string', 'in:reservation,sale'],
            'deal_id' => ['required', 'integer'],
            'document_type' => ['required', 'string', 'in:contract,invoice,id_doc,email,other'],
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:20480'], // 20MB
        ]);

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file('file');

        /** @var int|null $organizationId */
        $organizationId = $request->user()?->getAttribute('current_organization_id');

        $document = $action->handle(
            dealType: $validated['deal_type'],
            dealId: (int) $validated['deal_id'],
            file: $file,
            documentType: $validated['document_type'],
            title: $validated['title'],
            organizationId: $organizationId,
        );

        return response()->json($document->load('uploader'), 201);
    }

    public function destroy(DealDocument $dealDocument): JsonResponse
    {
        if (Storage::disk('private')->exists($dealDocument->file_path)) {
            Storage::disk('private')->delete($dealDocument->file_path);
        }

        $dealDocument->delete();

        return response()->json(['success' => true]);
    }
}
