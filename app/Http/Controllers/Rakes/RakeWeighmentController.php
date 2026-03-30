<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\Actions\UpdateStockLedger;
use App\Http\Controllers\Controller;
use App\Models\AppliedPenalty;
use App\Models\Rake;
use App\Models\RakeCharge;
use App\Models\StockLedger;
use App\Services\RakeWeighmentPdfImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Throwable;
use Illuminate\Support\Facades\DB;

final class RakeWeighmentController extends Controller
{
    public function __construct(
        private UpdateStockLedger $updateStockLedger,
    ) {}

    public function store(Request $request, Rake $rake, RakeWeighmentPdfImporter $importer): RedirectResponse
    {
        // $this->authorize('update', $rake);

        $validated = $request->validate([
            'weighment_pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        try {
            $importer->importForRake($rake, $validated['weighment_pdf'], (int) $request->user()->id);
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

    public function destroy(Rake $rake): RedirectResponse
    {
        $userId = (int) auth()->id();

        // Get the single dispatch entry for this rake
        $dispatch = StockLedger::query()
            ->where('rake_id', $rake->id)
            ->where('transaction_type', 'dispatch')
            ->latest('id')
            ->first();

        if ($dispatch) {

            
                DB::transaction(function () use ($dispatch, $rake, $userId) {

                    $sidingId = $dispatch->siding_id;

                    if (! $sidingId) {
                        return;
                    }
                    $alreadyReversed = StockLedger::query()
                        ->where('reference_number', 'REV-DISP-'.$dispatch->id)
                        ->lockForUpdate()
                        ->exists();

                    if ($alreadyReversed) {
                        return;
                    }

                    // reverse dispatch (negative → positive)
                    $reverseQty = abs((float) $dispatch->quantity_mt);

                    //  lock latest ledger row
                    $lastLedger = StockLedger::query()
                        ->where('siding_id', $sidingId)
                        ->lockForUpdate()
                        ->latest('id')
                        ->first();

                    $opening = $lastLedger
                        ? (float) $lastLedger->closing_balance_mt
                        : SidingOpeningBalance::getOpeningBalanceForSiding($sidingId);

                    $closing = round($opening + $reverseQty, 2);

                    StockLedger::create([
                        'siding_id' => $sidingId,
                        'transaction_type' => 'correction',
                        'rake_id' => $rake->id,
                        'quantity_mt' => $reverseQty, //  positive
                        'opening_balance_mt' => $opening,
                        'closing_balance_mt' => $closing,
                        'reference_number' => 'REV-DISP-'.$dispatch->id,
                        'remarks' => 'Reversal for deleted rake #'.$rake->id,
                        'created_by' => $userId ?: null,
                    ]);

                    DB::afterCommit(function () use ($sidingId, $closing) {
                        event(new \App\Events\CoalStockUpdated($sidingId, $closing));
                    });
                });
            
        }

        // 🔹 Delete weighment files + records
        $weighments = $rake->rakeWeighments()->get();

        foreach ($weighments as $weighment) {
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
