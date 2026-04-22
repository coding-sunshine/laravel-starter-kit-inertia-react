<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreApiRailwayReceiptUploadRequest;
use App\Models\DiverrtDestination;
use App\Models\PowerPlant;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\User;
use App\Services\Railway\RrImportService;
use App\Services\Railway\RrParserService;
use App\Services\TenantContext;
use App\Support\RakeRrHubPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

final class RailwayReceiptUploadController extends Controller
{
    public function store(
        StoreApiRailwayReceiptUploadRequest $request,
        RrParserService $parser,
        RrImportService $rrImportService,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        abort_unless($this->hasSectionPermission($user, 'sections.railway_receipts.upload'), 403);

        $validated = $request->validated();
        $pdf = $validated['pdf'];

        $rake = Rake::query()->find((int) $validated['rake_id']);
        if ($rake === null) {
            return response()->json([
                'message' => 'Selected rake is invalid or no longer available.',
            ], 422);
        }

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        if (! in_array($rake->siding_id, $sidingIds, true)) {
            abort(403);
        }

        $sidingId = $validated['siding_id'] ?? $rake->siding_id;
        $powerPlantId = $validated['power_plant_id'] ?? PowerPlant::query()->orderBy('id')->value('id');

        if ($powerPlantId === null) {
            return response()->json([
                'message' => 'No power plant available. Create a power plant first.',
            ], 422);
        }

        $diverrtDestination = null;
        if (! empty($validated['diverrt_destination_id'])) {
            $diverrtDestination = DiverrtDestination::query()->find((int) $validated['diverrt_destination_id']);
        }

        try {
            $parsed = $parser->parse($pdf);

            $importValidated = [
                'pdf' => $pdf,
                'siding_id' => $sidingId,
                'power_plant_id' => $powerPlantId,
                'rake_id' => $rake->id,
                'diverrt_destination_id' => $validated['diverrt_destination_id'] ?? null,
            ];

            $rrDocument = $rrImportService->importSnapshotOnly($parsed, $request, $importValidated, $rake, $diverrtDestination);

            $rakeForHub = $rake->refresh()->load(['rrDocuments', 'diverrtDestinations']);

            return response()->json([
                'rr_document' => [
                    'id' => $rrDocument->id,
                    'rr_number' => $rrDocument->rr_number,
                    'diverrt_destination_id' => $rrDocument->diverrt_destination_id,
                ],
                'rr_hub' => RakeRrHubPayload::fromRake($rakeForHub),
            ], 201);
        } catch (InvalidArgumentException $e) {
            Log::warning('RR API upload validation failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('RR API upload failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            report($e);

            return response()->json([
                'message' => 'Failed to process Railway Receipt. '.$e->getMessage(),
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
