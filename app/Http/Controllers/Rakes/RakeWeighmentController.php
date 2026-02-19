<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\Http\Controllers\Controller;
use App\Models\Rake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RakeWeighmentController extends Controller
{
    public function store(Request $request, Rake $rake): RedirectResponse
    {
        $this->authorize('update', $rake);

        $validated = $request->validate([
            'weighment_time' => ['required', 'date'],
            'total_weight_mt' => ['required', 'numeric', 'min:0'],
            'average_wagon_weight_mt' => ['nullable', 'numeric', 'min:0'],
            'weighment_status' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $rake->weighments()->create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('rakes.show', $rake)
            ->with('success', 'Weighment recorded.');
    }
}
