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
    public function start(Request $request, Rake $rake): RedirectResponse
    {
        // $this->authorize('update', $rake);

        if ($rake->txr !== null) {
            return to_route('rakes.show', $rake)
                ->with('error', 'TXR has already been started for this rake.');
        }

        $rake->txr()->create([
            'inspection_time' => now(),
            'status' => 'in_progress',
            'created_by' => $request->user()->id,
        ]);

        return to_route('rakes.show', $rake)
            ->with('success', 'TXR started successfully.');
    }

    public function end(Request $request, Rake $rake): RedirectResponse
    {
        // $this->authorize('update', $rake);

        $txr = $rake->txr;
        if ($txr === null) {
            return to_route('rakes.show', $rake)
                ->with('error', 'No TXR found for this rake.');
        }

        if ($txr->inspection_end_time !== null) {
            return to_route('rakes.show', $rake)
                ->with('error', 'TXR has already been ended.');
        }

        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $txr->update([
            'inspection_end_time' => now(),
            'status' => 'completed',
            ...$validated,
            'updated_by' => $request->user()->id,
        ]);

        return to_route('rakes.show', $rake)
            ->with('success', 'TXR completed successfully.');
    }

    public function update(Request $request, Rake $rake): RedirectResponse
    {
        // $this->authorize('update', $rake);

        $validated = $request->validate([
            'inspection_time' => ['required', 'date'],
            'inspection_end_time' => ['nullable', 'date'],
            'status' => ['required', 'string', Rule::in(['pending', 'in_progress', 'completed', 'approved', 'rejected'])],
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

        return to_route('rakes.show', $rake)
            ->with('success', 'TXR updated.');
    }
}
