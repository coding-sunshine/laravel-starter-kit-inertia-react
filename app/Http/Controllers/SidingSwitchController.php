<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Siding;
use App\Services\SidingContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class SidingSwitchController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $sidingId = $request->input('siding_id');
        $user = $request->user();

        // null = "All sidings" (allowed for super admins / management)
        if ($sidingId === null || $sidingId === '') {
            if (! $user->isManagement()) {
                return back()->withErrors(['siding_id' => __('You do not have access to view all sidings.')]);
            }

            SidingContext::set(null);

            return back()->with('status', __('Switched to all sidings.'));
        }

        $siding = Siding::query()->find($sidingId);
        if (! $siding instanceof Siding) {
            return back()->withErrors(['siding_id' => __('Invalid siding.')]);
        }

        if (! $user->canAccessSiding($siding->id)) {
            return back()->withErrors(['siding_id' => __('You do not have access to that siding.')]);
        }

        SidingContext::set($siding);

        return back()->with('status', __('Switched to :name.', ['name' => $siding->name]));
    }
}
