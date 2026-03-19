<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PowerPlant;
use App\Models\Rake;
use App\Models\RrDocument;
use App\Models\Siding;
use App\Services\Railway\RrImportService;
use App\Services\Railway\RrParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

final class RailwayReceiptUploadController extends Controller
{
    public function store(
        Request $request,
        RrParserService $parser,
        RrImportService $rrImportService,
    ): JsonResponse {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $validated = $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'rake_id' => ['nullable', 'integer', 'exists:rakes,id'],
            'siding_id' => ['nullable', 'integer', 'exists:sidings,id'],
            'power_plant_id' => ['nullable', 'integer', 'exists:power_plants,id'],
        ]);

        $pdf = $validated['pdf'];
        $rakeId = $validated['rake_id'] ?? null;

        try {
            $parsed = $parser->parse($pdf);

            if ($rakeId !== null) {
                $rake = Rake::query()->findOrFail((int) $rakeId);

                $sidingIds = $user->isSuperAdmin()
                    ? Siding::query()->pluck('id')->all()
                    : $user->accessibleSidings()->get()->pluck('id')->all();

                abort_unless(in_array($rake->siding_id, $sidingIds, true), 403);

                $sidingId = $validated['siding_id'] ?? $rake->siding_id;
                $powerPlantId = $validated['power_plant_id'] ?? PowerPlant::query()->orderBy('id')->value('id');

                if ($powerPlantId === null) {
                    return response()->json([
                        'message' => 'No power plant available. Create a power plant first.',
                    ], 422);
                }

                $importValidated = [
                    'pdf' => $pdf,
                    'siding_id' => $sidingId,
                    'power_plant_id' => $powerPlantId,
                ];

                $rrDocument = $rrImportService->importSnapshotOnly($parsed, $request, $importValidated, $rake);

                return response()->json([
                    'data' => [
                        'mode' => 'existing_rake',
                        'rr_document' => $this->transformDocument($rrDocument),
                    ],
                ], 201);
            }

            $sidingIds = $user->isSuperAdmin()
                ? Siding::query()->pluck('id')->all()
                : $user->accessibleSidings()->get()->pluck('id')->all();

            $sidingId = $validated['siding_id'] ?? ($sidingIds[0] ?? null);

            if ($sidingId === null) {
                return response()->json([
                    'message' => 'No siding available. Create a siding first.',
                ], 422);
            }

            $powerPlantId = $validated['power_plant_id'] ?? PowerPlant::query()->orderBy('id')->value('id');

            if ($powerPlantId === null) {
                return response()->json([
                    'message' => 'No power plant available. Create a power plant first.',
                ], 422);
            }

            $importValidated = [
                'pdf' => $pdf,
                'siding_id' => $sidingId,
                'power_plant_id' => $powerPlantId,
            ];

            $rrDocument = $rrImportService->importSnapshotOnly($parsed, $request, $importValidated, null);

            return response()->json([
                'data' => [
                    'mode' => 'standalone',
                    'rr_document' => $this->transformDocument($rrDocument),
                ],
            ], 201);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'pdf' => [$e->getMessage()],
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Failed to process Railway Receipt.',
            ], 500);
        }
    }

    private function transformDocument(RrDocument $document): array
    {
        return [
            'id' => $document->id,
            'rake_id' => $document->rake_id,
            'rr_number' => $document->rr_number,
            'rr_received_date' => $document->rr_received_date,
            'rr_weight_mt' => $document->rr_weight_mt,
            'document_status' => $document->document_status,
        ];
    }
}
