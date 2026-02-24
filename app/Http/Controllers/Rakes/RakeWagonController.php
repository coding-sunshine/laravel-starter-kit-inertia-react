<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\Http\Controllers\Controller;
use App\Models\Rake;
use App\Models\Wagon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RakeWagonController extends Controller
{
    /**
     * Update the specified wagon
     */
    public function update(Request $request, Rake $rake, Wagon $wagon): RedirectResponse
    {
        $this->authorize('update', $rake);

        // Ensure wagon belongs to this rake
        if ($wagon->rake_id !== $rake->id) {
            abort(403, 'Wagon does not belong to this rake');
        }

        $validated = $request->validate([
            'wagon_type' => ['nullable', 'string', 'max:50'],
            'tare_weight_mt' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'pcc_weight_mt' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'is_unfit' => ['nullable', 'boolean'],
        ]);

        $wagon->update([
            'wagon_type' => $validated['wagon_type'] ?? $wagon->wagon_type,
            'tare_weight_mt' => $validated['tare_weight_mt'] ?? $wagon->tare_weight_mt,
            'pcc_weight_mt' => $validated['pcc_weight_mt'] ?? $wagon->pcc_weight_mt,
            'is_unfit' => $validated['is_unfit'] ?? $wagon->is_unfit,
        ]);

        return redirect()->route('rakes.show', $rake)
            ->with('success', 'Wagon updated.');
    }
}
