<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\PreviewRailwayReceiptImport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreApiRailwayReceiptImportPreviewRequest;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

final class RailwayReceiptImportPreviewController extends Controller
{
    public function store(
        StoreApiRailwayReceiptImportPreviewRequest $request,
        PreviewRailwayReceiptImport $previewRailwayReceiptImport,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        abort_unless($this->hasSectionPermission($user, 'sections.railway_receipts.upload'), 403);

        try {
            return response()->json(
                $previewRailwayReceiptImport->handle($user, $request->file('pdf')),
            );
        } catch (InvalidArgumentException $e) {
            Log::warning('RR import preview validation failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('RR import preview failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            report($e);

            return response()->json([
                'message' => 'Failed to process Railway Receipt preview. Please ensure the PDF is valid and try again.',
            ], 500);
        }
    }

    private function hasSectionPermission(User $user, string $permission): bool
    {
        if ($user->can('bypass-permissions')) {
            return true;
        }

        if (TenantContext::check() && $user->canInCurrentOrganization($permission)) {
            return true;
        }

        return $user->hasPermissionTo($permission);
    }
}
