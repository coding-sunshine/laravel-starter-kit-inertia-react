<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkDocumentUploadRequest;
use App\Jobs\ProcessDocumentBatchJob;
use App\Models\BrochureProcessing;
use App\Services\TenantContext;
use Illuminate\Bus\Batch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class BulkDocumentController extends Controller
{
    /**
     * Upload multiple documents for batch processing
     */
    public function upload(BulkDocumentUploadRequest $request): JsonResponse
    {
        try {
            $batchId = Str::uuid()->toString();
            $user = auth()->user();
            $organizationId = TenantContext::id();
            $files = $request->file('files', []);
            $processingType = $request->string('processing_type', 'auto');
            $jobs = [];
            $processedFiles = [];

            if (empty($files)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No files were uploaded',
                ], 400);
            }

            DB::transaction(function () use ($files, $batchId, $processingType, $user, $organizationId, &$jobs, &$processedFiles) {
                foreach ($files as $file) {
                    if (! ($file instanceof UploadedFile) || ! $file->isValid()) {
                        continue;
                    }

                    // Store the file
                    $filePath = $file->store('document-uploads', 'local');

                    // Create the processing record
                    $processing = BrochureProcessing::create([
                        'organization_id' => $organizationId,
                        'batch_id' => $batchId,
                        'file_path' => $filePath,
                        'type' => $processingType,
                        'status' => 'pending_approval',
                        'queue_status' => 'pending',
                        'processed_by_user_id' => $user->id,
                        'extracted_data' => [],
                    ]);

                    // Create the job
                    $jobs[] = new ProcessDocumentBatchJob(
                        $processing->id,
                        $organizationId,
                        $user->id
                    );

                    $processedFiles[] = [
                        'id' => $processing->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ];
                }
            });

            if (empty($jobs)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid files were uploaded',
                ], 400);
            }

            // Dispatch the batch
            $batch = Bus::batch($jobs)
                ->name("Document Processing Batch: {$batchId}")
                ->allowFailures()
                ->dispatch();

            Log::info('Batch document upload initiated', [
                'batch_id' => $batchId,
                'files_count' => count($processedFiles),
                'user_id' => $user->id,
                'organization_id' => $organizationId,
            ]);

            return response()->json([
                'success' => true,
                'message' => sprintf('%d documents uploaded and queued for processing', count($processedFiles)),
                'data' => [
                    'batch_id' => $batchId,
                    'files_count' => count($processedFiles),
                    'files' => $processedFiles,
                    'processing_type' => $processingType,
                ],
            ]);

        } catch (Throwable $e) {
            Log::error('Bulk document upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while uploading documents: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get batch processing status
     */
    public function batchStatus(Request $request, string $batchId): JsonResponse
    {
        try {
            $organizationId = TenantContext::id();

            $documents = BrochureProcessing::where('batch_id', $batchId)
                ->where('organization_id', $organizationId)
                ->with(['processedByUser', 'approvedByUser'])
                ->get();

            if ($documents->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Batch not found',
                ], 404);
            }

            $stats = [
                'total' => $documents->count(),
                'pending' => $documents->where('queue_status', 'pending')->count(),
                'processing' => $documents->where('queue_status', 'processing')->count(),
                'completed' => $documents->where('queue_status', 'completed')->count(),
                'failed' => $documents->where('queue_status', 'failed')->count(),
                'approved' => $documents->where('status', 'approved')->count(),
                'rejected' => $documents->where('status', 'rejected')->count(),
                'pending_approval' => $documents->where('status', 'pending_approval')->count(),
            ];

            $documentsData = $documents->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'file_name' => $doc->file_name,
                    'file_size' => $doc->formatted_file_size,
                    'type' => $doc->type,
                    'queue_status' => $doc->queue_status,
                    'status' => $doc->status,
                    'processing_started_at' => $doc->processing_started_at?->toISOString(),
                    'processing_completed_at' => $doc->processing_completed_at?->toISOString(),
                    'extracted_data' => $doc->extracted_data,
                    'admin_notes' => $doc->admin_notes,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'batch_id' => $batchId,
                    'stats' => $stats,
                    'documents' => $documentsData,
                    'is_complete' => $stats['pending'] + $stats['processing'] === 0,
                ],
            ]);

        } catch (Throwable $e) {
            Log::error('Batch status check failed', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking batch status',
            ], 500);
        }
    }

    /**
     * Get processing queue status for real-time updates
     */
    public function queueStatus(Request $request): JsonResponse
    {
        try {
            $organizationId = TenantContext::id();
            $batchId = $request->query('batch_id');

            $query = BrochureProcessing::where('organization_id', $organizationId);

            if ($batchId) {
                $query->where('batch_id', $batchId);
            } else {
                // Only show recent documents if no batch specified
                $query->where('created_at', '>=', now()->subDays(7));
            }

            $documents = $query->orderBy('created_at', 'desc')->get();

            $stats = [
                'total' => $documents->count(),
                'pending' => $documents->where('queue_status', 'pending')->count(),
                'processing' => $documents->where('queue_status', 'processing')->count(),
                'completed' => $documents->where('queue_status', 'completed')->count(),
                'failed' => $documents->where('queue_status', 'failed')->count(),
            ];

            $recentActivity = $documents
                ->whereNotNull('processing_completed_at')
                ->sortByDesc('processing_completed_at')
                ->take(5)
                ->map(function ($doc) {
                    return [
                        'file_name' => $doc->file_name,
                        'queue_status' => $doc->queue_status,
                        'status' => $doc->status,
                        'completed_at' => $doc->processing_completed_at?->diffForHumans(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'recent_activity' => $recentActivity,
                    'timestamp' => now()->toISOString(),
                ],
            ]);

        } catch (Throwable $e) {
            Log::error('Queue status check failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking queue status',
            ], 500);
        }
    }

    /**
     * Bulk approve documents
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'document_ids' => 'required|array|min:1',
                'document_ids.*' => 'integer|exists:brochure_processings,id',
            ]);

            $organizationId = TenantContext::id();
            $user = auth()->user();
            $documentIds = $request->input('document_ids');

            $updated = BrochureProcessing::whereIn('id', $documentIds)
                ->where('organization_id', $organizationId)
                ->where('queue_status', 'completed')
                ->where('status', 'pending_approval')
                ->update([
                    'status' => 'approved',
                    'approved_by_user_id' => $user->id,
                    'approved_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => "{$updated} documents approved successfully",
                'data' => ['approved_count' => $updated],
            ]);

        } catch (Throwable $e) {
            Log::error('Bulk approval failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during bulk approval',
            ], 500);
        }
    }

    /**
     * Bulk reject documents
     */
    public function bulkReject(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'document_ids' => 'required|array|min:1',
                'document_ids.*' => 'integer|exists:brochure_processings,id',
                'reason' => 'nullable|string|max:500',
            ]);

            $organizationId = TenantContext::id();
            $user = auth()->user();
            $documentIds = $request->input('document_ids');
            $reason = $request->input('reason');

            $updated = BrochureProcessing::whereIn('id', $documentIds)
                ->where('organization_id', $organizationId)
                ->where('queue_status', 'completed')
                ->where('status', 'pending_approval')
                ->update([
                    'status' => 'rejected',
                    'approved_by_user_id' => $user->id,
                    'approved_at' => now(),
                    'admin_notes' => $reason ? "Rejected: {$reason}" : 'Rejected via bulk action',
                ]);

            return response()->json([
                'success' => true,
                'message' => "{$updated} documents rejected successfully",
                'data' => ['rejected_count' => $updated],
            ]);

        } catch (Throwable $e) {
            Log::error('Bulk rejection failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during bulk rejection',
            ], 500);
        }
    }
}
