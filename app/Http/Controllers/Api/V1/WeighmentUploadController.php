<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Rake;
use App\Models\Siding;
use App\Services\RakeWeighmentPdfImporter;
use App\Services\WeighmentPdfImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

final class WeighmentUploadController extends Controller
{
    public function store(
        Request $request,
        WeighmentPdfImporter $historicalImporter,
        RakeWeighmentPdfImporter $rakeImporter,
    ): JsonResponse {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        try {
            $validated = $request->validate([
                'pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
                'rake_id' => ['nullable', 'integer', 'exists:rakes,id'],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        $pdf = $validated['pdf'];
        $rakeId = $validated['rake_id'] ?? null;

        try {
            if ($rakeId !== null) {
                $rake = Rake::query()->findOrFail($rakeId);

                $sidingIds = $user->isSuperAdmin()
                    ? Siding::query()->pluck('id')->all()
                    : $user->accessibleSidings()->get()->pluck('id')->all();

                abort_unless(in_array($rake->siding_id, $sidingIds, true), 403);

                $rakeWeighment = $rakeImporter->importForRake($rake, $pdf, (int) $user->id);

                return response()->json([
                    'data' => [
                        'mode' => 'existing_rake',
                        'rake_weighment' => [
                            'id' => $rakeWeighment->id,
                            'rake_id' => $rakeWeighment->rake_id,
                            'attempt_no' => $rakeWeighment->attempt_no,
                            'gross_weighment_datetime' => $rakeWeighment->gross_weighment_datetime,
                            'tare_weighment_datetime' => $rakeWeighment->tare_weighment_datetime,
                            'status' => $rakeWeighment->status,
                            'total_gross_weight_mt' => $rakeWeighment->total_gross_weight_mt,
                            'total_tare_weight_mt' => $rakeWeighment->total_tare_weight_mt,
                            'total_net_weight_mt' => $rakeWeighment->total_net_weight_mt,
                            'total_cc_weight_mt' => $rakeWeighment->total_cc_weight_mt,
                            'total_under_load_mt' => $rakeWeighment->total_under_load_mt,
                            'total_over_load_mt' => $rakeWeighment->total_over_load_mt,
                            'maximum_train_speed_kmph' => $rakeWeighment->maximum_train_speed_kmph,
                            'maximum_weight_mt' => $rakeWeighment->maximum_weight_mt,
                            'pdf_file_path' => $rakeWeighment->pdf_file_path,
                        ],
                    ],
                ], 201);
            }

            $weighment = $historicalImporter->import($pdf, (int) $user->id);

            return response()->json([
                'data' => [
                    'mode' => 'historical',
                    'weighment' => [
                        'id' => $weighment->id,
                        'rake_id' => $weighment->rake_id,
                        'status' => $weighment->status,
                    ],
                ],
            ], 201);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'pdf' => [$e->getMessage()],
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Weighment import failed due to an unexpected error.',
            ], 500);
        }
    }
}
