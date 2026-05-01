<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sidings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sidings\QuickPlacementRequest;
use App\Models\Rake;
use App\Models\Siding;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class QuickPlacementController extends Controller
{
    public function show(Siding $siding): Response
    {
        $rakes = Rake::query()
            ->where('siding_id', $siding->id)
            ->whereNull('dispatch_time')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'rake_number', 'placement_time', 'loading_end_time']);

        return Inertia::render('sidings/quick-placement', [
            'siding' => ['id' => $siding->id, 'name' => $siding->name],
            'rakes' => $rakes,
        ]);
    }

    public function store(QuickPlacementRequest $request, Siding $siding): RedirectResponse
    {
        /** @var Rake $rake */
        $rake = Rake::query()->findOrFail($request->validated('rake_id'));
        $occurredAt = $request->validated('occurred_at') ?? now();

        if ($request->validated('event') === 'placed') {
            $rake->update(['placement_time' => $occurredAt]);
        } else {
            $rake->update(['loading_end_time' => $occurredAt]);
        }

        return back()->with('success', 'Recorded.');
    }
}
