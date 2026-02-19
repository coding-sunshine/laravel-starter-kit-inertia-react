<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\Http\Controllers\Controller;
use App\Models\Rake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class RakeTxrController extends Controller
{
    public function update(Request $request, Rake $rake): RedirectResponse
    {
        $this->authorize('update', $rake);

        $validated = $request->validate([
            'inspection_time' => ['required', 'date'],
            'state' => ['required', 'string', Rule::in(['pending', 'approved', 'rejected'])],
            'unfit_wagons_count' => ['required', 'integer', 'min:0'],
            'unfit_wagon_numbers' => ['nullable', 'string', 'max:500'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $txr = $rake->txr;
        if ($txr === null) {
            $rake->txr()->create([
                ...$validated,
                'created_by' => $request->user()->id,
            ]);
        } else {
            $txr->update([
                ...$validated,
                'updated_by' => $request->user()->id,
            ]);
        }

        return redirect()
            ->route('rakes.show', $rake)
            ->with('success', 'TXR updated.');
    }
}
