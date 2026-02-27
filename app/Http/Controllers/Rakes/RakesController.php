<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\DataTables\RakeDataTable;
use App\Http\Controllers\Controller;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\Wagon;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class RakesController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('rakes/index', [
            'tableData' => RakeDataTable::makeTable($request),
        ]);
    }

    public function show(Request $request, Rake $rake): Response
    {
        // $this->authorize('view', $rake);

        $rake->load([
            'siding:id,name,code',
            'siding.loaders:id,siding_id,loader_name,code',
            'wagons',
            'txr.wagonUnfitLogs.wagon:id,wagon_number,wagon_sequence,wagon_type',
            'wagonLoadings.wagon:id,wagon_number,wagon_sequence,wagon_type,pcc_weight_mt',
            'wagonLoadings.loader:id,loader_name,code',
            'guardInspections',
            'rrDocument',
            'penalties',
        ]);

        $demurrageRemainingMinutes = null;
        if (
            $rake->state === 'loading'
            && $rake->placement_time
            && $rake->loading_free_minutes !== null
        ) {
            $end = $rake->placement_time->copy()->addMinutes((int) $rake->loading_free_minutes);
            $demurrageRemainingMinutes = max(0, (int) now()->diffInMinutes($end, false));
        }

        return Inertia::render('rakes/show', [
            'rake' => $rake,
            'demurrageRemainingMinutes' => $demurrageRemainingMinutes,
            'demurrage_rate_per_mt_hour' => config('rrmcs.demurrage_rate_per_mt_hour', 50),
        ]);
    }

    /**
     * Generate wagons for a rake based on its wagon count
     */
    public function generateWagons(Request $request, Rake $rake)
    {
        // $this->authorize('update', $rake);

        // Check if wagons already exist
        if ($rake->wagons()->count() > 0) {
            return redirect()->route('rakes.show', $rake)->with('error', 'Wagons already exist for this rake');
        }

        // Generate wagons based on wagon_count
        $wagonCount = $rake->wagon_count;
        if ($wagonCount <= 0) {
            return redirect()->route('rakes.show', $rake)->with('error', 'Rake has no wagon count specified');
        }

        // Clear existing wagons (if any) and create new ones
        $rake->wagons()->delete();

        for ($i = 1; $i <= $wagonCount; $i++) {
            $wagon = new Wagon;
            $wagon->rake_id = $rake->id;
            $wagon->wagon_number = "W{$i}"; // W1, W2, W3, etc.
            $wagon->wagon_sequence = $i;
            $wagon->state = 'pending';
            $wagon->save();
        }

        return redirect()->route('rakes.show', $rake)->with('success', "Successfully generated {$wagonCount} wagons");
    }

    /**
     * Show the form for editing a rake
     */
    public function edit(Request $request, Rake $rake): Response
    {
        // $this->authorize('update', $rake);

        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('rakes/edit', [
            'rake' => $rake,
            'sidings' => $sidings,
        ]);
    }

    /**
     * Update the specified rake
     */
    public function update(Request $request, Rake $rake): RedirectResponse
    {
        // $this->authorize('update', $rake);

        $validated = $request->validate([
            'rake_type' => ['nullable', 'string', 'max:50'],
            'wagon_count' => ['nullable', 'integer', 'min:0'],
            'free_time_minutes' => ['nullable', 'integer', 'min:0'],
            'rr_expected_date' => ['nullable', 'date'],
            'placement_time' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $rake->update([
            'rake_type' => $validated['rake_type'] ?? $rake->rake_type,
            'wagon_count' => $validated['wagon_count'] ?? $rake->wagon_count,
            'loading_free_minutes' => $validated['free_time_minutes'] ?? $rake->loading_free_minutes,
            'rr_expected_date' => $validated['rr_expected_date'] ?? $rake->rr_expected_date,
            'placement_time' => $validated['placement_time'] ? new DateTimeImmutable($validated['placement_time']) : $rake->placement_time,
            'updated_by' => $request->user()->id,
        ]);

        // Generate wagons if rake has no wagons and wagon_count is specified
        if ($rake->wagons()->count() === 0 && $rake->wagon_count > 0) {
            for ($i = 1; $i <= $rake->wagon_count; $i++) {
                $wagon = new Wagon;
                $wagon->rake_id = $rake->id;
                $wagon->wagon_number = "W{$i}";
                $wagon->wagon_sequence = $i;
                $wagon->state = 'pending';
                $wagon->save();
            }
        }

        return to_route('rakes.show', $rake)
            ->with('success', 'Rake updated successfully.');
    }

    /**
     * Delete a rake if it has no wagons
     */
    public function destroy(Request $request, Rake $rake): RedirectResponse
    {
        // $this->authorize('delete', $rake);

        // Check if rake has wagons
        if ($rake->wagons()->count() > 0) {
            return to_route('rakes.show', $rake)
                ->with('error', 'Cannot delete rake with wagons. Delete all wagons first.');
        }

        // Check if rake has TXR
        if ($rake->txr) {
            return to_route('rakes.show', $rake)
                ->with('error', 'Cannot delete rake with TXR records.');
        }

        $rakeNumber = $rake->rake_number;
        $rake->delete();

        return to_route('rakes.index')
            ->with('success', "Rake {$rakeNumber} deleted successfully.");
    }
}
