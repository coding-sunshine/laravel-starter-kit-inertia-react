<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\Http\Controllers\Controller;
use App\Models\PowerPlant;
use App\Models\PowerPlantReceipt;
use App\Models\Rake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RakePowerPlantReceiptController extends Controller
{
    public function store(Request $request, Rake $rake): RedirectResponse
    {
        // $this->authorize('update', $rake);

        $validated = $request->validate([
            'receipt_date' => ['required', 'date'],
            'status' => ['required', 'string', 'in:pending,reached,verified,discrepancy'],
            'weight_mt' => ['required', 'numeric', 'min:0'],
            'rr_reference' => ['nullable', 'string', 'max:50'],
            'receipt_pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'power_plant_id' => ['nullable', 'integer', 'exists:power_plants,id'],
        ]);

        $powerPlantId = array_key_exists('power_plant_id', $validated) ? $validated['power_plant_id'] : null;

        if ($rake->is_diverted) {
            if ($powerPlantId === null) {
                return back()
                    ->withErrors(['power_plant_id' => 'Power plant is required for diverted rakes.'])
                    ->withInput();
            }
        } else {
            if ($powerPlantId === null) {
                $destinationCode = $rake->destination_code;
                if ($destinationCode === null || mb_trim($destinationCode) === '') {
                    return back()
                        ->withErrors(['power_plant_id' => 'Destination code is missing for this rake. Please enable diverted mode and select a power plant.'])
                        ->withInput();
                }

                $powerPlantId = PowerPlant::query()
                    ->where('code', $destinationCode)
                    ->value('id');

                if ($powerPlantId === null) {
                    return back()
                        ->withErrors(['power_plant_id' => "No power plant found for destination code '{$destinationCode}'. Please enable diverted mode and select a power plant."])
                        ->withInput();
                }
            }
        }

        $existing = PowerPlantReceipt::query()
            ->where('rake_id', $rake->id)
            ->where('power_plant_id', (int) $powerPlantId)
            ->first();

        if ($existing !== null) {
            return back()
                ->withErrors(['power_plant_id' => 'A receipt already exists for this power plant. Delete it first if you need to re-upload.'])
                ->withInput();
        }

        $existing = PowerPlantReceipt::query()
            ->where('rake_id', $rake->id)
            ->where('power_plant_id', (int) $powerPlantId)
            ->first();

        if ($existing !== null) {
            return back()
                ->withErrors([
                    'power_plant_id' => 'A receipt already exists for this power plant. Delete it first if you need to re-upload.',
                ])
                ->withInput();
        }

        $receipt = PowerPlantReceipt::query()->create([
            'rake_id' => $rake->id,
            'power_plant_id' => (int) $powerPlantId,
            'receipt_date' => $validated['receipt_date'],
            'weight_mt' => $validated['weight_mt'],
            'rr_reference' => $validated['rr_reference'] ?? null,
            'status' => $validated['status'],
            'created_by' => $request->user()->id,
        ]);

        $receipt->addMediaFromRequest('receipt_pdf')
            ->toMediaCollection('power_plant_receipt_pdf');

        return to_route('rakes.show', $rake)
            ->with('success', 'Power plant receipt saved.');
    }

    public function destroy(Request $request, Rake $rake, PowerPlantReceipt $receipt): RedirectResponse
    {
        // $this->authorize('update', $rake);

        if ((int) $receipt->rake_id !== (int) $rake->id) {
            abort(404);
        }

        $receipt->clearMediaCollection('power_plant_receipt_pdf');
        $receipt->delete();

        return to_route('rakes.show', $rake)
            ->with('success', 'Power plant receipt deleted.');
    }
}
