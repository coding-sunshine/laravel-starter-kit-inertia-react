<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

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
use App\Services\RakeWeighmentPdfImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Throwable;

final class RakeWeighmentController extends Controller
{
    public function __construct(
        private UpdateStockLedger $updateStockLedger,
    ) {}

    public function store(Request $request, Rake $rake, RakeWeighmentPdfImporter $importer): RedirectResponse
    {
        // $this->authorize('update', $rake);

        $validated = $request->validate([
            'weighment_pdf' => ['required', 'file', 'mimes:pdf,xlsx,xls', 'max:20480'],
        ]);

        try {
            $file = $validated['weighment_pdf'];
            $extension = mb_strtolower((string) $file->getClientOriginalExtension());
            $isXlsx = in_array($extension, ['xlsx', 'xls'], true);

            if ($isXlsx) {
                $importer->importForRakeFromXlsx($rake, $file, (int) $request->user()->id);
            } else {
                $importer->importForRake($rake, $file, (int) $request->user()->id);
            }
        } catch (InvalidArgumentException $e) {
            Log::warning('RakeWeighmentController: import validation failed', [
                'rake_id' => $rake->id,
                'message' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['weighment_pdf' => $e->getMessage()])
                ->withInput();
        } catch (Throwable $e) {
            Log::error('RakeWeighmentController: import failed', [
                'rake_id' => $rake->id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['weighment_pdf' => 'Weighment import failed due to an unexpected error. Please check logs.'])
                ->withInput();
        }

        // After successful upload, stay on the rake page so the user sees
        // the updated weighment data in the Rake workflow itself.
        return to_route('rakes.show', $rake)
            ->with('success', 'Weighment recorded.');
    }

    public function storeManual(
        StoreManualRakeWeighmentRequest $request,
        Rake $rake,
        RecordManualRakeWeighment $recordManual,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        if (! $user->canAccessSiding((int) $rake->siding_id)) {
            abort(403);
        }

        try {
            $recordManual->handle($rake, $request->manualPayload(), (int) $user->id);
        } catch (InvalidArgumentException $e) {
            return back()
                ->withErrors(['total_net_weight_mt' => $e->getMessage()])
                ->withInput();
        }

        return to_route('rakes.show', $rake)
            ->with('success', 'Manual weighment recorded. Upload the document when available.');
    }

    public function updateManual(
        UpdateManualRakeWeighmentRequest $request,
        Rake $rake,
        RakeWeighment $rakeWeighment,
        UpdateManualRakeWeighment $updateManual,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        if ($rakeWeighment->rake_id !== $rake->id) {
            abort(404);
        }

        if (! $user->canAccessSiding((int) $rake->siding_id)) {
            abort(403);
        }

        try {
            $updateManual->handle($rake, $rakeWeighment, $request->updatePayload(), (int) $user->id);
        } catch (InvalidArgumentException $e) {
            return back()
                ->withErrors(['total_net_weight_mt' => $e->getMessage()])
                ->withInput();
        }

        return to_route('rakes.show', $rake)
            ->with('success', 'Manual weighment updated.');
    }

    /**
     * Removes all weighment data for this rake and restores siding stock.
     *
     * Business rule: a rake has at most one weighment (second upload is blocked until this is deleted).
     * Stock reversal and file/row removal both use that single `rake_weighments` row (plus legacy
     * `stock_ledgers` rows with null `rake_weighment_id` for reversal only).
     */
    public function destroy(Rake $rake): RedirectResponse
    {
        $userId = (int) auth()->id();

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

        // 🔹 Delete penalties
        AppliedPenalty::query()
            ->where('rake_id', $rake->id)
            ->where('meta->source', 'weighment')
            ->delete();

        // Reset wagons back to placeholders (keep wagon loading rows intact).
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

        // 🔹 Update penalty charge
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

        return to_route('rakes.show', $rake)
            ->with('success', 'Rake weighment data deleted.');
    }
}
