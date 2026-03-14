<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\RetargetingPixel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class RetargetingPixelController extends Controller
{
    public function index(): Response
    {
        $pixels = RetargetingPixel::query()
            ->where('organization_id', tenant('id'))
            ->latest()
            ->paginate(15);

        return Inertia::render('retargeting-pixels/index', [
            'pixels' => $pixels,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'platform' => 'required|string|in:facebook,google,tiktok,linkedin,twitter',
            'pixel_id' => 'required|string|max:255',
            'script_tag' => 'nullable|string',
            'events' => 'nullable|array',
        ]);

        RetargetingPixel::create([
            'organization_id' => tenant('id'),
            ...$validated,
        ]);

        return redirect()->route('retargeting-pixels.index')->with('success', 'Pixel added.');
    }

    public function update(Request $request, RetargetingPixel $retargetingPixel): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|string|in:active,paused',
        ]);

        $retargetingPixel->update($validated);

        return redirect()->route('retargeting-pixels.index')->with('success', 'Pixel updated.');
    }

    public function destroy(RetargetingPixel $retargetingPixel): RedirectResponse
    {
        $retargetingPixel->delete();

        return redirect()->route('retargeting-pixels.index')->with('success', 'Pixel removed.');
    }
}
