<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\Http\Controllers\Controller;
use App\Models\Rake;
use App\Models\Wagon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        DB::transaction(function () use ($txr, $rake, $validated, $request): void {
            $unfitWagonIds = $txr->wagonUnfitLogs()->pluck('wagon_id')->all();

            Wagon::where('rake_id', $rake->id)->update(['is_unfit' => false]);
            if (! empty($unfitWagonIds)) {
                Wagon::whereIn('id', $unfitWagonIds)->update(['is_unfit' => true]);
            }

            $txr->update([
                'inspection_end_time' => now(),
                'status' => 'completed',
                ...$validated,
                'updated_by' => $request->user()->id,
            ]);
        });

        return to_route('rakes.show', $rake)
            ->with('success', 'TXR completed successfully.');
    }

    public function storeUnfitLogs(Request $request, Rake $rake): RedirectResponse
    {
        // $this->authorize('update', $rake);

        $txr = $rake->txr;
        if ($txr === null) {
            return to_route('rakes.show', $rake)
                ->with('error', 'No TXR found. Start TXR first.');
        }

        if ($txr->status !== 'in_progress') {
            return to_route('rakes.show', $rake)
                ->with('error', 'Unfit logs can only be saved while TXR is in progress.');
        }

        $validated = $request->validate([
            'unfit_logs' => ['required', 'array'],
            'unfit_logs.*.wagon_id' => ['required', 'exists:wagons,id'],
            'unfit_logs.*.reason' => ['nullable', 'string', 'max:2000'],
            'unfit_logs.*.marking_method' => ['nullable', 'string', 'max:100'],
            'unfit_logs.*.marked_at' => ['nullable', 'date'],
        ]);

        $rakeWagonIds = $rake->wagons()->pluck('id')->all();

        DB::transaction(function () use ($txr, $validated, $rakeWagonIds, $request): void {
            $txr->wagonUnfitLogs()->delete();

            foreach ($validated['unfit_logs'] as $log) {
                $wagonId = (int) $log['wagon_id'];
                if (! in_array($wagonId, $rakeWagonIds, true)) {
                    continue;
                }

                $txr->wagonUnfitLogs()->create([
                    'wagon_id' => $wagonId,
                    'reason' => $log['reason'] ?? null,
                    'marking_method' => $log['marking_method'] ?? null,
                    'marked_at' => isset($log['marked_at']) ? $log['marked_at'] : now(),
                    'created_by' => $request->user()->id,
                ]);
            }
        });

        return to_route('rakes.show', $rake)
            ->with('success', 'Unfit wagon details saved.');
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
