<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\Http\Controllers\Controller;
use App\Models\Rake;
use App\Models\Wagon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class RakeWagonController extends Controller
{
    /**
     * Update the specified wagon
     */
    public function update(Request $request, Rake $rake, Wagon $wagon): RedirectResponse|JsonResponse
    {
        // $this->authorize('update', $rake);

        // Ensure wagon belongs to this rake
        if ($wagon->rake_id !== $rake->id) {
            abort(403, 'Wagon does not belong to this rake');
        }

        $validated = $request->validate([
            'wagon_number' => ['nullable', 'string', 'max:20'],
            'wagon_type' => ['nullable', 'string', 'max:50'],
            'tare_weight_mt' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'pcc_weight_mt' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'is_unfit' => ['nullable', 'boolean'],
        ]);

        $wagon->update([
            'wagon_number' => $validated['wagon_number'] ?? $wagon->wagon_number,
            'wagon_type' => $validated['wagon_type'] ?? $wagon->wagon_type,
            'tare_weight_mt' => $validated['tare_weight_mt'] ?? $wagon->tare_weight_mt,
            'pcc_weight_mt' => $validated['pcc_weight_mt'] ?? $wagon->pcc_weight_mt,
            'is_unfit' => $validated['is_unfit'] ?? $wagon->is_unfit,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['wagon' => $wagon->fresh()]);
        }

        return redirect()->route('rakes.show', $rake)
            ->with('success', 'Wagon updated.');
    }

    /**
     * Bulk update wagons for a rake in a single request.
     */
    public function bulkUpdate(Request $request, Rake $rake): JsonResponse
    {
        // $this->authorize('update', $rake);

        $validated = $request->validate([
            'wagons' => ['required', 'array', 'min:1'],
            'wagons.*.id' => ['required', 'integer', Rule::exists('wagons', 'id')],
            'wagons.*.wagon_number' => ['nullable', 'string', 'max:20'],
            'wagons.*.wagon_type' => ['nullable', 'string', 'max:50'],
            'wagons.*.tare_weight_mt' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'wagons.*.pcc_weight_mt' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
        ]);

        $updated = [];

        foreach ($validated['wagons'] as $wagonData) {
            /** @var Wagon $wagon */
            $wagon = $rake->wagons()->whereKey($wagonData['id'])->firstOrFail();

            $wagon->update([
                'wagon_number' => $wagonData['wagon_number'] ?? $wagon->wagon_number,
                'wagon_type' => $wagonData['wagon_type'] ?? $wagon->wagon_type,
                'tare_weight_mt' => $wagonData['tare_weight_mt'] ?? $wagon->tare_weight_mt,
                'pcc_weight_mt' => $wagonData['pcc_weight_mt'] ?? $wagon->pcc_weight_mt,
            ]);

            $updated[] = $wagon->fresh();
        }

        return response()->json([
            'wagons' => $updated,
        ]);
    }
}
