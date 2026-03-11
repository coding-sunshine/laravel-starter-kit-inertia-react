<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Rake;
use App\Models\Txr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class StoreTyrUnfitLogsAction
{
    /**
     * Replace all unfit logs for the rake's TXR with the given list.
     * Only valid while TXR is in_progress. Wagons must belong to the rake.
     *
     * @param  array<int, array{wagon_id: int, reason?: string|null, marking_method?: string|null, marked_at?: string|null}>  $unfitLogs
     *
     * @throws InvalidArgumentException if no TXR or TXR not in progress
     */
    public function handle(Rake $rake, array $unfitLogs, int $userId): void
    {
        DB::transaction(function () use ($rake, $unfitLogs, $userId): void {
            $txr = $rake->txr;
            if ($txr === null) {
                throw new InvalidArgumentException('No TXR found. Start TXR first.');
            }
            if ($txr->status !== 'in_progress') {
                throw new InvalidArgumentException('Unfit logs can only be saved while TXR is in progress.');
            }

            $rakeWagonIds = $rake->wagons()->pluck('id')->all();

            $txr->wagonUnfitLogs()->delete();

            foreach ($unfitLogs as $log) {
                $wagonId = (int) $log['wagon_id'];
                if (! in_array($wagonId, $rakeWagonIds, true)) {
                    continue;
                }

                $txr->wagonUnfitLogs()->create([
                    'wagon_id' => $wagonId,
                    'reason' => $log['reason'] ?? $log['reason_unfit'] ?? null,
                    'marking_method' => $log['marking_method'] ?? null,
                    'marked_at' => isset($log['marked_at']) ? $log['marked_at'] : now(),
                    'created_by' => $userId,
                ]);
            }
        });
    }
}
