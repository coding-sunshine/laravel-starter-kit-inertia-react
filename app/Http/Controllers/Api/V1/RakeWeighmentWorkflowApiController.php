<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\RecordManualRakeWeighment;
use App\Actions\UpdateManualRakeWeighment;
use App\Actions\UpdateStockLedger;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreManualRakeWeighmentRequest;
use App\Http\Requests\UpdateManualRakeWeighmentRequest;
use App\Models\AppliedPenalty;
use App\Models\Rake;
use App\Models\RakeCharge;
use App\Models\RakeWeighment;
use App\Models\User;
use App\Services\RakeWeighmentExcelTemplateResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class RakeWeighmentWorkflowApiController extends Controller
{
    public function __construct(
        private UpdateStockLedger $updateStockLedger,
    ) {}

    public function storeManual(
        StoreManualRakeWeighmentRequest $request,
        Rake $rake,
        RecordManualRakeWeighment $recordManual,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->canAccessSiding((int) $rake->siding_id), 403);

        try {
            $weighment = $recordManual->handle($rake, $request->manualPayload(), (int) $user->id);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Manual weighment could not be recorded.',
                'errors' => [
                    'total_net_weight_mt' => [$e->getMessage()],
                ],
            ], 422);
        }

        return response()->json([
            'message' => 'Manual weighment recorded. Upload the document when available.',
            'data' => [
                'rake_weighment_id' => $weighment->id,
                'rake_id' => $weighment->rake_id,
                'status' => $weighment->status,
            ],
        ], 201);
    }

    public function updateManual(
        UpdateManualRakeWeighmentRequest $request,
        Rake $rake,
        RakeWeighment $rakeWeighment,
        UpdateManualRakeWeighment $updateManual,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        if ($rakeWeighment->rake_id !== $rake->id) {
            abort(404);
        }

        abort_unless($user->canAccessSiding((int) $rake->siding_id), 403);

        $canPatchManual = $user->can('bypass-permissions')
            || $user->hasPermissionTo('sections.rakes.upload')
            || $user->hasPermissionTo('sections.weighments.upload');

        abort_unless($canPatchManual, 403);

        try {
            $updated = $updateManual->handle($rake, $rakeWeighment, $request->updatePayload(), (int) $user->id);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Manual weighment could not be updated.',
                'errors' => [
                    'total_net_weight_mt' => [$e->getMessage()],
                ],
            ], 422);
        }

        return response()->json([
            'message' => 'Manual weighment updated.',
            'data' => [
                'rake_weighment_id' => $updated->id,
                'rake_id' => $updated->rake_id,
                'status' => $updated->status,
            ],
        ]);
    }

    public function destroy(Request $request, Rake $rake): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->canAccessSiding((int) $rake->siding_id), 403);

        $canDelete = $user->can('bypass-permissions')
            || $user->hasPermissionTo('sections.weighments.delete')
            || $user->hasPermissionTo('sections.rakes.update')
            || $user->hasPermissionTo('sections.weighments.upload');

        abort_unless($canDelete, 403);

        $userId = (int) $user->id;
        $rake->loadMissing('siding');
        $siding = $rake->siding;
        $weighment = $rake->rakeWeighments()->orderBy('id')->first();

        if ($siding !== null) {
            $this->updateStockLedger->reverseStockLedgerNetForRakeWeighment(
                $siding,
                (int) $rake->id,
                null,
                $userId,
                'Reversal for deleted rake #'.$rake->id.' (legacy ledger rows)',
            );

            if ($weighment !== null) {
                $this->updateStockLedger->reverseStockLedgerNetForRakeWeighment(
                    $siding,
                    (int) $rake->id,
                    (int) $weighment->id,
                    $userId,
                    'Reversal for deleted rake weighment #'.$weighment->id,
                );
            }
        }

        if ($weighment !== null) {
            if ($weighment->pdf_file_path) {
                Storage::disk('public')->delete($weighment->pdf_file_path);
            }

            $weighment->delete();
        }

        AppliedPenalty::query()
            ->where('rake_id', $rake->id)
            ->where('meta->source', 'weighment')
            ->delete();

        $rake->load(['wagons' => fn ($q) => $q->orderBy('wagon_sequence')]);
        foreach ($rake->wagons as $wagon) {
            $seq = (int) ($wagon->wagon_sequence ?? 0);
            $wagon->update([
                'wagon_number' => $seq > 0 ? 'W'.mb_str_pad((string) $seq, 2, '0', STR_PAD_LEFT) : $wagon->wagon_number,
                'wagon_type' => null,
                'tare_weight_mt' => 0.00,
                'pcc_weight_mt' => 0.00,
                'is_unfit' => false,
                'state' => 'pending',
            ]);
        }

        $penaltyCharge = RakeCharge::query()
            ->where('rake_id', $rake->id)
            ->where('charge_type', 'PENALTY')
            ->where('is_actual_charges', false)
            ->first();

        if ($penaltyCharge) {
            $total = AppliedPenalty::query()
                ->where('rake_charge_id', $penaltyCharge->id)
                ->sum('amount');

            $penaltyCharge->update(['amount' => round((float) $total, 2)]);
        }

        return response()->json([
            'message' => 'Rake weighment data deleted.',
            'data' => [
                'rake_id' => $rake->id,
                'rake_weighment_deleted' => $weighment !== null,
            ],
        ]);
    }

    public function downloadTemplateXlsx(
        Request $request,
        RakeWeighmentExcelTemplateResolver $templateResolver,
    ): BinaryFileResponse|JsonResponse {
        $validated = $request->validate([
            'rake_id' => ['required', 'integer', 'exists:rakes,id'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $rake = Rake::query()
            ->with('siding:id,name,code,station_code')
            ->findOrFail((int) $validated['rake_id']);

        abort_unless($user->canAccessSiding((int) $rake->siding_id), 403);

        $resolved = $templateResolver->resolve($rake);

        if (isset($resolved['error'])) {
            $message = match ($resolved['error']) {
                RakeWeighmentExcelTemplateResolver::ERROR_NO_SIDING => 'Assign a siding to this rake before downloading the template.',
                RakeWeighmentExcelTemplateResolver::ERROR_UNKNOWN_SIDING => 'Weighment Excel template is only available for Pakur, Dumka, or Kurwa sidings.',
                RakeWeighmentExcelTemplateResolver::ERROR_FILE_MISSING => 'The weighment template file is missing on the server. Please contact support.',
                default => 'Unable to download the weighment template.',
            };

            return response()->json([
                'message' => $message,
            ], 422);
        }

        return response()->download($resolved['absolute_path'], $resolved['download_basename']);
    }
}
