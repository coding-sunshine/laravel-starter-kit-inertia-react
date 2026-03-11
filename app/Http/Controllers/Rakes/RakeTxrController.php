<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\Actions\EndTxrAction;
use App\Actions\StartTxrAction;
use App\Actions\StoreTyrUnfitLogsAction;
use App\Actions\UpdateTxrHeaderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTxrRequest;
use App\Models\Rake;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class RakeTxrController extends Controller
{
    public function __construct(
        private StartTxrAction $startTxr,
        private EndTxrAction $endTxr,
        private UpdateTxrHeaderAction $updateTxrHeader,
        private StoreTyrUnfitLogsAction $storeTyrUnfitLogs,
    ) {}

    public function start(Request $request, Rake $rake): RedirectResponse
    {
        try {
            $this->startTxr->handle($rake, (int) $request->user()->id);
        } catch (InvalidArgumentException $e) {
            return to_route('rakes.show', $rake)->with('error', $e->getMessage());
        }

        return to_route('rakes.show', $rake)->with('success', 'TXR started successfully.');
    }

    public function end(Request $request, Rake $rake): RedirectResponse
    {
        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->endTxr->handle($rake, $validated, (int) $request->user()->id);
        } catch (InvalidArgumentException $e) {
            return to_route('rakes.show', $rake)->with('error', $e->getMessage());
        }

        return to_route('rakes.show', $rake)->with('success', 'TXR completed successfully.');
    }

    public function storeUnfitLogs(Request $request, Rake $rake): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'unfit_logs' => ['required', 'array'],
            'unfit_logs.*.wagon_id' => ['nullable', 'exists:wagons,id'],
            'unfit_logs.*.reason' => ['nullable', 'string', 'max:2000'],
            'unfit_logs.*.reason_unfit' => ['nullable', 'string', 'max:2000'],
            'unfit_logs.*.marking_method' => ['nullable', 'string', 'max:100'],
            'unfit_logs.*.marked_at' => ['nullable', 'date'],
        ]);

        $unfitLogs = [];
        foreach ($validated['unfit_logs'] as $log) {
            $wagonId = isset($log['wagon_id']) ? (int) $log['wagon_id'] : 0;
            if ($wagonId === 0) {
                continue;
            }
            $unfitLogs[] = [
                'wagon_id' => $wagonId,
                'reason' => $log['reason'] ?? null,
                'reason_unfit' => $log['reason_unfit'] ?? null,
                'marking_method' => $log['marking_method'] ?? null,
                'marked_at' => $log['marked_at'] ?? null,
            ];
        }

        try {
            $this->storeTyrUnfitLogs->handle($rake, $unfitLogs, (int) $request->user()->id);
        } catch (InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 400);
            }

            return to_route('rakes.show', $rake)->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            $rake->load('txr.wagonUnfitLogs.wagon:id,wagon_number,wagon_sequence,wagon_type');
            $logs = $rake->txr->wagonUnfitLogs->map(fn ($log) => [
                'id' => $log->id,
                'wagon_id' => $log->wagon_id,
                'reason' => $log->reason,
                'reason_unfit' => $log->reason,
                'marking_method' => $log->marking_method,
                'marked_at' => $log->marked_at?->toIso8601String(),
                'wagon' => $log->wagon ? [
                    'id' => $log->wagon->id,
                    'wagon_number' => $log->wagon->wagon_number,
                    'wagon_sequence' => $log->wagon->wagon_sequence,
                    'wagon_type' => $log->wagon->wagon_type,
                ] : null,
            ]);

            return response()->json(['wagonUnfitLogs' => $logs]);
        }

        return to_route('rakes.show', $rake)->with('success', 'Unfit wagon details saved.');
    }

    public function update(UpdateTxrRequest $request, Rake $rake): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $this->updateTxrHeader->handle($rake, $validated, (int) $request->user()->id);
        } catch (InvalidArgumentException $e) {
            return to_route('rakes.show', $rake)->with('error', $e->getMessage());
        }

        return to_route('rakes.show', $rake)->with('success', 'TXR updated.');
    }
}
