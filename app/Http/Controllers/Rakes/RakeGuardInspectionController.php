<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\Http\Controllers\Controller;
use App\Models\Rake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RakeGuardInspectionController extends Controller
{
    public function store(Request $request, Rake $rake): RedirectResponse
    {
        $this->authorize('update', $rake);

        $validated = $request->validate([
            'inspection_time' => ['required', 'date'],
            'is_approved' => ['required', 'boolean'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $inspection = $rake->guardInspection;
        if ($inspection === null) {
            $rake->guardInspection()->create([
                ...$validated,
                'created_by' => $request->user()->id,
            ]);
        } else {
            $inspection->update([
                ...$validated,
                'updated_by' => $request->user()->id,
            ]);
        }

        return to_route('rakes.show', $rake)
            ->with('success', 'Guard inspection recorded.');
    }
}
