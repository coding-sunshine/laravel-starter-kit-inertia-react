<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\DataTables\RakeDataTable;
use App\Http\Controllers\Controller;
use App\Models\Rake;
use App\Models\SectionTimer;
use App\Models\Siding;
use App\Models\Wagon;
use App\Models\WagonType;
use Closure;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
            'rakeWeighments' => fn ($q) => $q->whereNotNull('pdf_file_path'),
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

        $wagonTypes = WagonType::query()
            ->orderBy('code')
            ->get([
                'id',
                'code',
                'full_form',
                'typical_use',
                'loading_method',
                'carrying_capacity_min_mt',
                'carrying_capacity_max_mt',
                'gross_tare_weight_mt',
                'default_pcc_weight_mt',
            ]);

        $loadingSection = SectionTimer::query()
            ->where('section_name', 'loading')
            ->first();

        $rakeArray = $rake->toArray();
        $rakeArray['loading_warning_minutes'] = $loadingSection?->warning_minutes;
        $rakeArray['loading_section_free_minutes'] = $loadingSection?->free_minutes ?? 180;

        // Build weighments for WeighmentWorkflow: only PDF-origin records (rake_wagon_weighments
        // from load flow are not shown here; full wagon data is on /weighments/{id})
        $rakeArray['weighments'] = collect($rake->rakeWeighments ?? [])
            ->filter(fn ($rw) => ! empty($rw->pdf_file_path))
            ->map(function ($rw) {
                return [
                    'id' => $rw->id,
                    'weighment_time' => $rw->gross_weighment_datetime?->toIso8601String(),
                    'total_weight_mt' => $rw->total_net_weight_mt,
                    'status' => $rw->status,
                    'train_speed_kmph' => $rw->maximum_train_speed_kmph,
                    'attempt_no' => $rw->attempt_no,
                ];
            })
            ->values()
            ->all();

        // Normalize relation keys for frontend (camelCase expected)
        if (array_key_exists('guard_inspections', $rakeArray)) {
            $rakeArray['guardInspections'] = $rakeArray['guard_inspections'];
        }

        if (array_key_exists('wagon_loadings', $rakeArray)) {
            $rakeArray['wagonLoadings'] = $rakeArray['wagon_loadings'];
        }

        return Inertia::render('rakes/show', [
            'rake' => $rakeArray,
            'wagonTypes' => $wagonTypes,
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
            'rake_number' => [
                'nullable',
                'string',
                'max:100',
                function (string $attribute, mixed $value, Closure $fail) use ($rake): void {
                    $trimmed = $value !== null && mb_trim((string) $value) !== '' ? mb_trim((string) $value) : null;

                    if ($trimmed === null || $trimmed === $rake->rake_number) {
                        return;
                    }

                    $existsInMonth = Rake::query()
                        ->where('rake_number', $trimmed)
                        ->whereYear('created_at', now()->year)
                        ->whereMonth('created_at', now()->month)
                        ->whereKeyNot($rake->getKey())
                        ->exists();

                    if ($existsInMonth) {
                        $fail('This rake number is already in use this month.');
                    }
                },
            ],
            'rake_type' => ['nullable', 'string', 'max:50'],
            'wagon_count' => ['nullable', 'integer', 'min:0'],
            'free_time_minutes' => ['nullable', 'integer', 'min:0'],
            'rr_expected_date' => ['nullable', 'date'],
            'placement_time' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $rake->update([
            'rake_number' => array_key_exists('rake_number', $validated) && mb_trim((string) $validated['rake_number']) !== ''
                ? mb_trim((string) $validated['rake_number'])
                : $rake->rake_number,
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

    public function startLoadingTimer(Request $request, Rake $rake): RedirectResponse|JsonResponse
    {
        $freeMinutes = SectionTimer::query()
            ->where('section_name', 'loading')
            ->value('free_minutes') ?? 180;

        $rake->update([
            'loading_start_time' => now(),
            'loading_end_time' => null,
            'loading_free_minutes' => $freeMinutes,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'loading_start_time' => $rake->loading_start_time?->toIso8601String(),
                'loading_free_minutes' => $rake->loading_free_minutes,
            ]);
        }

        $hours = $freeMinutes >= 60 && $freeMinutes % 60 === 0
            ? (int) ($freeMinutes / 60)
            : null;
        $message = $hours !== null
            ? "Loading timer started for {$hours} hour(s)."
            : "Loading timer started for {$freeMinutes} minutes.";

        return to_route('rakes.show', $rake)
            ->with('success', $message);
    }

    public function resetLoadingTimer(Request $request, Rake $rake): RedirectResponse|JsonResponse
    {
        $rake->update([
            'loading_start_time' => null,
            'loading_end_time' => null,
            'loading_free_minutes' => null,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'loading_start_time' => null,
                'loading_end_time' => null,
            ]);
        }

        return to_route('rakes.show', $rake)
            ->with('success', 'Loading timer reset.');
    }

    public function stopLoadingTimer(Request $request, Rake $rake): RedirectResponse|JsonResponse
    {
        $rake->update([
            'loading_end_time' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'loading_start_time' => $rake->loading_start_time?->toIso8601String(),
                'loading_end_time' => $rake->loading_end_time?->toIso8601String(),
                'loading_free_minutes' => $rake->loading_free_minutes,
            ]);
        }

        return to_route('rakes.show', $rake)
            ->with('success', 'Loading timer stopped.');
    }

    public function updateLoadingTimes(Request $request, Rake $rake): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'loading_start_time' => ['nullable', 'date'],
            'loading_end_time' => ['nullable', 'date', 'after_or_equal:loading_start_time'],
        ]);

        $freeMinutes = SectionTimer::query()
            ->where('section_name', 'loading')
            ->value('free_minutes') ?? 180;

        $start = array_key_exists('loading_start_time', $validated) && $validated['loading_start_time'] !== null
            ? new DateTimeImmutable($validated['loading_start_time'])
            : null;

        $end = array_key_exists('loading_end_time', $validated) && $validated['loading_end_time'] !== null
            ? new DateTimeImmutable($validated['loading_end_time'])
            : null;

        $rake->update([
            'loading_start_time' => $start,
            'loading_end_time' => $end,
            'loading_date' => $start ? $start->format('Y-m-d') : null,
            'loading_free_minutes' => $freeMinutes,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'loading_start_time' => $rake->loading_start_time?->toIso8601String(),
                'loading_end_time' => $rake->loading_end_time?->toIso8601String(),
                'loading_date' => $rake->loading_date?->toDateString(),
            ]);
        }

        return to_route('rakes.show', $rake)
            ->with('success', 'Loading times updated.');
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
